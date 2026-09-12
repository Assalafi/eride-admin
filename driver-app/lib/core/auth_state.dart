import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:local_auth/local_auth.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';
import 'token_store.dart';

class AuthState extends ChangeNotifier {
  bool initializing = true;
  bool busy = false;
  Map<String, dynamic>? user;
  String? error;
  bool hasSavedSession = false;
  bool biometricEnabled = false;

  bool get isLoggedIn => user != null;
  String get displayName => (user?['name'] ?? 'Driver').toString();
  String get email => (user?['email'] ?? '').toString();

  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final token = await TokenStore.read();
    biometricEnabled = prefs.getBool('biometric_enabled') ?? false;
    hasSavedSession = token != null && token.isNotEmpty;

    // Android users who enabled biometrics explicitly unlock the saved session.
    // Web has no local_auth implementation, so a valid web session is restored directly.
    if (hasSavedSession && (!biometricEnabled || kIsWeb)) {
      await _restoreSession();
    }

    await Future<void>.delayed(const Duration(milliseconds: 700));
    initializing = false;
    notifyListeners();
  }

  Future<void> _restoreSession() async {
    try {
      final response = await ApiClient.instance.get('user');
      user = _readUser(response);
      if (user == null) await _clearToken();
    } catch (_) {
      await _clearToken();
    }
  }

  Future<bool> login(String emailAddress, String password) async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      final response = await ApiClient.instance.post('login', {
        'email': emailAddress.trim(),
        'password': password,
      });
      final token = response['token']?.toString();
      if (token == null || token.isEmpty) {
        throw ApiException('Login response did not contain a session token.');
      }
      await TokenStore.write(token);
      user = _readUser(response);
      if (user == null) await _restoreSession();
      hasSavedSession = true;
      busy = false;
      notifyListeners();
      return user != null;
    } on ApiException catch (exception) {
      error = exception.message;
    } catch (_) {
      error = 'Unable to sign in right now.';
    }
    busy = false;
    notifyListeners();
    return false;
  }

  Future<bool> biometricLogin() async {
    if (kIsWeb || !hasSavedSession) return false;
    try {
      final auth = LocalAuthentication();
      if (!await auth.canCheckBiometrics) return false;
      final verified = await auth.authenticate(
          localizedReason: 'Verify your identity to open E-RIDE Driver');
      if (!verified) return false;
      busy = true;
      notifyListeners();
      await _restoreSession();
      busy = false;
      notifyListeners();
      return isLoggedIn;
    } catch (_) {
      busy = false;
      error = 'Biometric verification was not completed.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> toggleBiometric(bool enable) async {
    if (kIsWeb) return false;
    final prefs = await SharedPreferences.getInstance();
    if (enable) {
      try {
        final auth = LocalAuthentication();
        if (!await auth.canCheckBiometrics) return false;
        if (!await auth.authenticate(
            localizedReason:
                'Verify your identity to enable biometric login')) {
          return false;
        }
      } catch (_) {
        return false;
      }
    }
    biometricEnabled = enable;
    await prefs.setBool('biometric_enabled', enable);
    notifyListeners();
    return true;
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.post('logout');
    } catch (_) {}
    await _clearToken();
    user = null;
    hasSavedSession = false;
    notifyListeners();
  }

  Future<void> _clearToken() async {
    await TokenStore.clear();
    hasSavedSession = false;
  }

  Map<String, dynamic>? _readUser(Map<String, dynamic> response) {
    final candidate = response['user'] ?? response['data'];
    if (candidate is Map) return Map<String, dynamic>.from(candidate);
    return null;
  }
}

extension AuthStateProvider on BuildContext {
  AuthState get auth => read<AuthState>();
}

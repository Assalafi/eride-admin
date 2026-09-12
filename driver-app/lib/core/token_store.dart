import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class TokenStore {
  TokenStore._();

  static const _key = 'driver_token';
  static const _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  static Future<String?> read() async {
    final secureToken = await _secureStorage.read(key: _key);
    if (secureToken != null && secureToken.isNotEmpty) return secureToken;

    // Preserve sessions created by earlier app builds, then remove the
    // plaintext copy once it has been migrated into encrypted storage.
    final preferences = await SharedPreferences.getInstance();
    final legacyToken = preferences.getString(_key);
    if (legacyToken != null && legacyToken.isNotEmpty) {
      await write(legacyToken);
      await preferences.remove(_key);
      return legacyToken;
    }
    return null;
  }

  static Future<void> write(String token) =>
      _secureStorage.write(key: _key, value: token);

  static Future<void> clear() async {
    await _secureStorage.delete(key: _key);
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_key);
  }
}

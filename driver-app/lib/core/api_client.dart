import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});
  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient._();

  static final ApiClient instance = ApiClient._();
  static const defaultBaseUrl = 'https://admin.eride.ng/api';
  static const baseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: defaultBaseUrl);

  Future<Map<String, dynamic>> get(String path) => _request('GET', path);

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) =>
      _request('POST', path, body: body);

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('driver_token');
    final uri = Uri.parse('${baseUrl.replaceFirst(RegExp(r'\/$'), '')}/$path');
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Client-Platform': kIsWeb ? 'BROWSER' : 'ANDROID',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };

    late http.Response response;
    try {
      response = method == 'GET'
          ? await http.get(uri, headers: headers)
          : await http.post(uri, headers: headers, body: jsonEncode(body ?? {}));
    } catch (_) {
      throw ApiException('Unable to connect. Check your internet connection.');
    }

    Map<String, dynamic> data = {};
    if (response.body.isNotEmpty) {
      try {
        data = Map<String, dynamic>.from(jsonDecode(response.body) as Map);
      } catch (_) {
        throw ApiException('The server returned an invalid response.', statusCode: response.statusCode);
      }
    }

    if (response.statusCode < 200 || response.statusCode >= 300 || data['success'] == false) {
      final errors = data['errors'];
      final detail = errors is Map ? errors.values.expand((value) => value is List ? value : [value]).join(' ') : null;
      throw ApiException(
        (data['message'] ?? detail ?? 'Request failed').toString(),
        statusCode: response.statusCode,
      );
    }
    return data;
  }
}

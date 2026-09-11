import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Thrown for any non-OK API answer (network, 4xx/5xx, or {"ok": false}).
class ApiException implements Exception {
  ApiException(this.message, {this.status = 0, this.fields = const {}});
  final String message;
  final int status;
  final Map<String, String> fields;
  bool get unauthorized => status == 401;
  @override
  String toString() => message;
}

/// Thin JSON client for the eJahan API (/api/v1). Holds the base URL and the Sanctum token.
class ApiClient {
  ApiClient({required this.baseUrl, this.token});

  static const defaultBase = String.fromEnvironment('API_BASE', defaultValue: 'http://localhost:8088');

  String baseUrl;
  String? token;

  static Future<ApiClient> load() async {
    final p = await SharedPreferences.getInstance();
    final client = ApiClient(baseUrl: p.getString('api.base') ?? defaultBase, token: p.getString('api.token'));
    // Test builds (--dart-define=ALLOW_URL_TOKEN=true) on the web accept ?base=…&token=… so a session can be injected.
    if (kIsWeb && const bool.fromEnvironment('ALLOW_URL_TOKEN')) {
      final q = Uri.base.queryParameters;
      if (q['base'] != null) client.baseUrl = q['base']!;
      if (q['token'] != null) client.token = q['token'];
    }
    return client;
  }

  Future<void> persist() async {
    final p = await SharedPreferences.getInstance();
    await p.setString('api.base', baseUrl);
    if (token == null) {
      await p.remove('api.token');
    } else {
      await p.setString('api.token', token!);
    }
  }

  Uri _uri(String path, [Map<String, String>? query]) {
    final base = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    return Uri.parse('$base/api/v1/$path').replace(queryParameters: query);
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) =>
      _handle(() => http.get(_uri(path, query), headers: _headers));

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic> body = const {}]) =>
      _handle(() => http.post(_uri(path), headers: _headers, body: jsonEncode(body)));

  Future<Map<String, dynamic>> delete(String path) => _handle(() => http.delete(_uri(path), headers: _headers));

  Future<Map<String, dynamic>> _handle(Future<http.Response> Function() call) async {
    http.Response res;
    try {
      res = await call().timeout(const Duration(seconds: 20));
    } catch (e) {
      throw ApiException('Cannot reach the server ($baseUrl).');
    }
    Map<String, dynamic> data;
    try {
      data = jsonDecode(utf8.decode(res.bodyBytes)) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException('Unexpected answer from server (HTTP ${res.statusCode}).', status: res.statusCode);
    }
    if (res.statusCode >= 400 || data['ok'] == false) {
      final fields = <String, String>{};
      (data['fields'] as Map?)?.forEach((k, v) => fields['$k'] = '$v');
      throw ApiException((data['error'] ?? data['message'] ?? 'Request failed').toString(), status: res.statusCode, fields: fields);
    }
    return data;
  }
}

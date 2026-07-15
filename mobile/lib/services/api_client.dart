import 'dart:convert';

import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;
import 'package:http/http.dart' as http;

class ApiClient {
  // Real Android phones can't reach 127.0.0.1 (that's the phone itself), so
  // when testing on a physical device this must be the host PC's LAN IP.
  static const String _lanIp = '192.168.197.74';

  static String get baseUrl {
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://$_lanIp:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }

  final String? token;

  ApiClient({this.token});

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Accept': 'application/json'},
      body: {'email': email, 'password': password},
    );

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchMembership() async {
    final response = await http.get(
      Uri.parse('$baseUrl/member/membership'),
      headers: _headers,
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to load membership.');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Uri qrCodeUrl() => Uri.parse('$baseUrl/member/qr');

  Future<List<dynamic>> fetchClasses() async {
    final response = await http.get(Uri.parse('$baseUrl/classes'), headers: _headers);

    if (response.statusCode != 200) {
      throw Exception('Failed to load classes.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['classes'] as List<dynamic>;
  }

  Future<void> bookClass(int classId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/classes/$classId/book'),
      headers: _headers,
    );

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> cancelBooking(int bookingId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/bookings/$bookingId/cancel'),
      headers: _headers,
    );

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> updateFcmToken(String fcmToken) async {
    await http.post(
      Uri.parse('$baseUrl/member/fcm-token'),
      headers: _headers,
      body: {'fcm_token': fcmToken},
    );
  }

  Future<List<dynamic>> fetchMeasurements() async {
    final response = await http.get(Uri.parse('$baseUrl/member/measurements'), headers: _headers);

    if (response.statusCode != 200) {
      throw Exception('Failed to load measurements.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['measurements'] as List<dynamic>;
  }

  Future<void> logMeasurement({
    required String recordedAt,
    double? weightKg,
    double? bodyFatPercentage,
    double? chestCm,
    double? waistCm,
    double? hipsCm,
    double? armsCm,
    String? notes,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/member/measurements'),
      headers: _headers,
      body: {
        'recorded_at': recordedAt,
        if (weightKg != null) 'weight_kg': weightKg.toString(),
        if (bodyFatPercentage != null) 'body_fat_percentage': bodyFatPercentage.toString(),
        if (chestCm != null) 'chest_cm': chestCm.toString(),
        if (waistCm != null) 'waist_cm': waistCm.toString(),
        if (hipsCm != null) 'hips_cm': hipsCm.toString(),
        if (armsCm != null) 'arms_cm': armsCm.toString(),
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );

    if (response.statusCode != 201) {
      throw Exception(_extractError(response.body));
    }
  }

  String get authToken => token ?? '';

  String _extractError(String body) {
    try {
      final data = jsonDecode(body) as Map<String, dynamic>;
      final errors = data['errors'] as Map<String, dynamic>?;
      if (errors != null && errors.isNotEmpty) {
        return (errors.values.first as List).first.toString();
      }
      return data['message']?.toString() ?? 'Something went wrong.';
    } catch (_) {
      return 'Something went wrong.';
    }
  }
}

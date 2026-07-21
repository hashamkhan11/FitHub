import 'dart:convert';

import 'package:fithub_app/services/api_client.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  group('ApiClient.login', () {
    test('returns the decoded body on success', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          expect(request.url.path, endsWith('/login'));
          return http.Response(
            jsonEncode({'token': 'abc123', 'member': {'id': 1, 'name': 'Jane'}}),
            200,
          );
        }),
      );

      final result = await client.login('jane@example.com', 'secret');

      expect(result['token'], 'abc123');
      expect(result['member']['name'], 'Jane');
    });

    test('throws the validation message on a failed login', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          return http.Response(
            jsonEncode({
              'message': 'The given data was invalid.',
              'errors': {
                'email': ['The provided credentials are incorrect.'],
              },
            }),
            422,
          );
        }),
      );

      expect(
        () => client.login('jane@example.com', 'wrong'),
        throwsA(
          isA<Exception>().having(
            (e) => e.toString(),
            'message',
            contains('The provided credentials are incorrect.'),
          ),
        ),
      );
    });
  });

  group('ApiClient.bookClass', () {
    test('completes without error on a 200 response', () async {
      final client = ApiClient(
        token: 'a-token',
        client: MockClient((request) async {
          expect(request.headers['Authorization'], 'Bearer a-token');
          return http.Response(jsonEncode({'booking': {'status': 'booked'}}), 200);
        }),
      );

      await client.bookClass(4);
    });

    test('surfaces the server error message on failure', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          return http.Response(
            jsonEncode({'message': 'This member already has a booking for this class.'}),
            422,
          );
        }),
      );

      expect(
        () => client.bookClass(4),
        throwsA(
          isA<Exception>().having(
            (e) => e.toString(),
            'message',
            contains('This member already has a booking for this class.'),
          ),
        ),
      );
    });
  });

  group('ApiClient.fetchClasses', () {
    test('returns the classes list', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          return http.Response(
            jsonEncode({
              'classes': [
                {'id': 1, 'name': 'Spin'},
                {'id': 2, 'name': 'Yoga'},
              ],
            }),
            200,
          );
        }),
      );

      final classes = await client.fetchClasses();

      expect(classes, hasLength(2));
      expect(classes[0]['name'], 'Spin');
    });

    test('throws a generic exception on a server error', () async {
      final client = ApiClient(
        client: MockClient((request) async => http.Response('', 500)),
      );

      expect(() => client.fetchClasses(), throwsA(isA<Exception>()));
    });
  });

  group('ApiClient.fetchAttendance', () {
    test('returns the attendance list', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          return http.Response(
            jsonEncode({
              'attendance': [
                {'id': 1, 'checked_in_at': '2026-07-15T10:00:00Z', 'checked_out_at': null},
              ],
            }),
            200,
          );
        }),
      );

      final attendance = await client.fetchAttendance();

      expect(attendance, hasLength(1));
      expect(attendance[0]['checked_out_at'], isNull);
    });

    test('throws a generic exception on a server error', () async {
      final client = ApiClient(
        client: MockClient((request) async => http.Response('', 500)),
      );

      expect(() => client.fetchAttendance(), throwsA(isA<Exception>()));
    });
  });

  group('ApiClient.fetchMeasurements', () {
    test('returns the measurements list', () async {
      final client = ApiClient(
        client: MockClient((request) async {
          return http.Response(
            jsonEncode({
              'measurements': [
                {'id': 1, 'weight_kg': '82.50'},
              ],
            }),
            200,
          );
        }),
      );

      final measurements = await client.fetchMeasurements();

      expect(measurements, hasLength(1));
      expect(measurements[0]['weight_kg'], '82.50');
    });
  });
}

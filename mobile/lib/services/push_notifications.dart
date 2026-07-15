import 'package:firebase_messaging/firebase_messaging.dart';

import 'api_client.dart';

Future<void> registerPushToken(ApiClient client) async {
  final messaging = FirebaseMessaging.instance;

  await messaging.requestPermission();

  final token = await messaging.getToken();
  if (token != null) {
    await client.updateFcmToken(token);
  }
}

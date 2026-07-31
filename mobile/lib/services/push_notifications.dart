import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../providers/navigation_provider.dart';
import 'api_client.dart';

const _lastFcmTokenKey = 'last_registered_fcm_token';

const _channel = AndroidNotificationChannel(
  'fithub_default',
  'FitHub notifications',
  description: 'Booking, class, progress and renewal reminders',
  importance: Importance.high,
);

final _localNotifications = FlutterLocalNotificationsPlugin();

/// Doesn't touch Firebase, so it can run concurrently with
/// `Firebase.initializeApp()` in `main()` instead of waiting behind it.
Future<void> initLocalNotifications() async {
  await _localNotifications.initialize(
    const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    ),
  );

  await _localNotifications
      .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(_channel);
}

/// Requires Firebase to already be initialized — call after
/// `Firebase.initializeApp()` resolves.
void listenForForegroundMessages() {
  FirebaseMessaging.onMessage.listen(_showForegroundNotification);
}

void _showForegroundNotification(RemoteMessage message) {
  final notification = message.notification;
  if (notification == null) return;

  _localNotifications.show(
    notification.hashCode,
    notification.title,
    notification.body,
    NotificationDetails(
      android: AndroidNotificationDetails(
        _channel.id,
        _channel.name,
        channelDescription: _channel.description,
        importance: Importance.high,
        priority: Priority.high,
      ),
    ),
  );
}

Future<void> initNotificationTapHandling(ProviderContainer container) async {
  final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
  if (initialMessage != null) {
    _routeToNotificationTab(initialMessage, container);
  }

  FirebaseMessaging.onMessageOpenedApp.listen(
    (message) => _routeToNotificationTab(message, container),
  );
}

void _routeToNotificationTab(RemoteMessage message, ProviderContainer container) {
  final index = notificationTypeTabIndex[message.data['type']];
  if (index != null) {
    container.read(selectedTabProvider.notifier).select(index);
  }
}

/// Skips the `updateFcmToken` network call when the token hasn't changed
/// since the last time it was successfully registered, so a normal app open
/// doesn't cost a request that has nothing new to report.
Future<void> registerPushToken(ApiClient client) async {
  final messaging = FirebaseMessaging.instance;

  await messaging.requestPermission();

  final token = await messaging.getToken();
  if (token == null) return;

  final prefs = await SharedPreferences.getInstance();
  if (prefs.getString(_lastFcmTokenKey) == token) return;

  await client.updateFcmToken(token);
  await prefs.setString(_lastFcmTokenKey, token);
}

import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'providers/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/main_shell.dart';
import 'services/push_notifications.dart';
import 'theme/app_theme.dart';

void main() async {
  final widgetsBinding = WidgetsFlutterBinding.ensureInitialized();
  FlutterNativeSplash.preserve(widgetsBinding: widgetsBinding);

  // initLocalNotifications() doesn't touch Firebase, so it runs alongside
  // Firebase.initializeApp() instead of waiting behind it.
  await Future.wait([Firebase.initializeApp(), initLocalNotifications()]);
  listenForForegroundMessages();

  final container = ProviderContainer();

  FlutterNativeSplash.remove();
  runApp(UncontrolledProviderScope(container: container, child: const FitHubApp()));

  // Only matters if the app was cold-launched from a tapped notification, so
  // it runs after the first frame rather than gating every launch on it.
  unawaited(initNotificationTapHandling(container));
}

class FitHubApp extends ConsumerWidget {
  const FitHubApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    return MaterialApp(
      title: 'FitHub',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.theme,
      home: auth.isLoggedIn ? const MainShell() : const LoginScreen(),
    );
  }
}

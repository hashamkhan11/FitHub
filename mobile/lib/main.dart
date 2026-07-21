import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'providers/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/main_shell.dart';
import 'services/push_notifications.dart';
import 'theme/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  await initPushNotifications();
  runApp(const ProviderScope(child: FitHubApp()));
}

class FitHubApp extends ConsumerWidget {
  const FitHubApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    return MaterialApp(
      title: 'FitHub',
      theme: AppTheme.theme,
      home: auth.isLoggedIn ? const MainShell() : const LoginScreen(),
    );
  }
}

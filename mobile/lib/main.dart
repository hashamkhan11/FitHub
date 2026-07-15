import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'providers/auth_provider.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(const ProviderScope(child: FitHubApp()));
}

class FitHubApp extends ConsumerWidget {
  const FitHubApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    return MaterialApp(
      title: 'FitHub',
      theme: ThemeData(colorSchemeSeed: Colors.blue, useMaterial3: true),
      home: auth.isLoggedIn ? const HomeScreen() : const LoginScreen(),
    );
  }
}

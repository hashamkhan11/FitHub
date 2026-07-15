import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/api_client.dart';
import '../services/push_notifications.dart';

class AuthState {
  final String? token;
  final Map<String, dynamic>? member;

  const AuthState({this.token, this.member});

  bool get isLoggedIn => token != null;
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    _restoreSession();
    return const AuthState();
  }

  Future<void> _restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token != null) {
      state = AuthState(token: token);
      await registerPushToken(ApiClient(token: token));
    }
  }

  Future<void> login(String email, String password) async {
    final client = ApiClient();
    final data = await client.login(email, password);

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', data['token'] as String);

    state = AuthState(
      token: data['token'] as String,
      member: data['member'] as Map<String, dynamic>,
    );

    await registerPushToken(ApiClient(token: data['token'] as String));
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    state = const AuthState();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

final apiClientProvider = Provider<ApiClient>((ref) {
  final token = ref.watch(authProvider).token;
  return ApiClient(token: token);
});

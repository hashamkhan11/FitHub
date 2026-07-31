import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../services/api_client.dart';
import '../services/push_notifications.dart';

const _secureStorage = FlutterSecureStorage();

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
    final token = await _secureStorage.read(key: 'token');
    if (token != null) {
      state = AuthState(token: token);
      await registerPushToken(ApiClient(token: token));
    }
  }

  Future<void> login(String email, String password) async {
    final client = ApiClient();
    final data = await client.login(email, password);

    await _secureStorage.write(key: 'token', value: data['token'] as String);

    state = AuthState(
      token: data['token'] as String,
      member: data['member'] as Map<String, dynamic>,
    );

    await registerPushToken(ApiClient(token: data['token'] as String));
  }

  Future<void> logout() async {
    final token = state.token;
    if (token != null) {
      // Best-effort server-side revoke — if the device is offline or the
      // token's already invalid, still clear local state below so the user
      // isn't stuck "logged in" on this device.
      try {
        await ApiClient(token: token).logout();
      } catch (_) {}
    }

    await _clearLocalSession();
  }

  /// Drops local session state without calling the server — used when a
  /// request comes back 401, so the already-invalid token isn't retried.
  Future<void> forceLogout() async {
    if (state.token == null) return;
    await _clearLocalSession();
  }

  Future<void> _clearLocalSession() async {
    await _secureStorage.delete(key: 'token');
    state = const AuthState();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

final apiClientProvider = Provider<ApiClient>((ref) {
  final token = ref.watch(authProvider).token;
  return ApiClient(
    token: token,
    onSessionExpired: () => ref.read(authProvider.notifier).forceLogout(),
  );
});

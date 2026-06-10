import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';

// Notifier holds the logged-in user; null = unauthenticated
class AuthNotifier extends AsyncNotifier<UserModel?> {
  final _service = AuthService();

  @override
  Future<UserModel?> build() async {
    final hasToken = await _service.hasToken();
    if (!hasToken) return null;
    try {
      return await _service.me();
    } catch (_) {
      return null;
    }
  }

  Future<UserModel> login(String phone, String password) async {
    state = const AsyncLoading();
    final result = await _service.login(phone, password);
    state = AsyncData(result.user);
    return result.user;
  }

  Future<void> logout() async {
    await _service.logout();
    state = const AsyncData(null);
  }

  Future<void> updateLocale(String locale) async {
    await _service.updateLocale(locale);
    // Refresh user to reflect locale change
    final user = state.valueOrNull;
    if (user != null) {
      state = AsyncData(UserModel(
        id: user.id,
        name: user.name,
        phone: user.phone,
        email: user.email,
        role: user.role,
        status: user.status,
        locale: locale,
        avatar: user.avatar,
        driver: user.driver,
        restaurant: user.restaurant,
      ));
    }
  }
}

final authProvider = AsyncNotifierProvider<AuthNotifier, UserModel?>(AuthNotifier.new);

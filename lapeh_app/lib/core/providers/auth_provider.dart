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

  /// Register a sender; the returned dev OTP (if any) helps testing.
  Future<({UserModel user, String? devOtp})> registerSender({
    required String type,
    required String name,
    required String phone,
    required String password,
    String? defaultPickupAddress,
    double? defaultPickupLat,
    double? defaultPickupLng,
    String? businessName,
    String? businessCategory,
    String? contactPersonName,
  }) async {
    state = const AsyncLoading();
    final result = await _service.registerSender(
      type: type, name: name, phone: phone, password: password,
      defaultPickupAddress: defaultPickupAddress,
      defaultPickupLat: defaultPickupLat,
      defaultPickupLng: defaultPickupLng,
      businessName: businessName,
      businessCategory: businessCategory,
      contactPersonName: contactPersonName,
    );
    state = AsyncData(result.user);
    return (user: result.user, devOtp: result.devOtp);
  }

  Future<UserModel> verifyOtp(String code) async {
    final user = await _service.verifyOtp(code);
    state = AsyncData(user);
    return user;
  }

  Future<String?> resendOtp() => _service.resendOtp();

  Future<void> logout() async {
    await _service.logout();
    state = const AsyncData(null);
  }

  Future<void> updateLocale(String locale) async {
    await _service.updateLocale(locale);
    final user = state.valueOrNull;
    if (user != null) {
      state = AsyncData(user.copyWith(locale: locale));
    }
  }

  /// Replace the cached user after a profile/password update returns fresh data.
  void setUser(UserModel user) => state = AsyncData(user);

  /// Re-fetch the authenticated user from the backend.
  Future<void> refreshUser() async {
    final user = await _service.me();
    state = AsyncData(user);
  }
}

final authProvider = AsyncNotifierProvider<AuthNotifier, UserModel?>(AuthNotifier.new);

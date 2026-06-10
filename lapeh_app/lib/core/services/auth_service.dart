import '../api_client.dart';
import '../models/user_model.dart';

class AuthService {
  final _api = ApiClient();

  Future<({String token, UserModel user})> login(String phone, String password) async {
    final res = await _api.dio.post('/auth/login', data: {
      'phone': phone,
      'password': password,
    });
    final token = res.data['token'] as String;
    final user = UserModel.fromJson(res.data['user']);
    await _api.saveToken(token);
    return (token: token, user: user);
  }

  /// Register a sender. Returns the token, user and (in dev) the OTP code.
  Future<({String token, UserModel user, String? devOtp})> registerSender({
    required String type, // individual | business
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
    final res = await _api.dio.post('/auth/register-sender', data: {
      'type': type,
      'name': name,
      'phone': phone,
      'password': password,
      if (defaultPickupAddress != null) 'default_pickup_address': defaultPickupAddress,
      if (defaultPickupLat != null) 'default_pickup_lat': defaultPickupLat,
      if (defaultPickupLng != null) 'default_pickup_lng': defaultPickupLng,
      if (businessName != null) 'business_name': businessName,
      if (businessCategory != null) 'business_category': businessCategory,
      if (contactPersonName != null) 'contact_person_name': contactPersonName,
    });
    final token = res.data['token'] as String;
    await _api.saveToken(token);
    return (
      token: token,
      user: UserModel.fromJson(res.data['user']),
      devOtp: res.data['dev_otp'] as String?,
    );
  }

  Future<UserModel> verifyOtp(String code) async {
    final res = await _api.dio.post('/auth/verify-otp', data: {'code': code});
    return UserModel.fromJson(res.data['user']);
  }

  Future<String?> resendOtp() async {
    final res = await _api.dio.post('/auth/resend-otp');
    return res.data['dev_otp'] as String?;
  }

  Future<void> logout() async {
    try {
      await _api.dio.post('/auth/logout');
    } catch (_) {}
    await _api.clearToken();
  }

  Future<UserModel> me() async {
    final res = await _api.dio.get('/auth/me');
    return UserModel.fromJson(res.data['user']);
  }

  Future<void> updateFcmToken(String fcmToken) async {
    await _api.dio.post('/auth/fcm-token', data: {'fcm_token': fcmToken});
  }

  Future<void> updateLocale(String locale) async {
    await _api.dio.patch('/auth/locale', data: {'locale': locale});
  }

  Future<bool> hasToken() async => (await _api.getToken()) != null;
}

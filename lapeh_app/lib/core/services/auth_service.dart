import 'package:dio/dio.dart';
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

  /// Update the current user's own account fields (name/email/avatar).
  /// Uses multipart when an avatar file path is supplied. Returns updated user.
  Future<UserModel> updateProfile({
    String? name,
    String? email,
    List<int>? avatarBytes,
    String avatarFilename = 'avatar.jpg',
  }) async {
    final map = <String, dynamic>{
      // PHP doesn't parse multipart bodies on PATCH, so POST + Laravel's
      // `_method` spoofing routes this to the PATCH endpoint correctly.
      '_method': 'PATCH',
      if (name != null) 'name': name,
      if (email != null) 'email': email,
      // Bytes (not a file path) so the same call works on web and mobile.
      if (avatarBytes != null) 'avatar': MultipartFile.fromBytes(avatarBytes, filename: avatarFilename),
    };
    final res = await _api.dio.post(
      '/auth/profile',
      data: FormData.fromMap(map),
      options: Options(contentType: 'multipart/form-data'),
    );
    return UserModel.fromJson(res.data['user']);
  }

  Future<void> changePassword({required String currentPassword, required String newPassword}) async {
    await _api.dio.post('/auth/change-password', data: {
      'current_password': currentPassword,
      'password': newPassword,
      'password_confirmation': newPassword,
    });
  }

  Future<bool> hasToken() async => (await _api.getToken()) != null;
}

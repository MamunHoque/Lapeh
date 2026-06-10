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

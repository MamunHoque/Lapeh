import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'constants.dart';
import 'i18n.dart';

/// Human-readable message for any thrown API error.
/// Prefers Laravel's first validation error / `message`, falls back to
/// localized network/server strings — never shows raw DioException text.
String apiErrorMessage(Object e) {
  if (e is DioException) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.connectionError:
        return tr('err_network');
      default:
        break;
    }
    final res = e.response;
    if (res != null) {
      final data = res.data;
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
        }
        final msg = data['message'];
        if (msg is String && msg.isNotEmpty && msg != 'Unauthenticated.') return msg;
      }
      final code = res.statusCode ?? 0;
      if (code == 401) return tr('err_unauthorized');
      if (code >= 500) return tr('err_server');
    }
  }
  return tr('err_generic');
}

class ApiClient {
  static final ApiClient _instance = ApiClient._();
  factory ApiClient() => _instance;
  ApiClient._() {
    _dio = Dio(BaseOptions(
      baseUrl: ApiConfig.baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: AppStrings.tokenKey);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Expired/invalid token: drop it so next launch lands on login.
        final path = error.requestOptions.path;
        if (error.response?.statusCode == 401 && !path.contains('/auth/login')) {
          _storage.delete(key: AppStrings.tokenKey);
        }
        return handler.next(error);
      },
    ));
  }

  late final Dio _dio;
  final _storage = const FlutterSecureStorage();

  Dio get dio => _dio;

  Future<void> saveToken(String token) =>
      _storage.write(key: AppStrings.tokenKey, value: token);

  Future<String?> getToken() => _storage.read(key: AppStrings.tokenKey);

  Future<void> clearToken() => _storage.delete(key: AppStrings.tokenKey);
}

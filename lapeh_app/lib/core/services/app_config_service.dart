import '../api_client.dart';
import '../models/app_config_model.dart';

/// Loads and caches the runtime [AppConfig] from the API. The last good value
/// is held in [current] for synchronous reads (e.g. the Maps key, router
/// redirects) and defaults to [AppConfig.fallback] when offline.
class AppConfigService {
  AppConfigService._();
  static final AppConfigService instance = AppConfigService._();
  factory AppConfigService() => instance;

  final _api = ApiClient();
  AppConfig current = AppConfig.fallback;

  Future<AppConfig> load() async {
    try {
      final res = await _api.dio.get('/meta/app-config');
      if (res.data is Map) {
        current = AppConfig.fromJson(Map<String, dynamic>.from(res.data));
      }
    } catch (_) {
      // Offline / server unreachable — keep the last good (or fallback) config.
    }
    return current;
  }
}

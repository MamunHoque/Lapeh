import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/app_config_model.dart';
import '../services/app_config_service.dart';

/// Reactive access to the runtime app config. Refetches on demand; UI should
/// fall back to [AppConfigService.instance.current] while loading.
final appConfigProvider = FutureProvider<AppConfig>((ref) async {
  return AppConfigService.instance.load();
});

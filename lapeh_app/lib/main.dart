import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/theme.dart';
import 'core/app_state.dart';
import 'core/router.dart';
import 'core/services/app_config_service.dart';
import 'core/maps_bootstrap_stub.dart'
    if (dart.library.html) 'core/maps_bootstrap_web.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase init — requires google-services.json (Android) and
  // GoogleService-Info.plist (iOS) from the client's Firebase project.
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase not configured yet — FCM push will be unavailable.
  }
  // Load runtime app config (branding, Maps key, registration flags). Bounded
  // so a slow/offline server never blocks launch — UI falls back to defaults.
  final config = await AppConfigService.instance.load().timeout(
        const Duration(seconds: 4),
        onTimeout: () => AppConfigService.instance.current,
      );

  // On web, inject the Maps SDK with the runtime key (no hardcoded key in HTML).
  if (kIsWeb) {
    await loadGoogleMaps(config.mapsKey);
  }

  runApp(const ProviderScope(child: LapehApp()));
}

class LapehApp extends ConsumerWidget {
  const LapehApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return ValueListenableBuilder<Locale>(
      valueListenable: localeNotifier,
      builder: (context, locale, _) {
        return MaterialApp.router(
          title: 'Lapeh',
          debugShowCheckedModeBanner: false,
          theme: buildLapehTheme(),
          locale: locale,
          supportedLocales: const [Locale('en'), Locale('ar')],
          localizationsDelegates: const [
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          routerConfig: router,
        );
      },
    );
  }
}

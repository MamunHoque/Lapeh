import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/theme.dart';
import 'core/app_state.dart';
import 'core/router.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase init — requires google-services.json (Android) and
  // GoogleService-Info.plist (iOS) from the client's Firebase project.
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase not configured yet — FCM push will be unavailable.
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

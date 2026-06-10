import 'package:flutter/material.dart';

/// Global app state (kept dependency-free with ValueNotifiers).
/// Swap for Riverpod/Bloc when wiring to the real API.
final ValueNotifier<Locale> localeNotifier = ValueNotifier(const Locale('en'));
final ValueNotifier<bool> driverOnline = ValueNotifier(false);

void toggleLocale() {
  localeNotifier.value =
      localeNotifier.value.languageCode == 'en' ? const Locale('ar') : const Locale('en');
}

bool get isArabic => localeNotifier.value.languageCode == 'ar';

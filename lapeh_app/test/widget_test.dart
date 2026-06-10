// Localization tests: verify the tr() lookup resolves keys in both locales
// and falls back to English for unknown locales.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:lapeh_app/core/app_state.dart';
import 'package:lapeh_app/core/i18n.dart';

void main() {
  test('tr resolves English strings', () {
    localeNotifier.value = const Locale('en');
    expect(tr('sign_in'), 'Sign in');
    expect(tr('reject'), 'Reject');
    expect(tr('new_request'), 'New request');
  });

  test('tr resolves Arabic strings', () {
    localeNotifier.value = const Locale('ar');
    expect(tr('sign_in'), 'تسجيل الدخول');
    expect(tr('reject'), 'رفض');
    expect(tr('accept'), 'قبول');
  });

  test('tr falls back to the key when missing', () {
    localeNotifier.value = const Locale('en');
    expect(tr('__missing_key__'), '__missing_key__');
  });
}

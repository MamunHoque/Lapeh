import 'package:flutter/material.dart';

/// Lapeh brand palette
class AppColors {
  static const pink = Color(0xFFFB0E72);
  static const pinkDeep = Color(0xFFD1005C);
  static const pinkSoft = Color(0xFFFFF0F6);
  static const ink = Color(0xFF14192B);
  static const ink2 = Color(0xFF1C2336);
  static const slate = Color(0xFF6B748A);
  static const slate2 = Color(0xFF9CA4B8);
  static const line = Color(0xFFEAECF2);
  static const bg = Color(0xFFF4F6FB);
  static const card = Colors.white;

  static const green = Color(0xFF0E9E6E);
  static const greenSoft = Color(0xFFE3F7EF);
  static const blue = Color(0xFF3457D5);
  static const blueSoft = Color(0xFFE8EEFF);
  static const indigo = Color(0xFF7C5CFC);
  static const indigoSoft = Color(0xFFEFE9FF);
  static const amber = Color(0xFFE08600);
  static const amberSoft = Color(0xFFFFF2D9);
  static const red = Color(0xFFE03131);
  static const redSoft = Color(0xFFFDE7E7);
}

ThemeData buildLapehTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.pink,
      primary: AppColors.pink,
      surface: Colors.white,
    ),
    scaffoldBackgroundColor: AppColors.bg,
  );

  return base.copyWith(
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.bg,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      centerTitle: false,
      iconTheme: IconThemeData(color: AppColors.ink),
      titleTextStyle: TextStyle(
        color: AppColors.ink,
        fontSize: 18,
        fontWeight: FontWeight.w700,
      ),
    ),
    textTheme: base.textTheme.apply(
      bodyColor: AppColors.ink,
      displayColor: AppColors.ink,
    ),
    dividerColor: AppColors.line,
  );
}

/// Common text styles
class T {
  static const h1 = TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.ink, letterSpacing: -0.4);
  static const h2 = TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.ink);
  static const title = TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink);
  static const body = TextStyle(fontSize: 14, color: AppColors.ink, height: 1.45);
  static const muted = TextStyle(fontSize: 12.5, color: AppColors.slate);
  static const mutedSm = TextStyle(fontSize: 11.5, color: AppColors.slate);
}

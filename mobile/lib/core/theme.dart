import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// The 2026 "command center" look: deep navy, glass cards, violet→cyan accents, gold for rewards.
class EjColors {
  static const bg = Color(0xFF0B1020);
  static const bg2 = Color(0xFF151C35);
  static const card = Color(0xCC141B34);
  static const line = Color(0x298CA0FF);
  static const text = Color(0xFFE8ECF8);
  static const muted = Color(0xFF9AA5C8);
  static const accent = Color(0xFF7C5CFF);
  static const accent2 = Color(0xFF22D3EE);
  static const gold = Color(0xFFF5C542);
  static const green = Color(0xFF34D399);
  static const red = Color(0xFFFB7185);
}

ThemeData ejTheme() {
  final base = ThemeData.dark(useMaterial3: true);
  final text = GoogleFonts.interTextTheme(base.textTheme).apply(bodyColor: EjColors.text, displayColor: EjColors.text);
  return base.copyWith(
    scaffoldBackgroundColor: EjColors.bg,
    colorScheme: base.colorScheme.copyWith(primary: EjColors.accent, secondary: EjColors.accent2, surface: EjColors.bg2, error: EjColors.red),
    textTheme: text,
    appBarTheme: const AppBarTheme(backgroundColor: Colors.transparent, elevation: 0, foregroundColor: EjColors.text),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.black.withValues(alpha: .3),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: EjColors.line)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: EjColors.line)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: EjColors.accent2)),
      hintStyle: const TextStyle(color: EjColors.muted),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: EjColors.bg2.withValues(alpha: .95),
      indicatorColor: EjColors.accent.withValues(alpha: .35),
      labelTextStyle: WidgetStatePropertyAll(GoogleFonts.rajdhani(fontWeight: FontWeight.w600, fontSize: 12, color: EjColors.text)),
      iconTheme: const WidgetStatePropertyAll(IconThemeData(color: EjColors.text)),
    ),
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
  );
}

TextStyle display({double size = 18, Color color = Colors.white, FontWeight weight = FontWeight.w700}) =>
    GoogleFonts.rajdhani(fontSize: size, fontWeight: weight, color: color, letterSpacing: .5);

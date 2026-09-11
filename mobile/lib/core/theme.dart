import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// The original eJahan palette (include/css/style_main.css): light theme,
/// sky/city ambient, dark-blue citizen bar, light-blue menubar, grey body text.
class EjColors {
  static const text = Color(0xFF4B4E50); // body copy
  static const black = Color(0xFF000000);
  static const link = Color(0xFF0B5FA5); // legacy anchor blue
  static const green = Color(0xFF008000); // task titles / notices
  static const lime = Color(0xFF33CC33); // level badge / wellness bar
  static const red = Color(0xFFD40000);
  static const line = Color(0xFFB0B0B0); // 1px borders
  static const page = Color(0xCCFFFFFF); // translucent white page over the ambient
  static const box = Color(0xFFFFFFFF);
  static const infoBox = Color(0xFF2E4C7A); // topbar citizen boxes
  static const infoBorder = Color(0xFF7DA0D6);
  static const infoText = Color(0xFFE8EEF7);
  static const menu = Color(0xFF6CB7EA); // menubar strip
  static const skyTop = Color(0xFFBFE3F7);
  static const reportBlue = Color(0xFF66BBFF); // #6BF gradient in army/work reports
  static const tabHover = Color(0xFF66BBFF);
}

/// The modern game skin (from the gym): dark graphite chrome, cyan / orange accents, glowing meters.
class Gym {
  static const bg = Color(0xFF0B1118);
  static const dark = Color(0xFF0F1620);
  static const panel = Color(0xFF1B2533);
  static const panel2 = Color(0xFF223042);
  static const line = Color(0xFF2A3A4F);
  static const cyan = Color(0xFF22D3EE);
  static const cyanSoft = Color(0xFF9FE7FF);
  static const orange = Color(0xFFFF8A3D);
  static const green = Color(0xFF7CFC9A);
  static const gold = Color(0xFFFFC94D);
  static const red = Color(0xFFFF6B6B);
  static const text = Color(0xFFE8EEF7);
  static const muted = Color(0xFF9FB3C8);

  static BoxDecoration card({Color? border, bool glow = false, Color glowColor = cyan, double radius = 12}) => BoxDecoration(
    gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [panel, dark]),
    border: Border.all(color: border ?? line, width: border == null ? 1 : 1.5),
    borderRadius: BorderRadius.circular(radius),
    boxShadow: glow ? [BoxShadow(color: glowColor.withValues(alpha: .45), blurRadius: 18)] : const [BoxShadow(color: Color(0x66000000), blurRadius: 12, offset: Offset(0, 4))],
  );
}

/// Legacy body font: Arial/Tahoma 10pt.
const ejFontFamily = 'Arial';

/// `.home-box-title` — "Harlow Solid Italic" script; Kaushan Script is the closest web font.
TextStyle boxTitle({double size = 20, Color color = EjColors.black}) => GoogleFonts.kaushanScript(fontSize: size, color: color, fontStyle: FontStyle.italic);

ThemeData ejTheme() {
  const scheme = ColorScheme.light(primary: EjColors.link, secondary: EjColors.menu, surface: EjColors.box, error: EjColors.red, onSurface: EjColors.text);
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: Gym.bg,
    fontFamily: ejFontFamily,
    textTheme: const TextTheme(
      bodyMedium: TextStyle(fontSize: 13, color: EjColors.text, height: 1.3),
      bodySmall: TextStyle(fontSize: 11, color: EjColors.text),
      titleMedium: TextStyle(fontSize: 14, color: EjColors.black, fontWeight: FontWeight.bold),
    ),
    dividerTheme: const DividerThemeData(color: EjColors.line, thickness: 1, space: 8),
    inputDecorationTheme: InputDecorationTheme(
      isDense: true,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(3),
        borderSide: const BorderSide(color: EjColors.line),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(3),
        borderSide: const BorderSide(color: EjColors.line),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(3),
        borderSide: const BorderSide(color: EjColors.link),
      ),
      hintStyle: const TextStyle(color: Color(0xFF9A9A9A), fontSize: 12),
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: EjColors.link),
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
    appBarTheme: const AppBarTheme(backgroundColor: Gym.dark, foregroundColor: Colors.white, elevation: 0, centerTitle: true),
  );
}

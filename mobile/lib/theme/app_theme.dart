import 'package:flutter/material.dart';

/// FitHub's "Pulse" identity — a dense, dark-mode-first operator palette:
/// cyan accent, hairline borders, no shadows, tabular-numeral figures.
/// Mirrors the palette used by the web admin (tailwind.config.js) so both
/// surfaces read as the same brand. The whole app now commits to the dark
/// ground — there is no separate light "paper" mode anymore.
class AppColors {
  AppColors._();

  /// Deepest background — scaffold/page bg and one end of gradient panels.
  static const voidBg = Color(0xFF0D0F12);

  static const paper = Color(0xFF171B20);
  static const paper2 = Color(0xFF1A1E23);
  static const paper3 = Color(0xFF262B31);

  static const ink = Color(0xFFE7EAEE);
  static const ink2 = Color(0xFF0D0F12);

  static const steel = Color(0xFF8D96A0);
  static const steel2 = Color(0xFF565F6A);

  static const gold = Color(0xFF3DD6D0);
  static const goldDeep = Color(0xFF22B0AB);
  static const goldLight = Color(0xFF7FE9E4);
  static const blue = Color(0xFF5B8DEF);
  static const blueDeep = Color(0xFF3D6FD1);

  static const turf = Color(0xFF4ADE80);
  static const tape = Color(0xFFFB6B6B);
  static const warn = Color(0xFFF5B94D);
}

class AppTheme {
  AppTheme._();

  /// Captions, stamps, numbers — tabular-nums figures throughout.
  static TextStyle mono({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.w500,
    Color color = AppColors.ink,
    double letterSpacing = 0,
  }) {
    return TextStyle(
      fontFamily: 'monospace',
      fontFamilyFallback: const ['Cascadia Mono', 'Consolas', 'Courier New', 'Courier'],
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      fontFeatures: const [FontFeature.tabularFigures()],
    );
  }

  /// Headlines and labels — a regular system sans, no condensed treatment.
  static TextStyle display({
    double fontSize = 16,
    FontWeight fontWeight = FontWeight.w600,
    Color color = AppColors.ink,
    double letterSpacing = 0.2,
  }) {
    return TextStyle(
      fontFamily: 'Segoe UI',
      fontFamilyFallback: const ['Roboto', 'Helvetica', 'Arial'],
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      height: 1.15,
    );
  }

  /// The hairline border used by cards, inputs, and buttons — a thin
  /// low-contrast rule rather than a heavy 2px ink stroke.
  static Border get inkBorder => Border.all(color: AppColors.ink2, width: 1);

  static ThemeData get theme {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: AppColors.voidBg,
      fontFamily: 'Segoe UI',
      colorScheme: const ColorScheme.dark(
        primary: AppColors.gold,
        onPrimary: AppColors.voidBg,
        secondary: AppColors.blue,
        onSecondary: AppColors.voidBg,
        surface: AppColors.paper2,
        onSurface: AppColors.ink,
        error: AppColors.tape,
        onError: AppColors.voidBg,
      ),
    );

    return base.copyWith(
      textTheme: base.textTheme.apply(bodyColor: AppColors.ink, displayColor: AppColors.ink),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.voidBg,
        foregroundColor: AppColors.ink,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: display(fontSize: 20, fontWeight: FontWeight.w600),
        shape: const Border(bottom: BorderSide(color: AppColors.ink2, width: 1)),
      ),
      cardTheme: CardThemeData(
        color: AppColors.paper2,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
          side: const BorderSide(color: AppColors.ink2, width: 1),
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.ink2, thickness: 1, space: 1),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.paper3,
        labelStyle: const TextStyle(color: AppColors.steel),
        floatingLabelStyle: const TextStyle(color: AppColors.gold),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.ink2, width: 1),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.ink2, width: 1),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.gold, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.gold,
          foregroundColor: AppColors.voidBg,
          minimumSize: const Size.fromHeight(48),
          textStyle: display(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.voidBg, letterSpacing: 0.4),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.blue,
          side: const BorderSide(color: AppColors.blue, width: 1),
          minimumSize: const Size.fromHeight(48),
          textStyle: display(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.blue, letterSpacing: 0.4),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.gold,
          textStyle: display(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.gold, letterSpacing: 0.4),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.gold,
        foregroundColor: AppColors.voidBg,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.paper3,
        contentTextStyle: const TextStyle(color: AppColors.ink),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      iconTheme: const IconThemeData(color: AppColors.steel),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.gold),
    );
  }
}

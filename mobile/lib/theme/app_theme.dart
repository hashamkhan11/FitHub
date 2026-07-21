import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// FitHub's palette — an ink/chalk/gold "scoreboard" identity rather than a
/// generic Material blue. The app commits fully to the dark ink ground; there
/// is no light variant.
class AppColors {
  AppColors._();

  static const ink = Color(0xFF101826);
  static const ink2 = Color(0xFF182236);
  static const inkLine = Color(0xFF2A3448);
  static const chalk = Color(0xFFF4F5F1);
  static const steel = Color(0xFF5B6472);
  static const steel2 = Color(0xFF8891A0);
  static const gold = Color(0xFFD9A441);
  static const gold2 = Color(0xFFB9862E);
  static const turf = Color(0xFF2F5D50);
  static const tape = Color(0xFFB23A2E);
}

class AppTheme {
  AppTheme._();

  static TextStyle mono({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.w500,
    Color color = AppColors.chalk,
  }) {
    return GoogleFonts.ibmPlexMono(fontSize: fontSize, fontWeight: fontWeight, color: color);
  }

  static TextStyle display({
    double fontSize = 16,
    FontWeight fontWeight = FontWeight.w600,
    Color color = AppColors.chalk,
    double letterSpacing = 0.4,
  }) {
    return GoogleFonts.oswald(
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
    );
  }

  static ThemeData get theme {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: AppColors.ink,
      colorScheme: const ColorScheme.dark(
        primary: AppColors.gold,
        onPrimary: AppColors.ink,
        secondary: AppColors.turf,
        onSecondary: AppColors.chalk,
        surface: AppColors.ink2,
        onSurface: AppColors.chalk,
        error: AppColors.tape,
        onError: AppColors.chalk,
      ),
    );

    final workSansTheme = GoogleFonts.workSansTextTheme(base.textTheme).apply(
      bodyColor: AppColors.chalk,
      displayColor: AppColors.chalk,
    );

    return base.copyWith(
      textTheme: workSansTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.ink,
        foregroundColor: AppColors.chalk,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: display(fontSize: 20, fontWeight: FontWeight.w600),
        shape: const Border(bottom: BorderSide(color: AppColors.gold, width: 3)),
      ),
      cardTheme: CardThemeData(
        color: AppColors.ink2,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
          side: const BorderSide(color: AppColors.inkLine),
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.inkLine, space: 1),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.ink2,
        labelStyle: const TextStyle(color: AppColors.steel2),
        floatingLabelStyle: const TextStyle(color: AppColors.gold),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(3),
          borderSide: const BorderSide(color: AppColors.inkLine),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(3),
          borderSide: const BorderSide(color: AppColors.inkLine),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(3),
          borderSide: const BorderSide(color: AppColors.gold, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.gold,
          foregroundColor: AppColors.ink,
          minimumSize: const Size.fromHeight(48),
          textStyle: display(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.ink, letterSpacing: 1),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(3)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.chalk,
          side: const BorderSide(color: AppColors.steel2),
          minimumSize: const Size.fromHeight(48),
          textStyle: display(fontSize: 14, fontWeight: FontWeight.w700, letterSpacing: 1),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(3)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.gold,
          textStyle: display(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.gold, letterSpacing: 1),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.gold,
        foregroundColor: AppColors.ink,
      ),
      snackBarTheme: const SnackBarThemeData(
        backgroundColor: AppColors.ink2,
        contentTextStyle: TextStyle(color: AppColors.chalk),
      ),
      iconTheme: const IconThemeData(color: AppColors.steel2),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.gold),
    );
  }
}

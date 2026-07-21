import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Shows the real logo once `assets/images/branding/logo.png` is dropped in;
/// falls back to the gold "FH" monogram until then.
class LogoBadge extends StatelessWidget {
  const LogoBadge({super.key, this.size = 56, this.fontSize = 20});

  final double size;
  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/images/branding/logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
      errorBuilder: (context, error, stackTrace) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(6),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.gold, AppColors.gold2],
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          'FH',
          style: AppTheme.display(fontSize: fontSize, fontWeight: FontWeight.w800, color: AppColors.ink),
        ),
      ),
    );
  }
}

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Shared "Pulse" decoration widgets — the status stamp and streak dots that
/// recur across cards, restyled from the old "Locker Room Zine" identity as
/// flat hairline-bordered chips instead of torn paper / tape / rubber stamps.

enum StampVariant { neutral, gold, blue, good, bad, warn }

/// A soft-tint status chip — used for status/booking labels.
class StampBadge extends StatelessWidget {
  const StampBadge({
    super.key,
    required this.label,
    this.variant = StampVariant.neutral,
    this.angle = 0,
  });

  final String label;
  final StampVariant variant;
  final double angle;

  ({Color fg, Color bg}) get _colors {
    switch (variant) {
      case StampVariant.gold:
        return (fg: AppColors.gold, bg: AppColors.gold.withValues(alpha: 0.14));
      case StampVariant.blue:
        return (fg: AppColors.blue, bg: AppColors.blue.withValues(alpha: 0.14));
      case StampVariant.good:
        return (fg: AppColors.turf, bg: AppColors.turf.withValues(alpha: 0.12));
      case StampVariant.bad:
        return (fg: AppColors.tape, bg: AppColors.tape.withValues(alpha: 0.12));
      case StampVariant.warn:
        return (fg: AppColors.warn, bg: AppColors.warn.withValues(alpha: 0.14));
      case StampVariant.neutral:
        return (fg: AppColors.steel, bg: AppColors.steel.withValues(alpha: 0.1));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = _colors;
    final chip = Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: colors.bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label.toUpperCase(),
        style: AppTheme.mono(fontSize: 11, fontWeight: FontWeight.w600, color: colors.fg, letterSpacing: 0.6),
      ),
    );

    return angle == 0 ? chip : Transform.rotate(angle: angle, child: chip);
  }
}

/// A single punch-card day marker for the check-in streak widget: a filled
/// accent dot for a hit day, a hairline-outlined dot for a miss.
class PunchDot extends StatelessWidget {
  const PunchDot({super.key, required this.filled, this.size = 22});

  final bool filled;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: filled ? AppColors.gold : Colors.transparent,
        border: Border.all(color: filled ? AppColors.gold : AppColors.ink2, width: 1.5),
        boxShadow: filled
            ? [BoxShadow(color: AppColors.gold.withValues(alpha: 0.5), blurRadius: 6)]
            : null,
      ),
      child: filled
          ? const Icon(Icons.check, size: 13, color: AppColors.voidBg)
          : null,
    );
  }
}

/// The dark gradient "membership ID card" surface reused on the home and
/// profile screens — a glowing accent disc in the corner, light text.
class MemCard extends StatelessWidget {
  const MemCard({super.key, required this.child, this.padding});

  final Widget child;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: padding ?? const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.paper2, AppColors.voidBg],
        ),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.ink2, width: 1),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -30,
            top: -30,
            child: Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [AppColors.gold.withValues(alpha: 0.25), Colors.transparent],
                ),
              ),
            ),
          ),
          child,
        ],
      ),
    );
  }
}

/// A staggered fade/slide-up entrance for top-level sections, skipped
/// entirely when the OS has "reduce motion" enabled.
class Reveal extends StatelessWidget {
  const Reveal({super.key, this.index = 0, required this.child});

  final int index;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.of(context).disableAnimations) return child;

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 350 + index * 80),
      curve: Curves.easeOutCubic,
      builder: (context, value, child) {
        return Opacity(
          opacity: value,
          child: Transform.translate(offset: Offset(0, (1 - value) * 16), child: child),
        );
      },
      child: child,
    );
  }
}

/// A circular member photo with a gold hairline ring, falling back to a
/// first-initial monogram when no photo has been uploaded yet. Shared between
/// the editable avatar on Profile and the read-only one on Home.
class Avatar extends StatelessWidget {
  const Avatar({
    super.key,
    required this.photoUrl,
    required this.name,
    required this.authToken,
    this.size = 44,
    this.borderWidth = 2,
    this.overlay = const [],
  });

  final String? photoUrl;
  final String name;
  final String authToken;
  final double size;
  final double borderWidth;

  /// Extra `Positioned` children (e.g. a camera badge, an upload spinner)
  /// stacked directly on top of the circle by the caller.
  final List<Widget> overlay;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: AppColors.gold, width: borderWidth),
            color: AppColors.ink2,
          ),
          clipBehavior: Clip.antiAlias,
          child: photoUrl != null
              ? CachedNetworkImage(
                  imageUrl: photoUrl!,
                  httpHeaders: {'Authorization': 'Bearer $authToken'},
                  fit: BoxFit.cover,
                )
              : Center(
                  child: Text(
                    name.isNotEmpty ? name[0].toUpperCase() : '?',
                    style: AppTheme.display(fontSize: size * 0.37, color: AppColors.ink),
                  ),
                ),
        ),
        ...overlay,
      ],
    );
  }
}

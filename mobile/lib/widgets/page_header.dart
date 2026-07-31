import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// The large-title header used at the top of every screen's body except
/// Home (which keeps its own bespoke greeting header). Lives inline in the
/// scrollable body/`SafeArea`, matching Home's structure, instead of a fixed
/// `Scaffold.appBar` — this is what keeps every screen visually consistent
/// with Home's borderless, in-body layout.
class PageHeader extends StatelessWidget {
  const PageHeader({
    super.key,
    required this.title,
    this.trailing,
    this.showBackButton = true,
    this.leading,
    this.fontSize = 28,
  });

  final String title;
  final Widget? trailing;
  final bool showBackButton;

  /// Overrides the default back-chevron leading element — used by tab-root
  /// screens that want Home's avatar treatment instead of back navigation.
  final Widget? leading;

  final double fontSize;

  @override
  Widget build(BuildContext context) {
    final canPop = showBackButton && Navigator.of(context).canPop();
    final resolvedLeading = leading ??
        (canPop
            ? _HeaderIconButton(
                icon: Icons.arrow_back_ios_new_rounded,
                onTap: () => Navigator.of(context).pop(),
              )
            : null);

    return Row(
      children: [
        if (resolvedLeading != null) ...[
          resolvedLeading,
          const SizedBox(width: 14),
        ],
        Expanded(
          child: Text(
            title,
            style: AppTheme.display(fontSize: fontSize, fontWeight: FontWeight.w800),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
        if (trailing != null) ...[
          const SizedBox(width: 8),
          trailing!,
        ],
      ],
    );
  }
}

/// A 44x44 hairline-ringed circular icon button, matching Home's
/// notification bell treatment — used here for the back button and
/// available to callers for their own `trailing` actions.
class HeaderIconButton extends StatelessWidget {
  const HeaderIconButton({super.key, required this.icon, required this.onTap, this.tooltip});

  final IconData icon;
  final VoidCallback onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    return _HeaderIconButton(icon: icon, onTap: onTap, tooltip: tooltip);
  }
}

class _HeaderIconButton extends StatelessWidget {
  const _HeaderIconButton({required this.icon, required this.onTap, this.tooltip});

  final IconData icon;
  final VoidCallback onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final button = Material(
      color: Colors.transparent,
      shape: const CircleBorder(side: BorderSide(color: AppColors.ink2, width: 1)),
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, color: AppColors.ink, size: 20),
        ),
      ),
    );

    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}

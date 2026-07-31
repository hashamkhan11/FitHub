import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/navigation_provider.dart';
import '../theme/app_theme.dart';
import 'attendance_screen.dart';
import 'bmi_screen.dart';
import 'classes_screen.dart';
import 'home_screen.dart';
import 'progress_screen.dart';
import 'settings_screen.dart';

class MainShell extends ConsumerStatefulWidget {
  const MainShell({super.key});

  // Index 4 was the Lock tab; Lock now lives as a Home quick-action, and
  // navigation_provider.dart's notificationTypeTabIndex only targets 0/1/3
  // so this reindex needs no changes there.
  static const _tabs = [
    HomeScreen(),
    ClassesScreen(),
    AttendanceScreen(),
    ProgressScreen(),
    BmiScreen(),
    SettingsScreen(),
  ];

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  // Each tab's FutureProvider fires its network request the moment the tab
  // widget is first built. IndexedStack builds every child regardless of
  // which index is showing, so without this tracking all 6 tabs' providers
  // would fire simultaneously on launch instead of just the visible one.
  final _visited = {0};

  @override
  Widget build(BuildContext context) {
    final index = ref.watch(selectedTabProvider);
    _visited.add(index);

    return Scaffold(
      body: IndexedStack(
        index: index,
        children: [
          for (var i = 0; i < MainShell._tabs.length; i++)
            _visited.contains(i) ? MainShell._tabs[i] : const SizedBox.shrink(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (index) => ref.read(selectedTabProvider.notifier).select(index),
        backgroundColor: AppColors.voidBg,
        indicatorColor: AppColors.gold.withValues(alpha: 0.16),
        surfaceTintColor: Colors.transparent,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return AppTheme.display(
            fontSize: 10,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.5,
            color: selected ? AppColors.gold : AppColors.steel,
          );
        }),
        destinations: [
          NavigationDestination(icon: Icon(Icons.home_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.home, color: AppColors.gold), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.event_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.event, color: AppColors.gold), label: 'Classes'),
          NavigationDestination(icon: Icon(Icons.qr_code_scanner_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.qr_code_scanner, color: AppColors.gold), label: 'Attendance'),
          NavigationDestination(icon: Icon(Icons.show_chart_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.show_chart, color: AppColors.gold), label: 'Progress'),
          NavigationDestination(icon: Icon(Icons.monitor_weight_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.monitor_weight, color: AppColors.gold), label: 'BMI'),
          NavigationDestination(icon: Icon(Icons.settings_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.settings, color: AppColors.gold), label: 'Settings'),
        ],
      ),
    );
  }
}

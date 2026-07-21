import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/logout_action.dart';

final attendanceProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchAttendance();
});

class AttendanceScreen extends ConsumerWidget {
  const AttendanceScreen({super.key});

  static const _months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];

  String _formatDateTime(DateTime dt) {
    final hour = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    final minute = dt.minute.toString().padLeft(2, '0');
    return '${_months[dt.month - 1]} ${dt.day}, $hour:$minute $period';
  }

  String _formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60);
    if (hours > 0) {
      return '${hours}h ${minutes}m';
    }
    return '${minutes}m';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final attendanceAsync = ref.watch(attendanceProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('ATTENDANCE'), actions: const [LogoutAction()]),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(attendanceProvider),
        color: AppColors.gold,
        backgroundColor: AppColors.ink2,
        child: attendanceAsync.when(
          data: (attendance) {
            if (attendance.isEmpty) {
              return ListView(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text('No check-ins yet.', style: AppTheme.mono(color: AppColors.steel2)),
                  ),
                ],
              );
            }

            final entries = attendance.cast<Map<String, dynamic>>();

            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: entries.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final entry = entries[index];
                final checkedInAt = DateTime.parse(entry['checked_in_at'] as String).toLocal();
                final checkedOutRaw = entry['checked_out_at'] as String?;
                final checkedOutAt = checkedOutRaw == null ? null : DateTime.parse(checkedOutRaw).toLocal();

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              _formatDateTime(checkedInAt),
                              style: AppTheme.display(fontSize: 15, fontWeight: FontWeight.w600),
                            ),
                            if (checkedOutAt == null) _chip('AT THE GYM', AppColors.turf),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _row('Checked in', _timeOnly(checkedInAt)),
                        _row('Checked out', checkedOutAt == null ? '—' : _timeOnly(checkedOutAt)),
                        if (checkedOutAt != null)
                          _row('Duration', _formatDuration(checkedOutAt.difference(checkedInAt))),
                      ],
                    ),
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(child: Text('Could not load attendance: $err', style: const TextStyle(color: AppColors.tape))),
        ),
      ),
    );
  }

  String _timeOnly(DateTime dt) {
    final hour = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    final minute = dt.minute.toString().padLeft(2, '0');
    return '$hour:$minute $period';
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          SizedBox(width: 100, child: Text(label, style: const TextStyle(color: AppColors.steel2, fontSize: 13))),
          Text(value, style: AppTheme.mono(fontSize: 13)),
        ],
      ),
    );
  }

  Widget _chip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: AppTheme.display(fontSize: 10, fontWeight: FontWeight.w600, color: color, letterSpacing: 0.5),
      ),
    );
  }
}

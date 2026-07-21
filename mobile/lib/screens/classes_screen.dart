import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/logout_action.dart';

final classesProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchClasses();
});

class ClassesScreen extends ConsumerWidget {
  const ClassesScreen({super.key});

  String _formatDateTime(DateTime dt) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    final hour = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    final minute = dt.minute.toString().padLeft(2, '0');
    return '${months[dt.month - 1]} ${dt.day}, $hour:$minute $period';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final classesAsync = ref.watch(classesProvider);
    final client = ref.watch(apiClientProvider);

    Future<void> handleAction(Future<void> Function() action) async {
      try {
        await action();
        ref.invalidate(classesProvider);
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
          );
        }
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('CLASSES'), actions: const [LogoutAction()]),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(classesProvider),
        color: AppColors.gold,
        backgroundColor: AppColors.ink2,
        child: classesAsync.when(
          data: (classes) {
            if (classes.isEmpty) {
              return ListView(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text('No upcoming classes.', style: AppTheme.mono(color: AppColors.steel2)),
                  ),
                ],
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: classes.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final gymClass = classes[index] as Map<String, dynamic>;
                final startTime = DateTime.parse(gymClass['start_time'] as String).toLocal();
                final myStatus = gymClass['my_status'] as String?;
                final myBookingId = gymClass['my_booking_id'] as int?;
                final bookedCount = gymClass['booked_count'] as int;
                final capacity = gymClass['capacity'] as int;

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                gymClass['name'] as String,
                                style: AppTheme.display(fontSize: 17, fontWeight: FontWeight.w600),
                              ),
                            ),
                            _statusChip(myStatus),
                          ],
                        ),
                        const SizedBox(height: 6),
                        if (gymClass['instructor_name'] != null)
                          Text(
                            gymClass['instructor_name'] as String,
                            style: const TextStyle(color: AppColors.steel2),
                          ),
                        const SizedBox(height: 4),
                        Text(_formatDateTime(startTime), style: AppTheme.mono(fontSize: 13, color: AppColors.steel2)),
                        Text('$bookedCount/$capacity booked', style: AppTheme.mono(fontSize: 13, color: AppColors.steel2)),
                        const SizedBox(height: 14),
                        if (myStatus == 'booked')
                          OutlinedButton(
                            onPressed: () => handleAction(() => client.cancelBooking(myBookingId!)),
                            child: const Text('CANCEL BOOKING'),
                          )
                        else if (myStatus == 'waitlisted')
                          OutlinedButton(
                            onPressed: () => handleAction(() => client.cancelBooking(myBookingId!)),
                            child: const Text('LEAVE WAITLIST'),
                          )
                        else
                          FilledButton(
                            onPressed: () => handleAction(() => client.bookClass(gymClass['id'] as int)),
                            child: const Text('BOOK'),
                          ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(child: Text('Could not load classes: $err', style: const TextStyle(color: AppColors.tape))),
        ),
      ),
    );
  }

  Widget _statusChip(String? status) {
    if (status == 'booked') {
      return _chip('BOOKED', AppColors.turf);
    }
    if (status == 'waitlisted') {
      return _chip('WAITLISTED', AppColors.gold);
    }
    return const SizedBox.shrink();
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

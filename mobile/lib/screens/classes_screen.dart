import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';

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
      appBar: AppBar(title: const Text('Classes')),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(classesProvider),
        child: classesAsync.when(
          data: (classes) {
            if (classes.isEmpty) {
              return ListView(
                children: const [
                  Padding(
                    padding: EdgeInsets.all(24),
                    child: Text('No upcoming classes.'),
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
                        Text(
                          gymClass['name'] as String,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 4),
                        if (gymClass['instructor_name'] != null)
                          Text('Instructor: ${gymClass['instructor_name']}'),
                        Text(_formatDateTime(startTime)),
                        Text('$bookedCount/$capacity booked'),
                        const SizedBox(height: 12),
                        if (myStatus == 'booked')
                          FilledButton.tonal(
                            onPressed: () => handleAction(() => client.cancelBooking(myBookingId!)),
                            child: const Text('Cancel booking'),
                          )
                        else if (myStatus == 'waitlisted')
                          OutlinedButton(
                            onPressed: () => handleAction(() => client.cancelBooking(myBookingId!)),
                            child: const Text('Leave waitlist'),
                          )
                        else
                          FilledButton(
                            onPressed: () => handleAction(() => client.bookClass(gymClass['id'] as int)),
                            child: const Text('Book'),
                          ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(child: Text('Could not load classes: $err')),
        ),
      ),
    );
  }
}

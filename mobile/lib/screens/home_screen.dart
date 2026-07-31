import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/zine.dart';
import 'attendance_screen.dart' show attendanceProvider;
import 'classes_screen.dart' show classesProvider;
import 'lock_screen.dart' show LockScreen, lockDevicesProvider;
import 'profile_screen.dart' show memberProfileProvider;

final membershipProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchMembership();
});

// Derived from the same classesProvider/attendanceProvider the Classes and
// Attendance tabs use, rather than fetching /classes and /member/attendance
// again — those tabs share this data instead of double-hitting the API.
final nextClassProvider = FutureProvider.autoDispose((ref) async {
  final classes = await ref.watch(classesProvider.future);

  for (final entry in classes) {
    final gymClass = entry as Map<String, dynamic>;
    if (gymClass['my_status'] == 'booked') {
      return gymClass;
    }
  }

  return null;
});

final attendanceStreakProvider = FutureProvider.autoDispose((ref) async {
  return _computeStreak(await ref.watch(attendanceProvider.future));
});

/// Consecutive calendar days (ending today or yesterday) with at least one
/// check-in. Anchoring on yesterday too means the streak doesn't drop to
/// zero first thing in the morning before today's check-in happens.
int _computeStreak(List<dynamic> attendance) {
  final days = attendance
      .map((a) => DateTime.parse((a as Map<String, dynamic>)['checked_in_at'] as String).toLocal())
      .map((dt) => DateTime(dt.year, dt.month, dt.day))
      .toSet();

  if (days.isEmpty) return 0;

  var cursor = DateTime.now();
  cursor = DateTime(cursor.year, cursor.month, cursor.day);

  if (!days.contains(cursor)) {
    cursor = cursor.subtract(const Duration(days: 1));
    if (!days.contains(cursor)) return 0;
  }

  var streak = 0;
  while (days.contains(cursor)) {
    streak++;
    cursor = cursor.subtract(const Duration(days: 1));
  }

  return streak;
}

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membershipAsync = ref.watch(membershipProvider);
    final profileAsync = ref.watch(memberProfileProvider);
    final client = ref.watch(apiClientProvider);

    final member = profileAsync.asData?.value;
    final fullName = (member?['name'] as String?)?.trim() ?? '';
    final firstName = fullName.isEmpty ? '' : fullName.split(RegExp(r'\s+')).first;
    final photoUrl = member?['photo_url'] as String?;

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(membershipProvider);
            ref.invalidate(classesProvider);
            ref.invalidate(attendanceProvider);
            ref.invalidate(memberProfileProvider);
            ref.invalidate(lockDevicesProvider);
          },
          color: AppColors.gold,
          backgroundColor: AppColors.paper2,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
            children: [
              Reveal(
                index: 0,
                child: Row(
                  children: [
                    Avatar(
                      photoUrl: photoUrl,
                      name: firstName,
                      authToken: client.authToken,
                      size: 60,
                      borderWidth: 2.5,
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'WELCOME BACK',
                            style: AppTheme.mono(fontSize: 12, color: AppColors.steel2, letterSpacing: 1.6),
                          ),
                          const SizedBox(height: 3),
                          Text(
                            firstName.isEmpty ? 'there' : firstName,
                            style: AppTheme.display(fontSize: 32, fontWeight: FontWeight.w800),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    _NotificationBell(
                      onTap: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text("You're all caught up — no new notifications.")),
                        );
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Reveal(
                index: 1,
                child: Column(
                  children: [
                    Text(
                      'SCAN TO CHECK IN',
                      textAlign: TextAlign.center,
                      style: AppTheme.display(fontSize: 12, color: AppColors.steel, letterSpacing: 2),
                    ),
                    const SizedBox(height: 16),
                    Center(
                      child: Container(
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          color: AppColors.paper2,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.ink2, width: 1),
                          boxShadow: [
                            BoxShadow(color: AppColors.gold.withValues(alpha: 0.16), blurRadius: 24, spreadRadius: 2),
                          ],
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              height: 3,
                              decoration: BoxDecoration(
                                color: AppColors.gold,
                                boxShadow: [BoxShadow(color: AppColors.gold.withValues(alpha: 0.6), blurRadius: 8)],
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                              child: Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8)),
                                child: SvgPicture.network(
                                  client.qrCodeUrl().toString(),
                                  headers: {'Authorization': 'Bearer ${client.authToken}'},
                                  width: 220,
                                  height: 220,
                                  placeholderBuilder: (context) => const SizedBox(
                                    width: 220,
                                    height: 220,
                                    child: Center(child: CircularProgressIndicator()),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 28),
              Reveal(index: 2, child: const _LockQuickAction()),
              const SizedBox(height: 16),
              Reveal(
                index: 3,
                child: MemCard(
                  child: membershipAsync.when(
                    data: (data) {
                      final membership = data['membership'] as Map<String, dynamic>?;
                      if (membership == null) {
                        return Text('No active membership.', style: AppTheme.mono(color: AppColors.steel2));
                      }
                      final trainer = data['trainer'] as Map<String, dynamic>?;
                      final plan = membership['plan'] as Map<String, dynamic>;
                      final status = membership['payment_status'] as String;
                      final paymentColor = switch (status) {
                        'paid' => AppColors.turf,
                        'partial' => AppColors.blue,
                        _ => AppColors.tape,
                      };
                      final balanceDue = double.tryParse(membership['balance_due'].toString()) ?? 0;
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            (plan['name'] as String).toUpperCase(),
                            style: AppTheme.display(fontSize: 20, fontWeight: FontWeight.w600, color: AppColors.gold),
                          ),
                          const SizedBox(height: 12),
                          _badgeRow(
                            'Ends',
                            membership['end_date'].toString().split('T').first,
                          ),
                          const SizedBox(height: 6),
                          _badgeRow(
                            'Payment',
                            status.toUpperCase(),
                            valueColor: paymentColor,
                          ),
                          if (status != 'paid') ...[
                            const SizedBox(height: 6),
                            _badgeRow('Balance due', balanceDue.toStringAsFixed(2), valueColor: paymentColor),
                          ],
                          if (trainer != null) ...[
                            const SizedBox(height: 6),
                            _badgeRow('Trainer', trainer['name'] as String),
                          ],
                        ],
                      );
                    },
                    loading: () => const Center(child: CircularProgressIndicator()),
                    error: (err, _) => Text('Could not load membership: $err', style: const TextStyle(color: AppColors.tape)),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Reveal(
                index: 4,
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(child: _nextClassCard(ref)),
                    const SizedBox(width: 12),
                    Expanded(child: _streakCard(ref)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _badgeRow(String label, String value, {Color valueColor = AppColors.ink}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: AppTheme.mono(fontSize: 12, color: AppColors.steel2)),
        Text(value, style: AppTheme.mono(fontSize: 12, fontWeight: FontWeight.w600, color: valueColor)),
      ],
    );
  }

  String _formatClassDateTime(DateTime dt) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    final hour = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    final minute = dt.minute.toString().padLeft(2, '0');
    return '${months[dt.month - 1]} ${dt.day}, $hour:$minute $period';
  }

  Widget _statCardShell(String label, Widget content, {Color labelColor = AppColors.steel}) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: AppTheme.display(fontSize: 11, color: labelColor, letterSpacing: 1.2)),
            const SizedBox(height: 8),
            content,
          ],
        ),
      ),
    );
  }

  Widget _nextClassCard(WidgetRef ref) {
    final nextClassAsync = ref.watch(nextClassProvider);

    return nextClassAsync.when(
      data: (gymClass) {
        if (gymClass == null) {
          return _statCardShell(
            'NEXT CLASS',
            Text('No upcoming bookings', style: AppTheme.mono(fontSize: 13, color: AppColors.steel2)),
          );
        }
        final startTime = DateTime.parse(gymClass['start_time'] as String).toLocal();
        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.gold,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('NEXT CLASS', style: AppTheme.display(fontSize: 11, color: AppColors.voidBg, letterSpacing: 1.2)),
              const SizedBox(height: 8),
              Text(
                gymClass['name'] as String,
                style: AppTheme.display(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.voidBg),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 4),
              Text(_formatClassDateTime(startTime), style: AppTheme.mono(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.voidBg)),
            ],
          ),
        );
      },
      loading: () => _statCardShell('NEXT CLASS', const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
      error: (_, _) => _statCardShell('NEXT CLASS', Text('—', style: AppTheme.mono(color: AppColors.steel2))),
    );
  }

  Widget _streakCard(WidgetRef ref) {
    final streakAsync = ref.watch(attendanceStreakProvider);

    return _statCardShell(
      'STREAK',
      streakAsync.when(
        data: (streak) => Row(
          crossAxisAlignment: CrossAxisAlignment.baseline,
          textBaseline: TextBaseline.alphabetic,
          children: [
            Text(
              '$streak',
              style: AppTheme.display(
                fontSize: 26,
                fontWeight: FontWeight.w700,
                color: streak > 0 ? AppColors.blueDeep : AppColors.steel2,
              ),
            ),
            const SizedBox(width: 6),
            Text(streak == 1 ? 'day' : 'days', style: AppTheme.mono(fontSize: 13, color: AppColors.steel2)),
            const Spacer(),
            PunchDot(filled: streak > 0),
          ],
        ),
        loading: () => const SizedBox(
          height: 20,
          width: 20,
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
        error: (_, _) => Text('—', style: AppTheme.mono(color: AppColors.steel2)),
      ),
    );
  }
}

/// The Home header's notification affordance — a hairline-bordered circular
/// icon button, matching the [Avatar]/[LogoBadge] ring treatment so it reads
/// as part of the same header group rather than a bare Material icon button.
class _NotificationBell extends StatelessWidget {
  const _NotificationBell({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      shape: const CircleBorder(side: BorderSide(color: AppColors.ink2, width: 1)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: const SizedBox(
          width: 50,
          height: 50,
          child: Icon(Icons.notifications_outlined, color: AppColors.ink, size: 23),
        ),
      ),
    );
  }
}

/// A single unlock action for the member's primary lock device, plus a link
/// to the full [LockScreen] when a gym has more than one. Renders nothing
/// when the gym has no lock devices set up.
class _LockQuickAction extends ConsumerStatefulWidget {
  const _LockQuickAction();

  @override
  ConsumerState<_LockQuickAction> createState() => _LockQuickActionState();
}

class _LockQuickActionState extends ConsumerState<_LockQuickAction> {
  bool _pending = false;

  @override
  Widget build(BuildContext context) {
    final devicesAsync = ref.watch(lockDevicesProvider);
    final client = ref.watch(apiClientProvider);

    return devicesAsync.when(
      data: (devices) {
        if (devices.isEmpty) return const SizedBox.shrink();

        final primary = devices.first as Map<String, dynamic>;
        final id = primary['id'] as int;
        final name = primary['name'] as String;
        final isOnline = primary['is_online'] as bool;

        Future<void> unlock() async {
          setState(() => _pending = true);
          try {
            final commandId = await client.unlockDevice(id);

            // The ESP32 only polls for new commands every second, so give it
            // room to pick this one up rather than guessing with a fixed delay.
            var status = 'pending';
            for (var attempt = 0; attempt < 30 && status == 'pending'; attempt++) {
              await Future.delayed(const Duration(milliseconds: 500));
              status = await client.fetchCommandStatus(commandId);
            }

            if (!context.mounted) return;
            final message = switch (status) {
              'completed' => '$name is now unlocked.',
              'failed' => 'Unlock failed on $name.',
              'expired' => 'Unlock request to $name timed out — the device may be offline.',
              _ => 'Unlock request sent to $name — still waiting for it to respond.',
            };
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
            ref.invalidate(lockDevicesProvider);
          } catch (e) {
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
              );
            }
          } finally {
            if (mounted) setState(() => _pending = false);
          }
        }

        return MemCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('DOOR LOCK', style: AppTheme.display(fontSize: 11, color: AppColors.steel2, letterSpacing: 1.5)),
                        const SizedBox(height: 6),
                        Text(
                          name,
                          style: AppTheme.display(fontSize: 16, fontWeight: FontWeight.w600),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          isOnline ? 'Online' : 'Offline',
                          style: AppTheme.mono(fontSize: 12, color: isOnline ? AppColors.turf : AppColors.steel2),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.gold,
                        foregroundColor: AppColors.voidBg,
                      ),
                      onPressed: _pending ? null : unlock,
                      child: _pending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.voidBg),
                            )
                          : const Text('UNLOCK', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
              if (devices.length > 1) ...[
                const SizedBox(height: 10),
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const LockScreen()),
                    ),
                    child: Text(
                      'MANAGE ALL (${devices.length}) →',
                      style: AppTheme.mono(fontSize: 11, color: AppColors.gold, fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
              ],
            ],
          ),
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (_, _) => const SizedBox.shrink(),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/logout_action.dart';

final lockDevicesProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchLockDevices();
});

class LockScreen extends ConsumerWidget {
  const LockScreen({super.key});

  String _lastSeenLabel(String? lastSeenAtRaw) {
    if (lastSeenAtRaw == null) return 'Never seen';

    final lastSeenAt = DateTime.parse(lastSeenAtRaw).toLocal();
    final diff = DateTime.now().difference(lastSeenAt);

    if (diff.inSeconds < 60) return 'Seen just now';
    if (diff.inMinutes < 60) return 'Seen ${diff.inMinutes}m ago';
    if (diff.inHours < 24) return 'Seen ${diff.inHours}h ago';
    return 'Seen ${diff.inDays}d ago';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final devicesAsync = ref.watch(lockDevicesProvider);
    final client = ref.watch(apiClientProvider);

    Future<void> handleUnlock(int deviceId, String name) async {
      try {
        await client.unlockDevice(deviceId);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Unlock request sent to $name.')),
          );
        }
        // give the device a few seconds to poll and pick up the command
        // before we refresh its "last seen" status
        await Future.delayed(const Duration(seconds: 4));
        ref.invalidate(lockDevicesProvider);
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
          );
        }
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('LOCK'), actions: const [LogoutAction()]),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(lockDevicesProvider),
        color: AppColors.gold,
        backgroundColor: AppColors.ink2,
        child: devicesAsync.when(
          data: (devices) {
            if (devices.isEmpty) {
              return ListView(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      'No lock devices set up for your gym yet.',
                      style: AppTheme.mono(color: AppColors.steel2),
                    ),
                  ),
                ],
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: devices.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final device = devices[index] as Map<String, dynamic>;
                final isOnline = device['is_online'] as bool;

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Icon(Icons.lock_outline, size: 36, color: AppColors.gold),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                device['name'] as String,
                                style: AppTheme.display(fontSize: 17, fontWeight: FontWeight.w600),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Icon(
                                    Icons.circle,
                                    size: 8,
                                    color: isOnline ? AppColors.turf : AppColors.steel2,
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    isOnline ? 'Online' : 'Offline',
                                    style: AppTheme.mono(fontSize: 12, color: AppColors.steel2),
                                  ),
                                ],
                              ),
                              Text(
                                _lastSeenLabel(device['last_seen_at'] as String?),
                                style: AppTheme.mono(fontSize: 12, color: AppColors.steel2),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        FilledButton(
                          onPressed: () => handleUnlock(device['id'] as int, device['name'] as String),
                          child: const Text('UNLOCK'),
                        ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(
            child: Text('Could not load lock devices: $err', style: const TextStyle(color: AppColors.tape)),
          ),
        ),
      ),
    );
  }
}

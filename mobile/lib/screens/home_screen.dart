import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../providers/auth_provider.dart';
import 'classes_screen.dart';
import 'progress_screen.dart';

final membershipProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  final data = await client.fetchMembership();
  return data['membership'] as Map<String, dynamic>?;
});

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membershipAsync = ref.watch(membershipProvider);
    final client = ref.watch(apiClientProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My FitHub'),
        actions: [
          IconButton(
            icon: const Icon(Icons.event),
            tooltip: 'Classes',
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const ClassesScreen()),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.show_chart),
            tooltip: 'Progress',
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const ProgressScreen()),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(membershipProvider),
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: membershipAsync.when(
                  data: (membership) {
                    if (membership == null) {
                      return const Text('No active membership.');
                    }
                    final plan = membership['plan'] as Map<String, dynamic>;
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          plan['name'] as String,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Ends: ${membership['end_date'].toString().split('T').first}',
                        ),
                        Text('Payment: ${membership['payment_status']}'),
                      ],
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Text('Could not load membership: $err'),
                ),
              ),
            ),
            const SizedBox(height: 24),
            const Text('Show this at check-in', textAlign: TextAlign.center),
            const SizedBox(height: 12),
            Center(
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
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/logout_action.dart';

final membershipProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchMembership();
});

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membershipAsync = ref.watch(membershipProvider);
    final client = ref.watch(apiClientProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('MY FITHUB'),
        actions: const [LogoutAction()],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(membershipProvider),
        color: AppColors.gold,
        backgroundColor: AppColors.ink2,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.inkLine),
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [AppColors.ink2, AppColors.ink],
                ),
              ),
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
                    'partial' => AppColors.gold,
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
            const SizedBox(height: 32),
            Text(
              'SCAN TO CHECK IN',
              textAlign: TextAlign.center,
              style: AppTheme.display(fontSize: 12, color: AppColors.steel2, letterSpacing: 2),
            ),
            const SizedBox(height: 16),
            Center(
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                ),
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
    );
  }

  Widget _badgeRow(String label, String value, {Color valueColor = AppColors.chalk}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: AppTheme.mono(fontSize: 12, color: AppColors.steel2)),
        Text(value, style: AppTheme.mono(fontSize: 12, fontWeight: FontWeight.w600, color: valueColor)),
      ],
    );
  }
}

import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';

final measurementsProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchMeasurements();
});

class ProgressScreen extends ConsumerWidget {
  const ProgressScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final measurementsAsync = ref.watch(measurementsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Progress')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _openLogSheet(context, ref),
        child: const Icon(Icons.add),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(measurementsProvider),
        child: measurementsAsync.when(
          data: (measurements) {
            if (measurements.isEmpty) {
              return ListView(
                children: const [
                  Padding(
                    padding: EdgeInsets.all(24),
                    child: Text('No measurements logged yet. Tap + to add one.'),
                  ),
                ],
              );
            }

            final entries = measurements.cast<Map<String, dynamic>>();

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                SizedBox(
                  height: 220,
                  child: _WeightChart(entries: entries),
                ),
                const SizedBox(height: 24),
                for (final entry in entries.reversed) _MeasurementCard(entry: entry),
              ],
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(child: Text('Could not load progress: $err')),
        ),
      ),
    );
  }

  void _openLogSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => _LogMeasurementSheet(ref: ref),
    );
  }
}

class _WeightChart extends StatelessWidget {
  const _WeightChart({required this.entries});

  final List<Map<String, dynamic>> entries;

  @override
  Widget build(BuildContext context) {
    final spots = <FlSpot>[];
    for (var i = 0; i < entries.length; i++) {
      final weight = entries[i]['weight_kg'];
      if (weight != null) {
        spots.add(FlSpot(i.toDouble(), double.parse(weight.toString())));
      }
    }

    if (spots.isEmpty) {
      return const Center(child: Text('No weight data to chart yet.'));
    }

    return LineChart(
      LineChartData(
        titlesData: FlTitlesData(
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 32,
              getTitlesWidget: (value, meta) {
                final index = value.round();
                if (index < 0 || index >= entries.length) {
                  return const SizedBox.shrink();
                }
                final date = entries[index]['recorded_at'].toString().split('T').first;
                return Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(date.substring(5), style: const TextStyle(fontSize: 10)),
                );
              },
            ),
          ),
        ),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            color: Colors.blue,
            dotData: const FlDotData(show: true),
          ),
        ],
      ),
    );
  }
}

class _MeasurementCard extends StatelessWidget {
  const _MeasurementCard({required this.entry});

  final Map<String, dynamic> entry;

  @override
  Widget build(BuildContext context) {
    final date = entry['recorded_at'].toString().split('T').first;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(date, style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            if (entry['weight_kg'] != null) Text('Weight: ${entry['weight_kg']} kg'),
            if (entry['body_fat_percentage'] != null)
              Text('Body fat: ${entry['body_fat_percentage']}%'),
            if (entry['chest_cm'] != null) Text('Chest: ${entry['chest_cm']} cm'),
            if (entry['waist_cm'] != null) Text('Waist: ${entry['waist_cm']} cm'),
            if (entry['hips_cm'] != null) Text('Hips: ${entry['hips_cm']} cm'),
            if (entry['arms_cm'] != null) Text('Arms: ${entry['arms_cm']} cm'),
            if (entry['notes'] != null && (entry['notes'] as String).isNotEmpty)
              Text(entry['notes'] as String),
          ],
        ),
      ),
    );
  }
}

class _LogMeasurementSheet extends StatefulWidget {
  const _LogMeasurementSheet({required this.ref});

  final WidgetRef ref;

  @override
  State<_LogMeasurementSheet> createState() => _LogMeasurementSheetState();
}

class _LogMeasurementSheetState extends State<_LogMeasurementSheet> {
  final _weightController = TextEditingController();
  final _bodyFatController = TextEditingController();
  final _chestController = TextEditingController();
  final _waistController = TextEditingController();
  final _hipsController = TextEditingController();
  final _armsController = TextEditingController();
  final _notesController = TextEditingController();
  bool _saving = false;
  String? _error;

  double? _parse(String text) => text.trim().isEmpty ? null : double.tryParse(text.trim());

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final client = widget.ref.read(apiClientProvider);
      await client.logMeasurement(
        recordedAt: DateTime.now().toIso8601String().split('T').first,
        weightKg: _parse(_weightController.text),
        bodyFatPercentage: _parse(_bodyFatController.text),
        chestCm: _parse(_chestController.text),
        waistCm: _parse(_waistController.text),
        hipsCm: _parse(_hipsController.text),
        armsCm: _parse(_armsController.text),
        notes: _notesController.text,
      );
      widget.ref.invalidate(measurementsProvider);
      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _weightController.dispose();
    _bodyFatController.dispose();
    _chestController.dispose();
    _waistController.dispose();
    _hipsController.dispose();
    _armsController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('Log Measurement', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            TextField(
              controller: _weightController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Weight (kg)'),
            ),
            TextField(
              controller: _bodyFatController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Body fat (%)'),
            ),
            TextField(
              controller: _chestController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Chest (cm)'),
            ),
            TextField(
              controller: _waistController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Waist (cm)'),
            ),
            TextField(
              controller: _hipsController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Hips (cm)'),
            ),
            TextField(
              controller: _armsController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Arms (cm)'),
            ),
            TextField(
              controller: _notesController,
              decoration: const InputDecoration(labelText: 'Notes'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Save'),
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

/// Daily training (legacy army.php).
class ArmyScreen extends ConsumerStatefulWidget {
  const ArmyScreen({super.key});
  @override
  ConsumerState<ArmyScreen> createState() => _ArmyState();
}

class _ArmyState extends ConsumerState<ArmyScreen> {
  int _type = 1;
  Map<int, int> _foods = {};
  bool _busy = false;

  Future<void> _train() async {
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('army/train', {'type': _type, 'foods': _foods.map((k, v) => MapEntry('$k', v))});
      ref.read(sessionProvider.notifier).update(r);
      ref.invalidate(armyProvider);
      ref.invalidate(homeProvider);
      if (mounted) toast(context, 'Training complete!');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final army = ref.watch(armyProvider);
    return army.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(armyProvider)),
      data: (d) {
        final options = (d['options'] as List).cast<Map<String, dynamic>>();
        final trained = d['trainedToday'] == true;
        final report = d['report'] as Map<String, dynamic>?;
        final sel = options.firstWhere((o) => o['type'] == _type, orElse: () => options.first);
        return ListView(padding: const EdgeInsets.fromLTRB(14, 8, 14, 24), children: [
          GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const SectionTitle('Training ground', icon: Icons.fitness_center),
            if (trained) ...[
              const Text('You have trained today. Come back tomorrow, soldier!', style: TextStyle(color: EjColors.green, fontWeight: FontWeight.w600)),
              if (report != null) ...[const SizedBox(height: 12), _report(report)],
            ] else ...[
              const Text('Pick an intensity. Harder training burns more wellness but gains more skill.', style: TextStyle(color: EjColors.muted, fontSize: 12)),
              const SizedBox(height: 10),
              for (final o in options) _option(o),
              const SizedBox(height: 12),
              FoodPicker(foods: (d['foods'] as Map).cast<String, dynamic>(), selected: _foods, onChanged: (f) => setState(() => _foods = f), maxRecover: -(sel['wellness'] as num).toInt()),
              const SizedBox(height: 14),
              EjButton('Train now', icon: Icons.fitness_center, busy: _busy, onPressed: _train),
            ],
          ])),
        ]);
      },
    );
  }

  Widget _option(Map<String, dynamic> o) {
    final unlocked = o['unlocked'] == true;
    final selected = o['type'] == _type;
    return InkWell(
      onTap: unlocked ? () => setState(() => _type = o['type'] as int) : null,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          color: Colors.white.withValues(alpha: selected ? .1 : .04),
          border: Border.all(color: selected ? EjColors.accent2 : EjColors.line, width: selected ? 1.5 : 1),
        ),
        child: Row(children: [
          Icon(unlocked ? (selected ? Icons.radio_button_checked : Icons.radio_button_off) : Icons.lock, color: selected ? EjColors.accent2 : EjColors.muted),
          const SizedBox(width: 12),
          Expanded(child: Text(o['label'] as String, style: display(size: 16))),
          Chip2(value: '+${o['skillGain']}', label: 'SP', icon: Icons.trending_up),
          const SizedBox(width: 6),
          Chip2(value: '${o['wellness']}', label: 'wellness', icon: Icons.favorite),
        ]),
      ),
    );
  }

  Widget _report(Map<String, dynamic> r) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), color: Colors.black.withValues(alpha: .25), border: Border.all(color: EjColors.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Today\'s report', style: display(size: 14)),
          const SizedBox(height: 6),
          _kv('Skill points gained', '+${r['spGained']}'),
          _kv('Wellness', '${r['wellnessBefore']} → ${(r['wellnessBefore'] as num) - (r['wellnessLoss'] as num) + (r['wellnessRecovered'] as num)}'),
          _kv('Experience', '+${r['ep']} EP'),
          _kv('Reward', '${r['received']} P'),
        ]),
      );

  Widget _kv(String k, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 2), child: Row(children: [Expanded(child: Text(k, style: const TextStyle(color: EjColors.muted))), Text(v, style: const TextStyle(fontWeight: FontWeight.w700))]));
}

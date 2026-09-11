import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

/// Workplace (legacy company-{id}-workplace).
class WorkScreen extends ConsumerStatefulWidget {
  const WorkScreen({super.key});
  @override
  ConsumerState<WorkScreen> createState() => _WorkState();
}

class _WorkState extends ConsumerState<WorkScreen> {
  int _type = 1;
  Map<int, int> _foods = {};
  bool _busy = false;

  Future<void> _work() async {
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('work', {'type': _type, 'foods': _foods.map((k, v) => MapEntry('$k', v))});
      ref.read(sessionProvider.notifier).update(r);
      ref.invalidate(workProvider);
      ref.invalidate(homeProvider);
      if (mounted) toast(context, 'Shift finished — salary paid!');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final work = ref.watch(workProvider);
    return work.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(workProvider)),
      data: (d) {
        if (d['employed'] != true) {
          return const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('You are not employed. Find a job in the job market on the website.', textAlign: TextAlign.center, style: TextStyle(color: EjColors.muted))));
        }
        final comp = d['company'] as Map<String, dynamic>;
        final options = (d['options'] as List).cast<Map<String, dynamic>>();
        final worked = d['workedToday'] == true;
        final report = d['report'] as Map<String, dynamic>?;
        final sel = options.firstWhere((o) => o['type'] == _type, orElse: () => options.first);
        return ListView(padding: const EdgeInsets.fromLTRB(14, 8, 14, 24), children: [
          GlassCard(child: Row(children: [
            Avatar(comp['avatar'] as String, size: 48),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(comp['name'] as String, style: display(size: 18)),
              Text('${comp['industry']} · ${'★' * (comp['stars'] as int)}', style: const TextStyle(color: EjColors.muted, fontSize: 12)),
              Text('Salary ${d['salary']} ${d['salaryCurrency']}', style: const TextStyle(color: EjColors.gold, fontSize: 12, fontWeight: FontWeight.w600)),
            ])),
          ])),
          const SizedBox(height: 12),
          GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const SectionTitle('Workplace', icon: Icons.work),
            if (worked) ...[
              const Text('You have worked today. Your salary is on your account.', style: TextStyle(color: EjColors.green, fontWeight: FontWeight.w600)),
              if (report != null) ...[const SizedBox(height: 12), _report(report, d['salaryCurrency'] as String)],
            ] else ...[
              for (final o in options) _option(o, d['salaryCurrency'] as String),
              const SizedBox(height: 12),
              FoodPicker(foods: (d['foods'] as Map).cast<String, dynamic>(), selected: _foods, onChanged: (f) => setState(() => _foods = f), maxRecover: -(sel['wellness'] as num).toInt()),
              const SizedBox(height: 14),
              EjButton('Start working', icon: Icons.construction, busy: _busy, onPressed: _work),
            ],
          ])),
        ]);
      },
    );
  }

  Widget _option(Map<String, dynamic> o, String cur) {
    final selected = o['type'] == _type;
    return InkWell(
      onTap: () => setState(() => _type = o['type'] as int),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), color: Colors.white.withValues(alpha: selected ? .1 : .04), border: Border.all(color: selected ? EjColors.accent2 : EjColors.line, width: selected ? 1.5 : 1)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, color: selected ? EjColors.accent2 : EjColors.muted),
            const SizedBox(width: 12),
            Text(o['label'] as String, style: display(size: 16)),
          ]),
          const SizedBox(height: 8),
          Wrap(spacing: 6, runSpacing: 6, children: [
            Chip2(value: '${o['production']}', label: 'products', icon: Icons.inventory_2),
            Chip2(value: '${o['salary']}', label: cur, icon: Icons.payments),
            Chip2(value: '+${o['skillGain']}', label: 'SP', icon: Icons.trending_up),
            Chip2(value: '${o['wellness']}', label: 'wellness', icon: Icons.favorite),
          ]),
        ]),
      ),
    );
  }

  Widget _report(Map<String, dynamic> r, String cur) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), color: Colors.black.withValues(alpha: .25), border: Border.all(color: EjColors.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Today\'s report', style: display(size: 14)),
          const SizedBox(height: 6),
          _kv('Produced', '${r['produced']} products'),
          _kv('Salary', '${r['salary']} $cur (tax ${r['tax']})'),
          _kv('Skill points gained', '+${r['spGained']}'),
          _kv('Wellness', '${r['wellnessBefore']} → ${(r['wellnessBefore'] as num) - (r['wellnessLoss'] as num) + (r['wellnessRecovered'] as num)}'),
          _kv('Experience', '+${r['epGained']} EP'),
        ]),
      );

  Widget _kv(String k, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 2), child: Row(children: [Expanded(child: Text(k, style: const TextStyle(color: EjColors.muted))), Text(v, style: const TextStyle(fontWeight: FontWeight.w700))]));
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'shell.dart';

/// Workplace — the legacy company-{id}-workplace page.
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
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final work = ref.watch(workProvider);
    return LegacyFrame(
      onRefresh: () => ref.refresh(workProvider.future),
      children: [
        SidebarLayout(
          content: work.when(
            loading: () => const Loading(),
            error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(workProvider)),
            data: (d) {
              if (d['employed'] != true) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Notice('You are not employed!', error: true, sub: 'Find a job in the job market.'),
                    Center(child: ImgButton('Go to job market', onPressed: () => openWeb(ref, 'market-en.html'))),
                  ],
                );
              }
              final comp = d['company'] as Map<String, dynamic>;
              final cur = d['salaryCurrency'] as String;
              final options = (d['options'] as List).cast<Map<String, dynamic>>();
              final worked = d['workedToday'] == true;
              final report = d['report'] as Map<String, dynamic>?;
              final stats = d['stats'] as Map<String, dynamic>?;
              final sel = options.firstWhere((o) => o['type'] == _type, orElse: () => options.first);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // company header (as on the company page)
                  Row(
                    children: [
                      Avatar(comp['avatar'] as String, size: 46),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              comp['name'] as String,
                              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: EjColors.black),
                            ),
                            Row(
                              children: [
                                Text('${comp['industry']} · ', style: const TextStyle(fontSize: 11)),
                                legacy('${(comp['stars'] as int).clamp(0, 5)}_star.gif', height: 10),
                              ],
                            ),
                          ],
                        ),
                      ),
                      ImgButton('Back to company', width: 120, onPressed: () => openWeb(ref, 'company-${comp['id']}-details-en.html')),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if (worked && report != null) _WorkReport(report, cur, stats?['workInRow'] as int? ?? 0),
                  if (worked && report == null) const Notice('You have worked today!', sub: 'Please come back tomorrow'),
                  if (stats != null) ...[
                    const SubHead('Your work stats'),
                    _indicator(
                      'Work skill',
                      Container(
                        width: 26,
                        height: 22,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: const Color(0xFF4169E1),
                          borderRadius: BorderRadius.circular(3),
                          border: Border.all(color: Colors.white),
                        ),
                        child: Text(
                          '${stats['skill']}',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                        ),
                      ),
                      LegacyBar(value: _frac(stats['sp'] as num, stats['spFrom'] as num, stats['spTo'] as num), color: const Color(0xFF4169E1), height: 8),
                      'Skill points: ${fmt(stats['sp'] as num)} / ${fmt(stats['spTo'] as num)}',
                    ),
                    Padding(
                      padding: const EdgeInsets.only(left: 6),
                      child: Text('Daily salary: ${fmt(d['salary'] as num)} $cur', style: const TextStyle(fontSize: 12)),
                    ),
                    const Divider(),
                  ],
                  if (!worked) ...[
                    const SubHead('Workplace'),
                    const Text('Choose your work type:', textAlign: TextAlign.center, style: TextStyle(fontSize: 12)),
                    const SizedBox(height: 6),
                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [for (final o in options) _taskBut(o)]),
                    const Divider(),
                    Table(
                      children: [
                        const TableRow(children: [_Th('Productivity'), _Th('Salary'), _Th('Skill points'), _Th('Wellness')]),
                        TableRow(children: [_Td('${sel['production']}'), _Td('${sel['salary']} $cur'), _Td('+${sel['skillGain']}'), _Td('${sel['wellness']}')]),
                      ],
                    ),
                    const Divider(color: EjColors.black, thickness: .5),
                    FoodPicker(foods: (d['foods'] as Map).cast<String, dynamic>(), selected: _foods, onChanged: (f) => setState(() => _foods = f), maxRecover: -(sel['wellness'] as num).toInt()),
                    const SizedBox(height: 10),
                    Center(
                      child: ImgButton('Work', busy: _busy, onPressed: _work),
                    ),
                    const SizedBox(height: 8),
                  ],
                ],
              );
            },
          ),
        ),
      ],
    );
  }

  static double _frac(num v, num from, num to) => to > from ? ((v - from) / (to - from)).clamp(0, 1).toDouble() : 1;

  Widget _taskBut(Map<String, dynamic> o) {
    final selected = o['type'] == _type;
    final img = const {1: 'normal', 2: 'extra', 3: 'hard'}[o['type']]!;
    return InkWell(
      onTap: () => setState(() => _type = o['type'] as int),
      child: Container(
        width: 78,
        margin: const EdgeInsets.symmetric(horizontal: 3),
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFE6F3FF) : Colors.white,
          border: Border.all(color: selected ? EjColors.link : EjColors.line, width: selected ? 2 : 1),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Column(
          children: [
            legacy('work/$img.gif', width: 50, height: 100),
            Text(
              o['label'] as String,
              style: const TextStyle(fontSize: 11, color: EjColors.black, fontWeight: FontWeight.bold),
            ),
            Text('${o['wellness']} wellness', style: const TextStyle(fontSize: 9)),
          ],
        ),
      ),
    );
  }

  Widget _indicator(String title, Widget desc, Widget bar, String caption) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Row(
      children: [
        SizedBox(width: 90, child: Text(title, style: const TextStyle(fontSize: 12))),
        desc,
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              bar,
              Text(caption, style: const TextStyle(fontSize: 9)),
            ],
          ),
        ),
      ],
    ),
  );
}

class _Th extends StatelessWidget {
  const _Th(this.t);
  final String t;
  @override
  Widget build(BuildContext context) => Text(
    t,
    textAlign: TextAlign.center,
    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.black),
  );
}

class _Td extends StatelessWidget {
  const _Td(this.t);
  final String t;
  @override
  Widget build(BuildContext context) => Text(t, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12));
}

/// `#work-report` — same blue-gradient box as the train report, with salary/tax rows.
class _WorkReport extends StatelessWidget {
  const _WorkReport(this.r, this.cur, this.inRow);
  final Map<String, dynamic> r;
  final String cur;
  final int inRow;
  @override
  Widget build(BuildContext context) {
    final before = (r['wellnessBefore'] as num), loss = (r['wellnessLoss'] as num), rec = (r['wellnessRecovered'] as num);
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        border: Border.all(color: EjColors.black),
        borderRadius: const BorderRadius.only(topLeft: Radius.circular(15), bottomRight: Radius.circular(15), topRight: Radius.circular(2), bottomLeft: Radius.circular(2)),
        gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.center, colors: [EjColors.reportBlue, Colors.white]),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              border: Border.all(color: EjColors.black, width: .8),
              borderRadius: BorderRadius.circular(5),
            ),
            child: Column(
              children: [
                const Text(
                  'Your productivity',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black),
                ),
                Text(
                  fmt(r['produced'] as num),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black),
                ),
                Text('Work type ${50 + (r['type'] as int) * 50}%', style: const TextStyle(fontSize: 10, color: EjColors.black)),
              ],
            ),
          ),
          const Divider(color: EjColors.black, thickness: .5),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              legacy('work-hummy.png', width: 70, height: 110, fit: BoxFit.contain),
              Expanded(child: Column(children: [_kv('Skill', fmt(r['skill'] as num)), _kv('Wellness', fmt(before)), _kv('Work in a row', '$inRow')])),
              Container(width: 1, height: 120, color: EjColors.black),
              Expanded(
                child: Column(
                  children: [
                    _kv('Wellness', fmt(before - (loss - rec))),
                    _kv('Skill points', fmt(r['sp'] as num), plus: '+${fmt(r['spGained'] as num)}'),
                    _kv('EP', '', plus: '+${fmt(r['epGained'] as num)}'),
                    const Divider(height: 4),
                    _kv('Salary', '${fmt(r['salary'] as num)} $cur'),
                    _kv('Tax', '${fmt(r['tax'] as num)} $cur'),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _kv(String k, String v, {String? plus}) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 5),
    child: Row(
      children: [
        Expanded(
          child: Text(
            k,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.black),
          ),
        ),
        Text(
          v,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.black),
        ),
        if (plus != null)
          Text(
            ' ($plus)',
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.green),
          ),
      ],
    ),
  );
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'battle.dart';
import 'shell.dart';

/// Daily training — the legacy army page (train form, "Your received skill" report, military stats).
class ArmyScreen extends ConsumerStatefulWidget {
  const ArmyScreen({super.key});
  @override
  ConsumerState<ArmyScreen> createState() => _ArmyState();
}

class _ArmyState extends ConsumerState<ArmyScreen> {
  int _type = 1;
  Map<int, int> _foods = {};
  bool _busy = false;
  bool _justTrained = false;

  Future<void> _train() async {
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('army/train', {'type': _type, 'foods': _foods.map((k, v) => MapEntry('$k', v))});
      ref.read(sessionProvider.notifier).update(r);
      _justTrained = true;
      ref.invalidate(armyProvider);
      ref.invalidate(homeProvider);
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final army = ref.watch(armyProvider);
    final home = ref.watch(homeProvider).asData?.value;
    return LegacyFrame(
      onRefresh: () => ref.refresh(armyProvider.future),
      children: [
        SidebarLayout(
          content: army.when(
            loading: () => const Loading(),
            error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(armyProvider)),
            data: (d) {
              final options = (d['options'] as List).cast<Map<String, dynamic>>();
              final trained = d['trainedToday'] == true;
              final report = d['report'] as Map<String, dynamic>?;
              final stats = d['stats'] as Map<String, dynamic>?;
              final sel = options.firstWhere((o) => o['type'] == _type, orElse: () => options.first);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (trained && !_justTrained) const Notice('You have trained today!', sub: 'Please come back tomorrow'),
                  if (trained && report != null) _TrainReport(report),
                  if (!trained) ...[
                    const SubHead('Train in the army'),
                    const Text('Choose your training type:', textAlign: TextAlign.center, style: TextStyle(fontSize: 12)),
                    const SizedBox(height: 6),
                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [for (final o in options) _taskBut(o)]),
                    const SizedBox(height: 10),
                    FoodPicker(foods: (d['foods'] as Map).cast<String, dynamic>(), selected: _foods, onChanged: (f) => setState(() => _foods = f), maxRecover: -(sel['wellness'] as num).toInt()),
                    const SizedBox(height: 10),
                    Center(
                      child: ImgButton('Train', busy: _busy, onPressed: _train),
                    ),
                    const SizedBox(height: 12),
                  ],
                  if (stats != null) _stats(stats, options),
                  const Divider(color: EjColors.black, thickness: .5),
                  const SubHead('Active battles for your country', center: true),
                  if ((home?.battles ?? []).isEmpty) const EmptyNote('There is no active battle for your country.'),
                  for (final b in home?.battles ?? [])
                    InkWell(
                      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: b.id))),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: Row(
                          children: [
                            legacy('att-s.jpg', width: 20, height: 20),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text('${b.attacker} attacked ${b.region}, ${b.defender}', style: const TextStyle(color: EjColors.link, fontSize: 12)),
                            ),
                            Text('started ${ago(b.start)}', style: const TextStyle(fontSize: 9)),
                          ],
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }

  /// `.taskbuts` — the three training pictures (normal / extra / super) acting as radio buttons.
  Widget _taskBut(Map<String, dynamic> o) {
    final unlocked = o['unlocked'] == true;
    final selected = o['type'] == _type;
    final img = const {1: 'normal', 2: 'extra', 3: 'super'}[o['type']]!;
    return Opacity(
      opacity: unlocked ? 1 : .4,
      child: InkWell(
        onTap: unlocked ? () => setState(() => _type = o['type'] as int) : null,
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
              legacy('train/$img.gif', width: 50, height: 100),
              Text(
                o['label'] as String,
                style: const TextStyle(fontSize: 11, color: EjColors.black, fontWeight: FontWeight.bold),
              ),
              Text('${o['wellness']} wellness', style: const TextStyle(fontSize: 9)),
              Text('+${o['skillGain']} SP', style: const TextStyle(fontSize: 9, color: EjColors.green)),
            ],
          ),
        ),
      ),
    );
  }

  /// "Your military stats": skill bar (RoyalBlue), rank bar (maroon), skill-points-per-train table.
  Widget _stats(Map<String, dynamic> s, List<Map<String, dynamic>> options) {
    double frac(num v, num from, num to) => to > from ? ((v - from) / (to - from)).clamp(0, 1).toDouble() : 1;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SubHead('Your military stats'),
        _indicator(
          'Military skill',
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
              '${s['skill']}',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
            ),
          ),
          LegacyBar(value: frac(s['sp'] as num, s['spFrom'] as num, s['spTo'] as num), color: const Color(0xFF4169E1), height: 8),
          'Skill points: ${fmt(s['sp'] as num)} / ${fmt(s['spTo'] as num)}',
        ),
        _indicator(
          'Military rank',
          netImg(s['rankIcon'] as String, width: 50, height: 13, fit: BoxFit.contain),
          LegacyBar(value: frac(s['damage'] as num, s['damageFrom'] as num, s['damageTo'] as num), color: const Color(0xFF800000), height: 8),
          'Total advance: ${fmt(s['damage'] as num)} / ${fmt(s['damageTo'] as num)} · ${s['rankName']}',
        ),
        const Divider(),
        Table(
          columnWidths: const {0: FlexColumnWidth(1.4)},
          children: [
            TableRow(
              children: [
                const SizedBox(),
                for (final o in options) Text('${o['label']} train', style: const TextStyle(fontSize: 12)),
              ],
            ),
            TableRow(
              children: [
                const Text(
                  'Received Skill points',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
                ),
                for (final o in options) Text('${o['skillGain']}', style: const TextStyle(fontSize: 12)),
              ],
            ),
          ],
        ),
        const SizedBox(height: 6),
        Align(
          alignment: Alignment.centerLeft,
          child: ImgButton('Show active wars', onPressed: () => openWeb(ref, 'wars-en.html')),
        ),
        const SizedBox(height: 6),
      ],
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

/// `#train-report` — blue-gradient rounded box: "Your received skill N", hummy, info/changes columns.
class _TrainReport extends StatelessWidget {
  const _TrainReport(this.r);
  final Map<String, dynamic> r;
  @override
  Widget build(BuildContext context) {
    final before = (r['wellnessBefore'] as num), loss = (r['wellnessLoss'] as num), rec = (r['wellnessRecovered'] as num);
    final sp = (r['sp'] as num);
    final left = ((sp / 7500).floor() + 1) * 7500 - sp;
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
              border: Border.all(color: EjColors.black, style: BorderStyle.solid, width: .8),
              borderRadius: BorderRadius.circular(5),
            ),
            child: Column(
              children: [
                const Text(
                  'Your received skill',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black),
                ),
                Text(
                  fmt(r['received'] as num),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black),
                ),
                Text('${fmt(left)} Skill points left to receive IS trophy', style: const TextStyle(fontSize: 10, color: EjColors.black)),
              ],
            ),
          ),
          const Divider(color: EjColors.black, thickness: .5),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              legacy('train-hummy.png', width: 70, height: 110, fit: BoxFit.contain),
              Expanded(child: Column(children: [_kv('Skill', '${fmt(r['skill'] as num)}'), _kv('Wellness', '${fmt(before)}'), _kv('Train type', '${50 + (r['type'] as int) * 50}%')])),
              Container(width: 1, height: 90, color: EjColors.black),
              Expanded(
                child: Column(
                  children: [
                    _kv('Wellness', '${fmt(before - (loss - rec))}'),
                    _kv('Skill points', '${fmt(sp)}', plus: '+${fmt(r['spGained'] as num)}'),
                    _kv('EP', '', plus: '+${fmt(r['ep'] as num)}'),
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
    padding: const EdgeInsets.symmetric(vertical: 5, horizontal: 5),
    child: Row(
      children: [
        Expanded(
          child: Text(
            k,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
          ),
        ),
        Text(
          v,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
        ),
        if (plus != null)
          Text(
            ' ($plus)',
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.green),
          ),
      ],
    ),
  );
}

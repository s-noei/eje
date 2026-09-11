import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'battle.dart';
import 'shell.dart';

/// Daily training: one session a day — weights (+1 strength) or cardio (+1 stamina) —
/// building the citizen's body shape (0–7). Military rank is shown as prestige only.
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
              final shape = d['shape'] as Map<String, dynamic>;
              final stats = d['stats'] as Map<String, dynamic>?;
              final sel = options.firstWhere((o) => o['type'] == _type, orElse: () => options.first);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (trained && !_justTrained) const Notice('You have trained today!', sub: 'Please come back tomorrow'),
                  if (trained && report != null) _TrainReport(report, shape),
                  if (!trained) ...[
                    const SubHead('Train in the army'),
                    const Text("Choose today's session:", textAlign: TextAlign.center, style: TextStyle(fontSize: 12)),
                    const SizedBox(height: 6),
                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [for (final o in options) _session(o, shape)]),
                    const SizedBox(height: 10),
                    FoodPicker(foods: (d['foods'] as Map).cast<String, dynamic>(), selected: _foods, onChanged: (f) => setState(() => _foods = f), maxRecover: -(sel['wellness'] as num).toInt()),
                    const SizedBox(height: 10),
                    Center(child: ImgButton('Train', busy: _busy, onPressed: _train)),
                    const SizedBox(height: 12),
                  ],
                  _BodyShape(shape),
                  if (stats != null) _rank(stats),
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
                            Expanded(child: Text('${b.attacker} attacked ${b.region}, ${b.defender}', style: const TextStyle(color: EjColors.link, fontSize: 12))),
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

  /// `.taskbuts` — the two session pictures acting as radio buttons.
  Widget _session(Map<String, dynamic> o, Map<String, dynamic> shape) {
    final selected = o['type'] == _type;
    final weights = o['type'] == 1;
    final cur = (shape[o['stat']] as num).toInt();
    final maxed = cur >= kShapeMax;
    return InkWell(
      onTap: () => setState(() => _type = o['type'] as int),
      child: Container(
        width: 118,
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.all(5),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFE6F3FF) : Colors.white,
          border: Border.all(color: selected ? EjColors.link : EjColors.line, width: selected ? 2 : 1),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Column(
          children: [
            legacy(weights ? 'train/normal.gif' : 'train/super.gif', width: 50, height: 100),
            Text(o['label'] as String, style: const TextStyle(fontSize: 12, color: EjColors.black, fontWeight: FontWeight.bold)),
            Text(maxed ? 'keeps ${o['stat']} at max' : o['effect'] as String, style: TextStyle(fontSize: 10, color: maxed ? EjColors.text : EjColors.green, fontWeight: FontWeight.bold)),
            Text(o['desc'] as String, textAlign: TextAlign.center, style: const TextStyle(fontSize: 9)),
            Text('${o['wellness']} wellness', style: const TextStyle(fontSize: 9)),
          ],
        ),
      ),
    );
  }

  /// Military rank: prestige, the unit bonus you can lead with, and the next rank reward.
  Widget _rank(Map<String, dynamic> s) {
    double frac(num v, num from, num to) => to > from ? ((v - from) / (to - from)).clamp(0, 1).toDouble() : 1;
    final next = s['nextRankName'] as String?;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SubHead('Military rank'),
        Row(
          children: [
            netImg(s['rankIcon'] as String, width: 60, height: 16, fit: BoxFit.contain),
            const SizedBox(width: 6),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${s['rankName']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black)),
                  LegacyBar(value: frac(s['damage'] as num, s['damageFrom'] as num, s['damageTo'] as num), color: const Color(0xFF800000), height: 8),
                  Text('Total advance ${fmt(s['damage'] as num)} / ${fmt(s['damageTo'] as num)}', style: const TextStyle(fontSize: 9)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          next == null
              ? 'Highest rank reached. Rank is your prestige: it lets you lead a military unit and sets your unit\'s battle bonus.'
              : 'Next: $next — reward ${s['nextRankReward']} Tala + a 5-star food. Rank is prestige: it lets you lead a military unit and sets your unit\'s battle bonus.',
          style: const TextStyle(fontSize: 10),
        ),
        const SizedBox(height: 6),
        Align(alignment: Alignment.centerLeft, child: ImgButton('Show active wars', onPressed: () => openWeb(ref, 'wars-en.html'))),
        const SizedBox(height: 6),
      ],
    );
  }
}

/// Seven-segment meter for strength / stamina.
class ShapeMeter extends StatelessWidget {
  const ShapeMeter({super.key, required this.value, required this.color, this.height = 12});
  final int value;
  final Color color;
  final double height;
  @override
  Widget build(BuildContext context) => Row(
    children: [
      for (var i = 1; i <= kShapeMax; i++)
        Expanded(
          child: Container(
            height: height,
            margin: const EdgeInsets.only(right: 2),
            decoration: BoxDecoration(color: i <= value ? color : const Color(0xFFEEEEEE), border: Border.all(color: const Color(0xFF666666), width: .8), borderRadius: BorderRadius.circular(2)),
          ),
        ),
    ],
  );
}

/// "Your body shape": strength + stamina meters, streak and what they mean in battle.
class _BodyShape extends StatelessWidget {
  const _BodyShape(this.s);
  final Map<String, dynamic> s;
  @override
  Widget build(BuildContext context) {
    final streak = s['streak'] as int;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SubHead('Your body shape — ${s['name']}'),
        Row(
          children: [
            Expanded(child: LegacyBar(value: (streak.clamp(0, kShapeMax)) / kShapeMax, color: EjColors.lime, height: 12, label: 'Day ${streak.clamp(0, kShapeMax)} of $kShapeMax in a row')),
          ],
        ),
        const SizedBox(height: 6),
        _row('Strength', const Color(0xFFCC3333), s['strength'] as int, '→ ${fmt(s['damage'] as num)} damage per hit'),
        _row('Stamina', const Color(0xFF3366CC), s['stamina'] as int, '→ a fight costs ${fmt(s['fightCost'] as num)} wellness'),
        const SizedBox(height: 4),
        const Text('Train every day: each session adds one stage, each missed day takes one away. Weapons multiply your hit.', style: TextStyle(fontSize: 10)),
        const SizedBox(height: 6),
      ],
    );
  }

  Widget _row(String label, Color color, int v, String effect) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 3),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 62, child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black))),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(child: ShapeMeter(value: v, color: color)),
                  const SizedBox(width: 6),
                  Text('$v/$kShapeMax', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.black)),
                ],
              ),
              Text(effect, style: const TextStyle(fontSize: 10)),
            ],
          ),
        ),
      ],
    ),
  );
}

/// `#train-report` — blue-gradient rounded box for today's session.
class _TrainReport extends StatelessWidget {
  const _TrainReport(this.r, this.shape);
  final Map<String, dynamic> r, shape;
  @override
  Widget build(BuildContext context) {
    final before = (r['wellnessBefore'] as num), loss = (r['wellnessLoss'] as num), rec = (r['wellnessRecovered'] as num);
    final weights = r['type'] == 1;
    final stat = (weights ? r['strength'] : r['stamina']) as int;
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
            decoration: BoxDecoration(border: Border.all(color: EjColors.black, width: .8), borderRadius: BorderRadius.circular(5)),
            child: Column(
              children: [
                Text('${weights ? 'Weights' : 'Cardio'} session done', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black)),
                Text('${weights ? 'Strength' : 'Stamina'} $stat / $kShapeMax', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: EjColors.black)),
                Text('Day ${r['streak']} in a row — ${shape['name']}', style: const TextStyle(fontSize: 10, color: EjColors.black)),
              ],
            ),
          ),
          const Divider(color: EjColors.black, thickness: .5),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              legacy('train-hummy.png', width: 70, height: 110, fit: BoxFit.contain),
              Expanded(
                child: Column(
                  children: [
                    _kv('Strength', '${r['strength']} / $kShapeMax'),
                    _kv('Stamina', '${r['stamina']} / $kShapeMax'),
                    _kv('Hit', fmt(shape['damage'] as num)),
                  ],
                ),
              ),
              Container(width: 1, height: 90, color: EjColors.black),
              Expanded(
                child: Column(
                  children: [
                    _kv('Wellness', fmt(before - (loss - rec))),
                    _kv('Fight cost', '${fmt(shape['fightCost'] as num)} wellness'),
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
        Expanded(child: Text(k, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black))),
        Text(v, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black)),
        if (plus != null) Text(' ($plus)', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.green)),
      ],
    ),
  );
}

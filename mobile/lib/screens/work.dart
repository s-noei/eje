import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'army.dart' show ShapeMeter;
import 'shell.dart';

/// The workshop: the work counterpart of the gym. One session a day — a shift (+1 craft,
/// full output and pay) or a study day (+1 efficiency, half output and pay).
class WorkScreen extends ConsumerStatefulWidget {
  const WorkScreen({super.key});
  @override
  ConsumerState<WorkScreen> createState() => _WorkState();
}

const _amber = Color(0xFFF39C12);

class _WorkState extends ConsumerState<WorkScreen> {
  int? _type;
  bool _busy = false;
  bool _justWorked = false;

  Future<void> _work() async {
    if (_type == null) {
      toast(context, 'Choose a session first', error: true);
      return;
    }
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('work', {'type': _type});
      ref.read(sessionProvider.notifier).update(r);
      _justWorked = true;
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
    final c = ref.watch(sessionProvider);
    return LegacyFrame(
      dark: true,
      padding: const EdgeInsets.all(4),
      onRefresh: () => ref.refresh(workProvider.future),
      children: [
        work.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(workProvider)),
          data: (d) {
            if (d['employed'] != true) {
              return Container(
                padding: const EdgeInsets.all(14),
                decoration: Er.cardBox(),
                child: Column(
                  children: [
                    const Text('🧑‍🏭', style: TextStyle(fontSize: 40)),
                    const SizedBox(height: 6),
                    const Text('You are not employed', style: TextStyle(color: Er.text, fontSize: 15, fontWeight: FontWeight.w800)),
                    const Text('Find a job in the job market to start working.', style: TextStyle(color: Er.muted, fontSize: 12)),
                    const SizedBox(height: 10),
                    GlowButton('Job market', onPressed: () => openWeb(ref, 'jobs-en.html')),
                  ],
                ),
              );
            }
            final comp = d['company'] as Map<String, dynamic>;
            final cur = d['salaryCurrency'] as String;
            final options = (d['options'] as List).cast<Map<String, dynamic>>();
            final worked = d['workedToday'] == true;
            final report = d['report'] as Map<String, dynamic>?;
            final craft = d['craft'] as Map<String, dynamic>;
            final lowWellness = (c?.wellness ?? 100) <= (options.last['wellness'] as num).abs();
            final avatar = _justWorked ? 'avatar-victory' : (worked ? 'avatar-rest' : (lowWellness ? 'avatar-tired' : 'shape-${craft['stage']}'));
            final banner = _justWorked
                ? '🧾 Shift done! Come back tomorrow to keep your craft.'
                : worked
                ? '💤 You already worked today — come back tomorrow.'
                : lowWellness
                ? '🥵 Too tired to work — eat or drink something first.'
                : 'Pick today\'s session. Every day adds a stage, every missed day takes one away.';
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _WorkScene(avatar: avatar, craft: craft, comp: comp, shiftCost: (options.first['wellness'] as num).abs(), banner: banner),
                const SizedBox(height: 8),
                if (worked && report != null) _WorkReport(report, craft, cur),
                if (!worked) ...[
                  Row(
                    children: [for (final o in options) Expanded(child: _SessionCard(o, craft: craft, cur: cur, selected: _type == o['type'], onTap: () => setState(() => _type = o['type'] as int)))],
                  ),
                  const SizedBox(height: 10),
                  Center(child: GlowButton('🧾 Work', busy: _busy, color: _amber, onPressed: !lowWellness && _type != null ? _work : null)),
                  const SizedBox(height: 8),
                ],
                const SizedBox(height: 4),
                Center(child: LinkText('Company page »', onTap: () => openWeb(ref, 'company-${comp['id']}-details-en.html'), color: Er.blue)),
              ],
            );
          },
        ),
      ],
    );
  }
}

/// The workshop scene: warm dark room, the avatar, craft/efficiency meters, company line.
class _WorkScene extends StatelessWidget {
  const _WorkScene({required this.avatar, required this.craft, required this.comp, required this.shiftCost, required this.banner});
  final String avatar, banner;
  final Map<String, dynamic> craft, comp;
  final num shiftCost;
  @override
  Widget build(BuildContext context) {
    final streak = craft['streak'] as int;
    const white = TextStyle(color: Colors.white, fontFamily: ejFontFamily, shadows: [Shadow(color: Colors.black, blurRadius: 4)]);
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Container(
        height: 340,
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF2A1F14), Color(0xFF0F0D0B)])),
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.black.withValues(alpha: .35), Colors.transparent, Colors.black.withValues(alpha: .75)], stops: const [0, .35, 1])),
              ),
            ),
            Positioned(left: 0, right: 0, bottom: 44, child: Center(child: Image.asset('assets/legacy/gym/$avatar.png', height: 230, fit: BoxFit.contain))),
            Positioned(
              left: 10,
              top: 8,
              right: 190,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(craft['name'] as String, style: white.copyWith(fontSize: 20, fontWeight: FontWeight.bold)),
                  Text('${streak > 0 ? '🔥' : '🌫️'} Day ${streak.clamp(0, kShapeMax)} of $kShapeMax${streak > kShapeMax ? ' · $streak days' : ''}', style: white.copyWith(fontSize: 12, color: const Color(0xFFFFD9A0))),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Avatar(comp['avatar'] as String, size: 22),
                      const SizedBox(width: 4),
                      Expanded(child: Text('${comp['name']} · ${comp['industry']}', style: white.copyWith(fontSize: 11), overflow: TextOverflow.ellipsis)),
                    ],
                  ),
                  legacy('${(comp['stars'] as int).clamp(0, 5)}_star.gif', height: 9),
                ],
              ),
            ),
            Positioned(
              right: 10,
              top: 8,
              width: 170,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  _meter('🛠️ Craft', craft['craft'] as int, _amber, '×${fmt(craft['factor'] as num)} output', white),
                  const SizedBox(height: 6),
                  _meter('⚡ Efficiency', craft['efficiency'] as int, const Color(0xFF22D3EE), 'shift ${fmt(shiftCost)} wellness', white),
                ],
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                color: Colors.black.withValues(alpha: .7),
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                child: Text(banner, textAlign: TextAlign.center, style: white.copyWith(fontSize: 12, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _meter(String label, int v, Color color, String effect, TextStyle white) => Column(
    crossAxisAlignment: CrossAxisAlignment.end,
    children: [
      Row(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          Flexible(child: FittedBox(fit: BoxFit.scaleDown, child: Text(label, style: white.copyWith(fontSize: 11, fontWeight: FontWeight.bold)))),
          const SizedBox(width: 6),
          Text('$v/$kShapeMax', style: white.copyWith(fontSize: 11, fontWeight: FontWeight.bold)),
        ],
      ),
      const SizedBox(height: 2),
      SizedBox(width: 150, child: ShapeMeter(value: v, color: color, height: 9)),
      Text(effect, style: white.copyWith(fontSize: 10, color: const Color(0xFFFFD9A0))),
    ],
  );
}

/// Session card: shift or study, with today's output / pay / wellness.
class _SessionCard extends StatelessWidget {
  const _SessionCard(this.o, {required this.craft, required this.cur, required this.selected, required this.onTap});
  final Map<String, dynamic> o, craft;
  final String cur;
  final bool selected;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) {
    final shift = o['type'] == 1;
    final maxed = (craft[o['stat']] as num).toInt() >= kShapeMax;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: const EdgeInsets.all(3),
        padding: const EdgeInsets.fromLTRB(6, 10, 6, 10),
        decoration: Er.cardBox(border: selected ? _amber : null, glow: selected, glowColor: _amber),
        child: Column(
          children: [
            Text(shift ? '🏭' : '📚', style: const TextStyle(fontSize: 56)),
            const SizedBox(height: 4),
            Text(o['label'] as String, style: const TextStyle(color: Er.text, fontSize: 15, fontWeight: FontWeight.bold)),
            Text(maxed ? 'keeps ${o['stat']} at max' : o['effect'] as String, style: const TextStyle(color: Er.accentDark, fontSize: 12, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text('${fmt(o['production'] as num)} products', style: const TextStyle(color: Er.text, fontSize: 11)),
            Text('${fmt(o['salary'] as num)} $cur', style: const TextStyle(color: Er.text, fontSize: 11, fontWeight: FontWeight.bold)),
            Text('${o['wellness']} wellness', style: const TextStyle(color: Er.muted, fontSize: 10)),
          ],
        ),
      ),
    );
  }
}

/// Today's work report.
class _WorkReport extends StatelessWidget {
  const _WorkReport(this.r, this.craft, this.cur);
  final Map<String, dynamic> r, craft;
  final String cur;
  @override
  Widget build(BuildContext context) {
    final shift = r['type'] == 1;
    final stat = (shift ? r['craft'] : r['efficiency']) as int;
    TextStyle t([double s = 12, Color c = Er.text, FontWeight w = FontWeight.normal]) => TextStyle(color: c, fontSize: s, fontWeight: w);
    Widget kv(String k, String v, {Color? color}) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(children: [Expanded(child: Text(k, style: t(12, Er.muted))), Text(v, style: t(12, color ?? Er.text, FontWeight.bold))]),
    );
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: Er.cardBox(),
      child: Column(
        children: [
          Text('${shift ? '🏭 Shift' : '📚 Study day'} done', style: t(16, Er.text, FontWeight.bold)),
          Text('${shift ? 'Craft' : 'Efficiency'} $stat / $kShapeMax', style: t(20, shift ? _amber : Er.blue, FontWeight.bold)),
          Text('${(r['streak'] as int) > 0 ? '🔥' : ''} Day ${r['streak']} in a row — ${craft['name']}', style: t(11, Er.muted)),
          const Divider(color: Er.line),
          Row(
            children: [
              Expanded(child: Column(children: [kv('Made', '${fmt(r['units'] as num)} ${r['item']}'), kv('Craft', '${r['craft']} / $kShapeMax'), kv('Efficiency', '${r['efficiency']} / $kShapeMax')])),
              Container(width: 1, height: 70, color: Er.line, margin: const EdgeInsets.symmetric(horizontal: 8)),
              Expanded(child: Column(children: [kv('Salary', '${fmt(r['salary'] as num)} $cur', color: Er.accentDark), kv('Tax', '${fmt(r['tax'] as num)} $cur'), kv('EP', '+${fmt(r['epGained'] as num)}', color: Er.accentDark)])),
            ],
          ),
        ],
      ),
    );
  }
}

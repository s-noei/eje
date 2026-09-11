import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'shell.dart';

/// The gym: your body-shape avatar in the gym scene, strength/stamina meters, and one
/// session a day — weights (+1 strength) or cardio (+1 stamina).
class ArmyScreen extends ConsumerStatefulWidget {
  const ArmyScreen({super.key});
  @override
  ConsumerState<ArmyScreen> createState() => _ArmyState();
}

const _gymDark = Color(0xFF0F1620);
const _gymPanel = Color(0xFF1B2533);
const _gymLine = Color(0xFF2A3A4F);
const _cyan = Color(0xFF22D3EE);
const _orange = Color(0xFFFF8A3D);
const _gymText = Color(0xFFE8EEF7);
const _gymMuted = Color(0xFF9FB3C8);

class _ArmyState extends ConsumerState<ArmyScreen> {
  int? _type;
  bool _busy = false;
  bool _justTrained = false;

  Future<void> _train() async {
    if (_type == null) {
      toast(context, 'Choose a session first', error: true);
      return;
    }
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('army/train', {'type': _type});
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
    final c = ref.watch(sessionProvider);
    return LegacyFrame(
      padding: const EdgeInsets.all(4),
      onRefresh: () => ref.refresh(armyProvider.future),
      children: [
        army.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(armyProvider)),
          data: (d) {
            final options = (d['options'] as List).cast<Map<String, dynamic>>();
            final trained = d['trainedToday'] == true;
            final report = d['report'] as Map<String, dynamic>?;
            final shape = d['shape'] as Map<String, dynamic>;
            final lowWellness = (c?.wellness ?? 100) <= 2;
            final avatar = _justTrained ? 'avatar-victory' : (trained ? 'avatar-rest' : (lowWellness ? 'avatar-tired' : 'shape-${shape['stage']}'));
            final banner = _justTrained
                ? '💪 Session done! Come back tomorrow to keep your shape.'
                : trained
                ? '💤 You already trained today — come back tomorrow.'
                : lowWellness
                ? '🥵 Too tired to train — eat or drink something first.'
                : 'Pick today\'s session. Every day adds a stage, every missed day takes one away.';
            return Container(
              decoration: BoxDecoration(color: _gymDark, borderRadius: BorderRadius.circular(8)),
              padding: const EdgeInsets.all(6),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _GymScene(avatar: avatar, shape: shape, banner: banner),
                  const SizedBox(height: 8),
                  if (trained && report != null) _SessionReport(report, shape),
                  if (!trained) ...[
                    Row(
                      children: [for (final o in options) Expanded(child: _SessionCard(o, shape: shape, selected: _type == o['type'], onTap: () => setState(() => _type = o['type'] as int)))],
                    ),
                    const SizedBox(height: 10),
                    Center(child: _TrainButton(busy: _busy, enabled: !lowWellness && _type != null, onPressed: _train)),
                    const SizedBox(height: 8),
                  ],
                ],
              ),
            );
          },
        ),
      ],
    );
  }
}

/// Seven-segment glowing meter for strength / stamina.
class ShapeMeter extends StatelessWidget {
  const ShapeMeter({super.key, required this.value, required this.color, this.height = 10});
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
            decoration: BoxDecoration(
              color: i <= value ? color : Colors.white.withValues(alpha: .15),
              border: Border.all(color: i <= value ? color.withValues(alpha: .9) : Colors.white.withValues(alpha: .25), width: .8),
              borderRadius: BorderRadius.circular(2),
              boxShadow: i <= value ? [BoxShadow(color: color.withValues(alpha: .7), blurRadius: 5)] : null,
            ),
          ),
        ),
    ],
  );
}

/// The gym scene: background, the avatar, the HUD (shape name + streak) and the two meters.
class _GymScene extends StatelessWidget {
  const _GymScene({required this.avatar, required this.shape, required this.banner});
  final String avatar, banner;
  final Map<String, dynamic> shape;
  @override
  Widget build(BuildContext context) {
    final streak = shape['streak'] as int;
    const white = TextStyle(color: Colors.white, fontFamily: ejFontFamily, shadows: [Shadow(color: Colors.black, blurRadius: 4)]);
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Container(
        height: 340,
        decoration: const BoxDecoration(image: DecorationImage(image: AssetImage('assets/legacy/gym/gym-bg.jpg'), fit: BoxFit.cover)),
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.black.withValues(alpha: .55), Colors.transparent, Colors.black.withValues(alpha: .8)], stops: const [0, .35, 1])),
              ),
            ),
            Positioned(left: 0, right: 0, bottom: 44, child: Center(child: Image.asset('assets/legacy/gym/$avatar.png', height: 230, fit: BoxFit.contain))),
            Positioned(
              left: 10,
              top: 8,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(shape['name'] as String, style: white.copyWith(fontSize: 20, fontWeight: FontWeight.bold)),
                  Text('${streak > 0 ? '🔥' : '🌫️'} Day ${streak.clamp(0, kShapeMax)} of $kShapeMax${streak > kShapeMax ? ' · $streak days' : ''}', style: white.copyWith(fontSize: 12, color: const Color(0xFF9FE7FF))),
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
                  _meter('🏋️ Strength', shape['strength'] as int, _orange, 'hit ${fmt(shape['damage'] as num)}', white),
                  const SizedBox(height: 6),
                  _meter('💓 Stamina', shape['stamina'] as int, _cyan, 'fight ${fmt(shape['fightCost'] as num)} wellness', white),
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
          Text(label, style: white.copyWith(fontSize: 11, fontWeight: FontWeight.bold)),
          const SizedBox(width: 6),
          Text('$v/$kShapeMax', style: white.copyWith(fontSize: 11, fontWeight: FontWeight.bold)),
        ],
      ),
      const SizedBox(height: 2),
      SizedBox(width: 150, child: ShapeMeter(value: v, color: color, height: 9)),
      Text(effect, style: white.copyWith(fontSize: 10, color: const Color(0xFF9FE7FF))),
    ],
  );
}

/// One session card: the art, the emoji title, the effect and the wellness cost.
class _SessionCard extends StatelessWidget {
  const _SessionCard(this.o, {required this.shape, required this.selected, required this.onTap});
  final Map<String, dynamic> o, shape;
  final bool selected;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) {
    final weights = o['type'] == 1;
    final maxed = (shape[o['stat']] as num).toInt() >= kShapeMax;
    return InkWell(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: const EdgeInsets.all(3),
        padding: const EdgeInsets.fromLTRB(6, 8, 6, 10),
        decoration: BoxDecoration(
          gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [_gymPanel, _gymDark]),
          border: Border.all(color: selected ? _cyan : _gymLine, width: 2),
          borderRadius: BorderRadius.circular(8),
          boxShadow: selected ? [BoxShadow(color: _cyan.withValues(alpha: .55), blurRadius: 16)] : null,
        ),
        child: Column(
          children: [
            Image.asset('assets/legacy/gym/${weights ? 'session-weights' : 'session-cardio'}.png', height: 130, fit: BoxFit.contain),
            const SizedBox(height: 4),
            Text('${weights ? '🏋️' : '🏃'} ${o['label']}', style: const TextStyle(color: _gymText, fontSize: 15, fontWeight: FontWeight.bold)),
            Text(maxed ? 'keeps ${o['stat']} at max' : o['effect'] as String, style: const TextStyle(color: Color(0xFF7CFC9A), fontSize: 12, fontWeight: FontWeight.bold)),
            Text('${o['desc']} · ${o['wellness']} wellness', textAlign: TextAlign.center, style: const TextStyle(color: _gymMuted, fontSize: 10)),
          ],
        ),
      ),
    );
  }
}

class _TrainButton extends StatelessWidget {
  const _TrainButton({required this.busy, required this.enabled, required this.onPressed});
  final bool busy, enabled;
  final VoidCallback onPressed;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: enabled && !busy ? onPressed : null,
    borderRadius: BorderRadius.circular(24),
    child: Container(
      width: 170,
      height: 44,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: enabled ? const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFF7FF3FF), _cyan]) : null,
        color: enabled ? null : const Color(0xFF3A4657),
        boxShadow: enabled ? [BoxShadow(color: _cyan.withValues(alpha: .6), blurRadius: 18)] : null,
      ),
      child: busy
          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF061018)))
          : Text('⚡ Train', style: TextStyle(color: enabled ? const Color(0xFF061018) : const Color(0xFF8A97A8), fontSize: 17, fontWeight: FontWeight.bold)),
    ),
  );
}

/// Today's session report (after training).
class _SessionReport extends StatelessWidget {
  const _SessionReport(this.r, this.shape);
  final Map<String, dynamic> r, shape;
  @override
  Widget build(BuildContext context) {
    final before = (r['wellnessBefore'] as num), loss = (r['wellnessLoss'] as num), rec = (r['wellnessRecovered'] as num);
    final weights = r['type'] == 1;
    final stat = (weights ? r['strength'] : r['stamina']) as int;
    TextStyle t([double s = 12, Color c = _gymText, FontWeight w = FontWeight.normal]) => TextStyle(color: c, fontSize: s, fontWeight: w);
    Widget kv(String k, String v, {Color? color}) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(children: [Expanded(child: Text(k, style: t(12, _gymMuted))), Text(v, style: t(12, color ?? _gymText, FontWeight.bold))]),
    );
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [_gymPanel, _gymDark]), border: Border.all(color: _gymLine), borderRadius: BorderRadius.circular(8)),
      child: Column(
        children: [
          Text('${weights ? '🏋️ Weights' : '🏃 Cardio'} session done', style: t(16, _gymText, FontWeight.bold)),
          Text('${weights ? 'Strength' : 'Stamina'} $stat / $kShapeMax', style: t(20, weights ? _orange : _cyan, FontWeight.bold)),
          Text('${(r['streak'] as int) > 0 ? '🔥' : ''} Day ${r['streak']} in a row — ${shape['name']}', style: t(11, _gymMuted)),
          const Divider(color: _gymLine),
          Row(
            children: [
              Expanded(child: Column(children: [kv('Strength', '${r['strength']} / $kShapeMax'), kv('Stamina', '${r['stamina']} / $kShapeMax'), kv('Hit', fmt(shape['damage'] as num))])),
              Container(width: 1, height: 70, color: _gymLine, margin: const EdgeInsets.symmetric(horizontal: 8)),
              Expanded(child: Column(children: [kv('Wellness', fmt(before - (loss - rec))), kv('Fight cost', '${fmt(shape['fightCost'] as num)} wellness'), kv('EP', '+${fmt(r['ep'] as num)}', color: const Color(0xFF7CFC9A))])),
            ],
          ),
        ],
      ),
    );
  }
}

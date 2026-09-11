import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'home.dart';

class BattlesScreen extends ConsumerWidget {
  const BattlesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final battles = ref.watch(battlesProvider);
    return RefreshIndicator(
      onRefresh: () => ref.refresh(battlesProvider.future),
      child: battles.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(battlesProvider)),
        data: (list) {
          final mine = list.where((b) => b.mine).toList();
          final others = list.where((b) => !b.mine).toList();
          return ListView(padding: const EdgeInsets.fromLTRB(14, 8, 14, 24), children: [
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const SectionTitle('Your country\'s battles', icon: Icons.shield),
              if (mine.isEmpty) const EmptyNote('No active battle involves your country.'),
              for (final b in mine) BattleTile(b, onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: b.id)))),
            ])),
            const SizedBox(height: 12),
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const SectionTitle('Around the world', icon: Icons.public),
              if (others.isEmpty) const EmptyNote('The world is at peace.'),
              for (final b in others) BattleTile(b, onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: b.id)))),
            ])),
          ]);
        },
      ),
    );
  }
}

class BattleScreen extends ConsumerStatefulWidget {
  const BattleScreen({super.key, required this.id});
  final int id;
  @override
  ConsumerState<BattleScreen> createState() => _BattleState();
}

class _BattleState extends ConsumerState<BattleScreen> with SingleTickerProviderStateMixin {
  int _weapon = 0;
  bool _busy = false;
  Map<String, dynamic>? _last;
  Timer? _tick;
  late final AnimationController _hit = AnimationController(vsync: this, duration: const Duration(milliseconds: 500));

  @override
  void initState() {
    super.initState();
    _tick = Timer.periodic(const Duration(seconds: 1), (_) => setState(() {}));
  }

  @override
  void dispose() {
    _tick?.cancel();
    _hit.dispose();
    super.dispose();
  }

  Future<void> _fight() async {
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('battles/${widget.id}/fight', {'weapon': _weapon});
      ref.read(sessionProvider.notifier).update(r);
      setState(() => _last = r);
      _hit.forward(from: 0);
      ref.invalidate(battleProvider(widget.id));
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = ref.watch(battleProvider(widget.id));
    return Scaffold(
      appBar: AppBar(title: Text('Battlefield', style: display(size: 18))),
      body: data.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(battleProvider(widget.id))),
        data: (d) {
          final b = Battle.fromJson(d['battle'] as Map<String, dynamic>);
          final side = d['side'] as String?;
          final canFight = d['canFight'] == true;
          final weapons = (d['weapons'] as List).cast<Map<String, dynamic>>();
          final heroes = d['heroes'] as Map<String, dynamic>;
          final log = (d['log'] as List).cast<Map<String, dynamic>>();
          final wellness = (d['wellness'] as num).toDouble();
          final wallPct = b.securePoint > 0 ? (b.wall / b.securePoint).clamp(0.0, 1.0) : 0.0;
          return ListView(padding: const EdgeInsets.fromLTRB(14, 8, 14, 24), children: [
            GlassCard(
              accent: EjColors.red,
              child: Column(children: [
                Row(children: [
                  _sideCol(b.attackerFlag, b.attacker, 'ATTACKER', side == 'att'),
                  Expanded(child: Column(children: [
                    Text(b.region, style: display(size: 20), textAlign: TextAlign.center),
                    Text(b.timeLeft.inSeconds > 0 ? hms(b.timeLeft) : 'CLOSED', style: display(size: 22, color: b.timeLeft.inMinutes < 10 ? EjColors.red : EjColors.accent2)),
                  ])),
                  _sideCol(b.defenderFlag, b.defender, 'DEFENDER', side == 'def'),
                ]),
                const SizedBox(height: 14),
                StatBar(label: 'Wall', value: wallPct, text: '${fmt(b.wall)} / ${fmt(b.securePoint)}', colors: const [EjColors.red, EjColors.gold]),
                const SizedBox(height: 4),
                Text('Attackers need to break the wall before time runs out', style: const TextStyle(color: EjColors.muted, fontSize: 11)),
              ]),
            ),
            const SizedBox(height: 12),
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              SectionTitle('Fight', icon: Icons.local_fire_department, trailing: Text('Your force: ${fmt((d['myForce'] as num))}', style: const TextStyle(color: EjColors.muted, fontSize: 12))),
              StatBar(label: 'Wellness', value: wellness / 100, text: '${fmt(wellness)} / 100', colors: const [Color(0xFFF97316), EjColors.green]),
              const SizedBox(height: 10),
              Wrap(spacing: 6, runSpacing: 6, children: [
                _weaponChip(0, 'Bare hands', null),
                for (final w in weapons) _weaponChip(w['stars'] as int, '${w['stars']}★ weapon', w['amount'] as int),
              ]),
              const SizedBox(height: 14),
              if (side == null) const Text('Your country is not involved in this battle.', style: TextStyle(color: EjColors.muted))
              else EjButton(side == 'att' ? 'Attack!' : 'Defend!', icon: Icons.gps_fixed, busy: _busy, onPressed: canFight && wellness >= 20 && (d['occupiedUntil'] as int) < DateTime.now().millisecondsSinceEpoch ~/ 1000 ? _fight : null),
              if (_last != null) _hitCard(_last!),
            ])),
            const SizedBox(height: 12),
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const SectionTitle('Battle heroes', icon: Icons.military_tech),
              Row(children: [
                Expanded(child: _heroList('Attackers', (heroes['attacker'] as List).cast<Map<String, dynamic>>())),
                Expanded(child: _heroList('Defenders', (heroes['defender'] as List).cast<Map<String, dynamic>>())),
              ]),
            ])),
            const SizedBox(height: 12),
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const SectionTitle('Latest hits', icon: Icons.history),
              if (log.isEmpty) const EmptyNote('No fights yet.'),
              for (final f in log)
                ListTile(
                  dense: true, contentPadding: EdgeInsets.zero,
                  leading: Avatar(f['avatar'] as String, size: 28),
                  title: Text(f['name'] as String, style: const TextStyle(fontSize: 13)),
                  subtitle: Text(ago(f['time'] as int), style: const TextStyle(fontSize: 11, color: EjColors.muted)),
                  trailing: Text('${(f['damage'] as num) < 0 ? '🛡' : '⚔'} ${fmt((f['damage'] as num).abs())}', style: display(size: 14, color: (f['damage'] as num) < 0 ? EjColors.accent2 : EjColors.red)),
                ),
            ])),
          ]);
        },
      ),
    );
  }

  Widget _sideCol(String flag, String name, String label, bool me) => Column(children: [
        Container(
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(6), border: Border.all(color: me ? EjColors.accent2 : Colors.transparent, width: 2)),
          child: ClipRRect(borderRadius: BorderRadius.circular(4), child: Image.network(flag, width: 56, height: 40, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox(width: 56, height: 40))),
        ),
        const SizedBox(height: 4),
        SizedBox(width: 90, child: Text(name, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700), maxLines: 2, overflow: TextOverflow.ellipsis)),
        Text(label, style: const TextStyle(fontSize: 8, color: EjColors.muted, letterSpacing: 1)),
      ]);

  Widget _weaponChip(int stars, String label, int? amount) {
    final sel = _weapon == stars;
    return ChoiceChip(
      selected: sel,
      onSelected: (_) => setState(() => _weapon = stars),
      selectedColor: EjColors.accent,
      backgroundColor: Colors.black.withValues(alpha: .25),
      side: const BorderSide(color: EjColors.line),
      label: Text(amount == null ? label : '$label ×$amount', style: TextStyle(color: sel ? Colors.white : EjColors.text, fontSize: 12)),
    );
  }

  Widget _hitCard(Map<String, dynamic> r) => ScaleTransition(
        scale: Tween(begin: 1.15, end: 1.0).animate(CurvedAnimation(parent: _hit, curve: Curves.elasticOut)),
        child: Container(
          margin: const EdgeInsets.only(top: 12),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), gradient: LinearGradient(colors: [EjColors.red.withValues(alpha: .25), EjColors.gold.withValues(alpha: .15)]), border: Border.all(color: EjColors.gold.withValues(alpha: .5))),
          child: Column(children: [
            Text('${r['tier']} hit!'.toUpperCase(), style: display(size: 13, color: EjColors.gold)),
            Text(fmt((r['damage'] as num)), style: display(size: 34, color: Colors.white)),
            Text('rank ${r['mRank']} · +${r['ep']} EP · wellness ${r['wellness']}', style: const TextStyle(color: EjColors.muted, fontSize: 12)),
          ]),
        ),
      );

  Widget _heroList(String title, List<Map<String, dynamic>> list) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(title, style: const TextStyle(fontSize: 11, color: EjColors.muted, fontWeight: FontWeight.w700)),
        const SizedBox(height: 4),
        if (list.isEmpty) const Text('—', style: TextStyle(color: EjColors.muted)),
        for (final h in list)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 3),
            child: Row(children: [
              Avatar(h['avatar'] as String, size: 24),
              const SizedBox(width: 6),
              Expanded(child: Text(h['name'] as String, style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis)),
              Text(fmt((h['damage'] as num)), style: display(size: 12, color: EjColors.gold)),
            ]),
          ),
      ]);
}

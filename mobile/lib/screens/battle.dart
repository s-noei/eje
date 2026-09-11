import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'shell.dart';

/// Battles tab — the legacy battle lists (battles-bg rows).
class BattlesScreen extends ConsumerWidget {
  const BattlesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final battles = ref.watch(battlesProvider);
    return LegacyFrame(
      onRefresh: () => ref.refresh(battlesProvider.future),
      children: [
        battles.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(battlesProvider)),
          data: (list) {
            final mine = list.where((b) => b.mine).toList();
            final others = list.where((b) => !b.mine).toList();
            void open(Battle b) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: b.id)));
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const BoxTitle('Active battles for your country'),
                if (mine.isEmpty) const EmptyNote('There is no active battle for your country.'),
                for (final b in mine) BattleRow(b, onTap: () => open(b)),
                const SizedBox(height: 10),
                const BoxTitle('Active battles around the world'),
                if (others.isEmpty) const EmptyNote('The world is at peace.'),
                for (final b in others) BattleRow(b, onTap: () => open(b)),
                const SizedBox(height: 8),
                Center(child: ImgButton('Show all wars', onPressed: () => openWeb(ref, 'wars-en.html'))),
              ],
            );
          },
        ),
      ],
    );
  }
}

/// The battlefield page (#battletable + .fight-arena).
class BattleScreen extends ConsumerStatefulWidget {
  const BattleScreen({super.key, required this.id});
  final int id;
  @override
  ConsumerState<BattleScreen> createState() => _BattleState();
}

class _BattleState extends ConsumerState<BattleScreen> {
  int _weapon = 0;
  bool _busy = false;
  Map<String, dynamic>? _last;
  Timer? _tick;

  @override
  void initState() {
    super.initState();
    _tick = Timer.periodic(const Duration(seconds: 1), (_) => setState(() {}));
  }

  @override
  void dispose() {
    _tick?.cancel();
    super.dispose();
  }

  Future<void> _fight() async {
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('battles/${widget.id}/fight', {'weapon': _weapon});
      ref.read(sessionProvider.notifier).update(r);
      setState(() => _last = r);
      if (r['rankedUp'] == true && mounted) toast(context, 'Promoted to ${r['mRank']}! Reward: Tala + a 5-star food.');
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
    return LegacyFrame(
      back: true,
      padding: const EdgeInsets.all(4),
      onRefresh: () => ref.refresh(battleProvider(widget.id).future),
      children: [
        data.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(battleProvider(widget.id))),
          data: (d) {
            final b = Battle.fromJson(d['battle'] as Map<String, dynamic>);
            final side = d['side'] as String?;
            final canFight = d['canFight'] == true;
            final weapons = (d['weapons'] as List).cast<Map<String, dynamic>>();
            final heroes = d['heroes'] as Map<String, dynamic>;
            final log = (d['log'] as List).cast<Map<String, dynamic>>();
            final wellness = (d['wellness'] as num).toDouble();
            final occupied = (d['occupiedUntil'] as int) > DateTime.now().millisecondsSinceEpoch ~/ 1000;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [_battleTable(b), const SizedBox(height: 4), _arena(b, side, canFight && !occupied, weapons, heroes, wellness, (d['myForce'] as num), (d['hit'] as num? ?? 0), (d['fightCost'] as num? ?? 10)), const SizedBox(height: 8), _log(log)],
            );
          },
        ),
      ],
    );
  }

  /// `#battletable` — dark battlefield header: flags, versus, region, timer and the wall.
  Widget _battleTable(Battle b) {
    final wallPct = b.securePoint > 0 ? (b.wall / b.securePoint).clamp(0.0, 1.0) : 0.0;
    final closing = b.timeLeft.inMinutes < 10;
    return Container(
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        image: const DecorationImage(image: AssetImage('assets/legacy/battlefield-bg.png'), fit: BoxFit.cover, alignment: Alignment.topCenter),
        color: const Color(0xFF3A3A3A),
        borderRadius: BorderRadius.circular(4),
      ),
      child: DefaultTextStyle(
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontFamily: ejFontFamily,
          shadows: [Shadow(color: Colors.black87, blurRadius: 3)],
        ),
        child: Column(
          children: [
            Row(
              children: [
                netImg(b.attackerFlag, width: 48, height: 40),
                const SizedBox(width: 6),
                Expanded(child: Text(b.attacker, style: const TextStyle(fontSize: 13))),
                Column(
                  children: [
                    legacy('versus.png', width: 40, height: 40),
                    Text(b.region, style: const TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
                Expanded(
                  child: Text(b.defender, textAlign: TextAlign.right, style: const TextStyle(fontSize: 13)),
                ),
                const SizedBox(width: 6),
                netImg(b.defenderFlag, width: 48, height: 40),
              ],
            ),
            const SizedBox(height: 6),
            if (!b.ended) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFCAD5EF), Color(0xFF6FA9EE)]),
                      borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                    ),
                    child: Text(
                      hms(b.timeLeft),
                      style: TextStyle(color: closing ? EjColors.red : EjColors.black, fontWeight: FontWeight.bold, fontSize: 14, shadows: const []),
                    ),
                  ),
                ],
              ),
              // .battle-field — the wall: attackers advance from the left over the defenders' ground
              Container(
                height: 24,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.white),
                  borderRadius: BorderRadius.circular(3),
                  image: const DecorationImage(image: AssetImage('assets/legacy/defenders-bg.jpg'), fit: BoxFit.fill),
                ),
                child: Stack(
                  children: [
                    FractionallySizedBox(
                      widthFactor: wallPct,
                      child: Container(
                        decoration: const BoxDecoration(
                          image: DecorationImage(image: AssetImage('assets/legacy/attackers-bg.jpg'), fit: BoxFit.fill),
                          border: Border(right: BorderSide(color: Colors.black)),
                        ),
                      ),
                    ),
                    Center(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          image: const DecorationImage(image: AssetImage('assets/legacy/battle-ind-bg.gif'), repeat: ImageRepeat.repeatX),
                          color: Colors.black54,
                          borderRadius: BorderRadius.circular(3),
                        ),
                        child: Text('Wall: ${fmt(b.wall)} / ${fmt(b.securePoint)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 4),
            ],
            Align(
              alignment: Alignment.centerLeft,
              child: PlainButton('Back to war info', onPressed: () => openWeb(ref, 'wars-en.html')),
            ),
          ],
        ),
      ),
    );
  }

  /// `.fight-arena` — heroes columns around the fight area on the burning-city background.
  Widget _arena(Battle b, String? side, bool canFight, List<Map<String, dynamic>> weapons, Map<String, dynamic> heroes, double wellness, num myForce, num hit, num fightCost) {
    return Container(
      constraints: const BoxConstraints(minHeight: 320),
      decoration: BoxDecoration(
        border: Border.all(color: EjColors.black),
        borderRadius: BorderRadius.circular(5),
        image: const DecorationImage(image: AssetImage('assets/legacy/battlefield-bg.jpg'), fit: BoxFit.cover),
      ),
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _heroes('HEROES', EjColors.red, (heroes['attacker'] as List).cast<Map<String, dynamic>>()),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  border: Border.symmetric(vertical: BorderSide(color: Colors.white54)),
                  image: DecorationImage(image: AssetImage('assets/legacy/shoot.png'), alignment: Alignment.bottomLeft, fit: BoxFit.none),
                ),
                padding: const EdgeInsets.fromLTRB(6, 40, 6, 6),
                child: b.ended ? _ended(b) : _fightArea(side, canFight, weapons, wellness, myForce, hit, fightCost),
              ),
            ),
            _heroes('HEROES', const Color(0xFF00C000), (heroes['defender'] as List).cast<Map<String, dynamic>>()),
          ],
        ),
      ),
    );
  }

  Widget _heroes(String title, Color color, List<Map<String, dynamic>> list) => SizedBox(
    width: 74,
    child: Column(
      children: [
        const SizedBox(height: 6),
        Text(
          title,
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: 13,
            fontFamily: 'Comic Sans MS',
            shadows: const [Shadow(color: Colors.black, blurRadius: 2)],
          ),
        ),
        for (var i = 0; i < list.length && i < 3; i++) ...[
          SizedBox(height: i == 0 ? 6 : 10),
          Avatar(list[i]['avatar'] as String, size: 58.0 - i * 8),
          if (list[i]['rankIcon'] != null) netImg(list[i]['rankIcon'] as String, width: 40, height: 10, fit: BoxFit.contain),
          Text(
            list[i]['name'] as String,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Colors.white,
              fontSize: 12.0 - i,
              shadows: const [Shadow(color: Colors.black, blurRadius: 2)],
            ),
          ),
          Text(
            fmt((list[i]['damage'] as num).abs()),
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 12.0 - i,
              shadows: const [Shadow(color: Colors.black, blurRadius: 2)],
            ),
          ),
        ],
      ],
    ),
  );

  /// fight-area-end: the winner flag with the coloured verdict.
  Widget _ended(Battle b) {
    final attWon = b.result == 'conq' || b.result == 'defreat';
    final text = switch (b.result) {
      'secu' => '${b.defender} secured this region against ${b.attacker}',
      'attreat' => 'The CP of ${b.attacker} retreated from the battlefield\nThe region is secured by ${b.defender}',
      'defreat' => 'The CP of ${b.defender} retreated from the battlefield\nThe region is conquered by ${b.attacker}',
      'conq' => '${b.attacker} conquered this region against ${b.defender}',
      _ => 'This battle is closed.',
    };
    return Column(
      children: [
        Container(
          decoration: BoxDecoration(
            border: Border.all(color: EjColors.green, width: 3),
            borderRadius: BorderRadius.circular(5),
            color: Colors.white,
          ),
          child: netImg(attWon ? b.attackerFlag : b.defenderFlag, width: 94, height: 94, fit: BoxFit.contain),
        ),
        const SizedBox(height: 8),
        Text(
          text,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: attWon ? EjColors.red : const Color(0xFF00C000),
            fontSize: 15,
            shadows: const [Shadow(color: Colors.black, blurRadius: 3)],
          ),
        ),
      ],
    );
  }

  /// fight-area: weapon select, wellness meter, "My advance", the Fight button and the last hit.
  Widget _fightArea(String? side, bool canFight, List<Map<String, dynamic>> weapons, double wellness, num myForce, num hit, num fightCost) {
    final dot = BoxDecoration(
      image: const DecorationImage(image: AssetImage('assets/legacy/bg-dot.png'), repeat: ImageRepeat.repeat),
      color: Colors.black38,
      borderRadius: BorderRadius.circular(3),
    );
    final white = const TextStyle(
      color: Colors.white,
      fontSize: 11,
      shadows: [Shadow(color: Colors.black, blurRadius: 2)],
    );
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // .select-wep
            Column(
              children: [
                Text('Select your weapon type', style: white.copyWith(fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Wrap(spacing: 3, runSpacing: 3, children: [_weaponBox(0, null), for (final w in weapons) _weaponBox(w['stars'] as int, w['amount'] as int)]),
              ],
            ),
          ],
        ),
        const SizedBox(height: 8),
        // .info
        Container(
          padding: const EdgeInsets.all(5),
          decoration: dot,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Wellness', style: white),
              LegacyBar(value: wellness / 100, color: EjColors.green, height: 10, label: fmt(wellness)),
              const SizedBox(height: 4),
              Row(children: [_fightBut('juice.png'), _fightBut('clinic.png'), _fightBut('tala.png')]),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(5),
          decoration: dot,
          child: Text('My advance: ${fmt(myForce)}m · hit ${fmt(hit)} · ${fmt(fightCost)} wellness per fight', style: white),
        ),
        const SizedBox(height: 8),
        if (side == null)
          Text('Your country is not involved in this battle.', textAlign: TextAlign.center, style: white)
        else
          InkWell(
            onTap: canFight && wellness >= 20 && !_busy ? _fight : null,
            child: Container(
              width: 120,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(10),
                gradient: canFight && wellness >= 20 ? const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFCAD5EF), Color(0xFF6FA9EE)]) : null,
                color: canFight && wellness >= 20 ? null : Colors.grey,
              ),
              child: _busy
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: EjColors.black))
                  : const Text(
                      'Fight',
                      style: TextStyle(color: EjColors.black, fontWeight: FontWeight.bold, fontSize: 18),
                    ),
            ),
          ),
        if (_last != null) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(6),
            decoration: dot,
            child: Column(
              children: [
                Text(
                  '${_last!['tier']} hit! ${fmt(_last!['damage'] as num)}m',
                  style: white.copyWith(fontSize: 14, fontWeight: FontWeight.bold, color: const Color(0xFFFFD700)),
                ),
                Text('+${_last!['ep']} EP · wellness ${_last!['wellness']} · rank ${_last!['mRank']}', style: white),
              ],
            ),
          ),
        ],
      ],
    );
  }

  /// `.weapon-select`: weapon icon over the N_star strip; bare hands when stars == 0.
  Widget _weaponBox(int stars, int? amount) {
    final sel = _weapon == stars;
    return InkWell(
      onTap: () => setState(() => _weapon = stars),
      child: Container(
        width: 52,
        padding: const EdgeInsets.all(2),
        decoration: BoxDecoration(
          color: sel ? Colors.white : Colors.white70,
          border: Border.all(color: sel ? EjColors.link : EjColors.line, width: sel ? 2 : 1),
          borderRadius: BorderRadius.circular(3),
        ),
        child: Column(
          children: [
            legacy('weapon-icon.png', width: 32, height: 32, fit: BoxFit.contain),
            legacy('${stars}_star.gif', width: 46, height: 9, fit: BoxFit.fill),
            Text(amount == null ? 'hands' : '×$amount', style: const TextStyle(fontSize: 9, color: EjColors.black)),
          ],
        ),
      ),
    );
  }

  Widget _fightBut(String icon) => Container(
    width: 24,
    height: 24,
    margin: const EdgeInsets.only(right: 3),
    padding: const EdgeInsets.all(3),
    decoration: BoxDecoration(
      image: const DecorationImage(image: AssetImage('assets/legacy/buttons-bg.png'), fit: BoxFit.fill),
      borderRadius: BorderRadius.circular(3),
    ),
    child: legacy(icon, fit: BoxFit.contain),
  );

  /// bat-stats: the latest fights as a plain legacy table.
  Widget _log(List<Map<String, dynamic>> log) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      const SubHead('Latest fights', center: true),
      if (log.isEmpty) const EmptyNote('No fights yet.'),
      for (final f in log)
        Container(
          decoration: const BoxDecoration(
            border: Border(bottom: BorderSide(color: EjColors.line)),
          ),
          padding: const EdgeInsets.symmetric(vertical: 3, horizontal: 2),
          child: Row(
            children: [
              Avatar(f['avatar'] as String, size: 24),
              const SizedBox(width: 6),
              Expanded(
                child: Text(f['name'] as String, style: const TextStyle(fontSize: 12, color: EjColors.link)),
              ),
              Text(ago(f['time'] as int), style: const TextStyle(fontSize: 10)),
              const SizedBox(width: 8),
              Text(
                '${(f['damage'] as num) < 0 ? '-' : '+'}${fmt((f['damage'] as num).abs())}',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: (f['damage'] as num) < 0 ? EjColors.red : EjColors.green),
              ),
            ],
          ),
        ),
    ],
  );
}

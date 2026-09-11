import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'army.dart';
import 'article.dart';
import 'battle.dart';
import 'shell.dart';

/// The home hub in the gym skin: hero card (your citizen), today's missions, the war room,
/// the backpack, then the dispatches (events / news / Around eJahan / chatbox).
class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});
  @override
  ConsumerState<HomeScreen> createState() => _HomeState();
}

const _amberPill = Color(0xFFB9770E);

class _HomeState extends ConsumerState<HomeScreen> {
  int _dispatch = 0, _eventTab = 0, _newsTab = 0;
  bool _claiming = false;
  Timer? _tick;

  @override
  void initState() {
    super.initState();
    _tick = Timer.periodic(const Duration(seconds: 1), (_) {
      final battles = ref.read(homeProvider).asData?.value.battles ?? const [];
      if (battles.isNotEmpty && mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _tick?.cancel();
    super.dispose();
  }

  Future<void> _claim() async {
    setState(() => _claiming = true);
    try {
      final r = await ref.read(apiProvider).post('daily-reward');
      ref.read(sessionProvider.notifier).update(r);
      ref.invalidate(homeProvider);
      if (mounted) toast(context, 'You received 5 EP and one 5-star food!');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _claiming = false);
    }
  }

  void _openBattle(int id) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: id)));
  void _openArticle(int id) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ArticleScreen(id: id)));
  void _tab(int i) => ref.read(tabProvider.notifier).state = i;

  @override
  Widget build(BuildContext context) {
    final home = ref.watch(homeProvider);
    return LegacyFrame(
      dark: true,
      padding: const EdgeInsets.all(6),
      onRefresh: () => ref.refresh(homeProvider.future),
      children: [
        home.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(homeProvider)),
          data: (d) => Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (d.quests['vote'] != null) _voteHandler(d.quests['vote'] as Map<String, dynamic>),
              _hero(d.citizen),
              const SizedBox(height: 10),
              _missions(d),
              const SizedBox(height: 10),
              _warRoom(d),
              const SizedBox(height: 10),
              _backpack(d.citizen),
              const SizedBox(height: 10),
              _dispatches(d),
            ],
          ),
        ),
      ],
    );
  }

  Widget _voteHandler(Map<String, dynamic> v) => InkWell(
    onTap: () => launchUrl(Uri.parse(v['url'] as String), mode: LaunchMode.externalApplication),
    child: Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(8),
      decoration: Er.cardBox(border: Er.orange, glow: true, glowColor: Er.orange),
      child: Row(
        children: [
          legacy('tasks/vote.png', width: 30, height: 30),
          const SizedBox(width: 8),
          Expanded(child: Text('🗳️ Election day! Cast your vote in the ${(v['type'] as String).toUpperCase()} elections', style: const TextStyle(color: Er.text, fontWeight: FontWeight.bold, fontSize: 12))),
        ],
      ),
    ),
  );

  // ---- Hero: your citizen --------------------------------------------------------------

  Widget _hero(Citizen c) {
    final stage = ((c.strength + c.stamina) ~/ 2).clamp(0, kShapeMax);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: Er.cardBox(),
      child: Row(
        children: [
          if (!c.isCA)
            InkWell(
              onTap: () => _tab(1),
              child: Container(
                width: 110,
                height: 150,
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(10), gradient: RadialGradient(colors: [Er.accent.withValues(alpha: .18), Colors.transparent], radius: .8)),
                child: Image.asset('assets/legacy/gym/shape-$stage.png', fit: BoxFit.contain),
              ),
            ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(c.isCA ? 'Co-account' : c.shapeName.toUpperCase(), style: const TextStyle(color: Er.blue, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1.5)),
                Text(c.rank, style: const TextStyle(color: Er.text, fontSize: 15, fontWeight: FontWeight.w800)),
                Row(
                  children: [
                    netImg(c.countryFlag, width: 16, height: 11),
                    const SizedBox(width: 4),
                    Expanded(child: Text(c.regionName.isEmpty ? c.countryName : '${c.regionName}, ${c.countryName}', style: const TextStyle(color: Er.muted, fontSize: 11), overflow: TextOverflow.ellipsis)),
                  ],
                ),
                const SizedBox(height: 8),
                if (!c.isCA) ...[
                  _stat('🏋️', 'Strength', ShapeMeter(value: c.strength, color: Er.orange, height: 9), 'hit ${fmt(c.hit)}'),
                  const SizedBox(height: 4),
                  _stat('💓', 'Stamina', ShapeMeter(value: c.stamina, color: Er.blue, height: 9), '${fmt(c.fightCost)} / fight'),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: [
                      _pill(c.trainStreak > 0 ? '🔥 ${c.trainStreak} day streak' : '🌫️ no streak', c.trainStreak > 0 ? Er.orange : Er.muted),
                      _pill('🛠️ ${c.craftName.toLowerCase()} · craft ${c.craft}/$kShapeMax', _amberPill),
                      if (c.worldRank != null) _pill('🌍 #${c.worldRank}', Er.gold),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _stat(String emoji, String label, Widget meter, String effect) => Row(
    children: [
      Text(emoji, style: const TextStyle(fontSize: 12)),
      const SizedBox(width: 4),
      SizedBox(width: 54, child: Text(label, style: const TextStyle(color: Er.text, fontSize: 11, fontWeight: FontWeight.bold))),
      Expanded(child: meter),
      const SizedBox(width: 6),
      Text(effect, style: const TextStyle(color: Er.muted, fontSize: 10)),
    ],
  );

  Widget _pill(String text, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(10), border: Border.all(color: color.withValues(alpha: .5))),
    child: Text(text, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
  );

  // ---- Today's missions -------------------------------------------------------------------

  Widget _missions(HomeData d) {
    final c = d.citizen;
    final q = d.quests;
    final isCA = c.isCA;
    final missions = <_Mission>[
      if (!isCA) _Mission('Gym', '🏋️', c.trainedToday ? 'day ${c.trainStreak} in a row' : 'weights or cardio · day ${c.trainStreak + 1}', done: c.trainedToday, onGo: () => _tab(1)),
      if (!isCA) _Mission('Workshop', '🏭', c.workedToday ? 'day ${c.workStreak} in a row' : 'shift or study · day ${c.workStreak + 1}', done: c.workedToday, available: q['work'] == true || c.workedToday, onGo: () => _tab(2), hint: 'Find a job first', onHint: () => openWeb(ref, 'jobs-en.html')),
      if (!isCA && (q['explore'] == true || c.exploredToday)) _Mission('Explore the mines', '⛏️', 'find resources', done: c.exploredToday, onGo: () => openWeb(ref, 'mines-en.html')),
      if (!isCA) _Mission('Eat', '🍔', 'restore wellness', done: c.wellness >= 100, onGo: () => openWeb(ref, 'market-en.html')),
      if (d.unitBattle != null)
        _Mission('Unit order: ${d.unitBattle!.region}', '⚔️', 'fight with your military unit', onGo: () => _openBattle(d.unitBattle!.id))
      else if (d.battles.isNotEmpty)
        _Mission('Fight', '⚔️', d.battles.first.region, onGo: () => _openBattle(d.battles.first.id)),
    ];
    final total = missions.where((m) => m.available).length;
    final done = missions.where((m) => m.available && m.done).length;
    final chestOpen = q['dailyReward'] == true;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        GymTitle("Today's missions", trailing: total > 0 ? '$done / $total' : null),
        if (total > 0)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: Container(
                height: 8,
                color: Er.surface2,
                alignment: Alignment.centerLeft,
                child: FractionallySizedBox(widthFactor: total == 0 ? 0 : done / total, child: Container(decoration: BoxDecoration(color: Er.accentDark, boxShadow: [BoxShadow(color: Er.accentDark.withValues(alpha: .7), blurRadius: 6)]))),
              ),
            ),
          ),
        LayoutBuilder(
          builder: (context, box) {
            final cols = box.maxWidth >= 560 ? 3 : 2;
            final w = (box.maxWidth - 6 * (cols - 1)) / cols;
            return Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [for (final m in missions) SizedBox(width: w, height: 70, child: _MissionTile(m))],
            );
          },
        ),
        const SizedBox(height: 8),
        if (!isCA)
          Container(
            padding: const EdgeInsets.all(10),
            decoration: Er.cardBox(border: chestOpen ? Er.gold : null, glow: chestOpen, glowColor: Er.gold),
            child: Row(
              children: [
                Text(c.dailyClaimed ? '✅' : (chestOpen ? '🎁' : '🔒'), style: const TextStyle(fontSize: 30)),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(c.dailyClaimed ? 'Daily reward collected' : (chestOpen ? 'Daily reward unlocked!' : 'Daily reward'), style: TextStyle(fontWeight: FontWeight.w800, color: chestOpen ? Er.orange : Er.text, fontSize: 13)),
                      Row(
                        children: [
                          legacy('xp_icon.png', width: 14, height: 14),
                          const Text(' 5 EP  +  ', style: TextStyle(fontSize: 11, color: Er.muted)),
                          legacy('food-icon.png', width: 14, height: 14),
                          const Text(' 1 food ', style: TextStyle(fontSize: 11, color: Er.muted)),
                          legacy('5_star.gif', height: 8),
                        ],
                      ),
                      if (!chestOpen && !c.dailyClaimed) const Text('Train today to unlock it', style: TextStyle(fontSize: 10, color: Er.muted)),
                    ],
                  ),
                ),
                if (chestOpen) GlowButton('Claim', busy: _claiming, onPressed: _claim, color: Er.gold, small: true),
              ],
            ),
          ),
      ],
    );
  }

  // ---- War room ---------------------------------------------------------------------------

  Widget _warRoom(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      GymTitle('War room', trailing: 'All wars »', onTrailing: () => openWeb(ref, 'wars-en.html')),
      if (d.unitBattle != null) _BattleCard(d.unitBattle!, label: 'MILITARY UNIT ORDER', onTap: () => _openBattle(d.unitBattle!.id)),
      if (d.battles.isEmpty)
        Container(
          padding: const EdgeInsets.all(10),
          decoration: Er.cardBox(),
          child: Row(
            children: [
              const Text('🕊️', style: TextStyle(fontSize: 22)),
              const SizedBox(width: 8),
              const Expanded(child: Text('Your country is at peace — no active battle.', style: TextStyle(fontSize: 12, color: Er.text))),
              GlowButton('Battles', small: true, onPressed: () => _tab(3)),
            ],
          ),
        ),
      for (final b in d.battles) _BattleCard(b, onTap: () => _openBattle(b.id)),
    ],
  );

  // ---- Backpack ---------------------------------------------------------------------------

  Widget _backpack(Citizen c) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      GymTitle('Backpack', trailing: 'Market »', onTrailing: () => openWeb(ref, 'market-en.html')),
      Container(
        padding: const EdgeInsets.all(8),
        decoration: Er.cardBox(),
        child: c.inventory.isEmpty
            ? const Text('Your backpack is empty — buy food and weapons on the market.', style: TextStyle(fontSize: 12, color: Er.muted))
            : SizedBox(
                height: 78,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    for (final i in c.inventory)
                      Container(
                        width: 56,
                        margin: const EdgeInsets.only(right: 6),
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(color: Er.surface, borderRadius: BorderRadius.circular(10), border: Border.all(color: Er.line)),
                        child: Column(
                          children: [
                            Expanded(child: netImg(i.icon, fit: BoxFit.contain)),
                            legacy('${i.stars.clamp(0, 5)}_star.gif', width: 44, height: 8, fit: BoxFit.fill),
                            Text('×${i.amount}', style: const TextStyle(color: Er.text, fontSize: 10, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
      ),
    ],
  );

  // ---- Dispatches -------------------------------------------------------------------------

  Widget _dispatches(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      const GymTitle('Dispatches'),
      GymTabs(items: const ['⚔️ Events', '📰 News', '🌍 Around', '💬 Chat'], selected: _dispatch, onSelect: (i) => setState(() => _dispatch = i)),
      const SizedBox(height: 6),
      Container(
        padding: const EdgeInsets.all(8),
        decoration: Er.cardBox(),
        child: switch (_dispatch) {
          0 => _events(d),
          1 => _news(d),
          2 => _around(d),
          _ => const ChatBox(),
        },
      ),
    ],
  );

  Widget _events(HomeData d) {
    final list = _eventTab == 0 ? d.localEvents : d.worldEvents;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        GymTabs(items: [d.citizen.countryName, 'International'], selected: _eventTab, onSelect: (i) => setState(() => _eventTab = i), small: true),
        const SizedBox(height: 6),
        if (list.isEmpty) const Text('No military events yet.', style: TextStyle(fontSize: 12, color: Er.muted)),
        for (var i = 0; i < list.length; i++) ...[
          InkWell(
            onTap: () => launchUrl(Uri.parse(list[i].link), mode: LaunchMode.externalApplication),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 5),
              child: Row(
                children: [
                  netImg(list[i].icon, width: 20, height: 20, fit: BoxFit.contain),
                  const SizedBox(width: 8),
                  Expanded(child: Text(list[i].title, style: const TextStyle(fontSize: 12, color: Er.text))),
                ],
              ),
            ),
          ),
          if (i < list.length - 1) const Divider(height: 4, color: Er.line),
        ],
        Center(child: LinkText('Show all military events', onTap: () => openWeb(ref, 'media-0-eve-en.html'), color: Er.blue)),
      ],
    );
  }

  Widget _news(HomeData d) {
    final keys = ['top', 'latest', 'international', if (d.news.containsKey('subscriptions') && !d.citizen.isCA) 'subscriptions'];
    final labels = {'top': 'Top', 'latest': 'Latest', 'international': 'International', 'subscriptions': 'My subs'};
    final key = keys[_newsTab.clamp(0, keys.length - 1)];
    final list = d.news[key] ?? [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        GymTabs(items: keys.map((k) => labels[k]!).toList(), selected: _newsTab, onSelect: (i) => setState(() => _newsTab = i), small: true),
        const SizedBox(height: 6),
        if (list.isEmpty) const Text('There is no article to show.', style: TextStyle(fontSize: 12, color: Er.muted)),
        for (final a in list) _articleRow(a),
        Center(child: LinkText('Go to Media center', onTap: () => openWeb(ref, 'media-${d.citizen.countryId}-en.html'), color: Er.blue)),
      ],
    );
  }

  Widget _articleRow(Article a) => InkWell(
    onTap: () => _openArticle(a.id),
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36,
            height: 36,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: Er.accentDark.withValues(alpha: .15), borderRadius: BorderRadius.circular(8), border: Border.all(color: Er.accentDark.withValues(alpha: .5))),
            child: Text('${a.votes}', style: const TextStyle(color: Er.accentDark, fontSize: 13, fontWeight: FontWeight.w800)),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(a.title, style: const TextStyle(color: Er.text, fontSize: 12, fontWeight: FontWeight.bold), maxLines: 2, overflow: TextOverflow.ellipsis),
                Text('${ago(a.time)} · ${a.npName}', style: const TextStyle(color: Er.muted, fontSize: 10)),
              ],
            ),
          ),
        ],
      ),
    ),
  );

  Widget _around(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      for (final a in d.around)
        InkWell(
          onTap: () => _openArticle(a.id),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 5),
            child: Row(
              children: [
                const Text('📣', style: TextStyle(fontSize: 18)),
                const SizedBox(width: 8),
                if (a.isNew) const _Blink(child: Text('NEW ', style: TextStyle(color: Er.orange, fontSize: 12, fontWeight: FontWeight.w800))),
                Expanded(child: Text(a.title, style: const TextStyle(color: Er.text, fontSize: 12))),
              ],
            ),
          ),
        ),
    ],
  );
}

class _Mission {
  const _Mission(this.title, this.emoji, this.reward, {this.done = false, this.available = true, this.onGo, this.hint, this.onHint});
  final String title, emoji, reward;
  final bool done, available;
  final VoidCallback? onGo, onHint;
  final String? hint;
}

/// A mission tile: emoji badge, title, reward line; lit green when done, dimmed when locked.
class _MissionTile extends StatelessWidget {
  const _MissionTile(this.m);
  final _Mission m;
  @override
  Widget build(BuildContext context) {
    final locked = !m.available;
    return InkWell(
      onTap: m.done ? null : (locked ? m.onHint : m.onGo),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: Er.cardBox(border: m.done ? Er.accentDark : (locked ? Er.line : null), glow: m.done, glowColor: Er.accentDark),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: (m.done ? Er.accentDark : (locked ? Er.muted : Er.accent)).withValues(alpha: .15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(m.done ? '✅' : m.emoji, style: const TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(m.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: m.done ? Er.accentDark : Er.text)),
                  Text(locked ? (m.hint ?? 'Locked') : m.reward, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: Er.muted)),
                ],
              ),
            ),
            if (!m.done) Text(locked ? '🔒' : '›', style: TextStyle(color: locked ? Er.muted : Er.accent, fontSize: locked ? 14 : 22, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}

/// A battle card for the war room: flags, region, the wall bar and the countdown.
class _BattleCard extends StatelessWidget {
  const _BattleCard(this.b, {this.label, this.onTap});
  final Battle b;
  final String? label;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final wallPct = b.securePoint > 0 ? (b.wall / b.securePoint).clamp(0.0, 1.0) : 0.0;
    final closing = b.timeLeft.inMinutes < 10;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(10),
        decoration: Er.cardBox(border: label != null ? Er.red : null, glow: label != null, glowColor: Er.red),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (label != null) Text(label!, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Er.red, letterSpacing: 1.5)),
            Row(
              children: [
                ClipRRect(borderRadius: BorderRadius.circular(4), child: netImg(b.attackerFlag, width: 32, height: 22)),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(b.region, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Er.text)),
                      Text('${b.attacker} vs ${b.defender}', style: const TextStyle(fontSize: 10, color: Er.muted)),
                    ],
                  ),
                ),
                ClipRRect(borderRadius: BorderRadius.circular(4), child: netImg(b.defenderFlag, width: 32, height: 22)),
                const SizedBox(width: 10),
                Column(
                  children: [
                    Text(hms(b.timeLeft), style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: closing ? Er.red : Er.blue)),
                    GlowButton('Fight', small: true, onPressed: onTap),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 6),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: Container(
                height: 8,
                color: Er.surface2,
                alignment: Alignment.centerLeft,
                child: FractionallySizedBox(widthFactor: wallPct, child: Container(color: Er.orange)),
              ),
            ),
            Text('Wall ${fmt(b.wall)} / ${fmt(b.securePoint)}', style: const TextStyle(fontSize: 9, color: Er.muted)),
          ],
        ),
      ),
    );
  }
}

/// The `<blink>` tag, faithfully.
class _Blink extends StatefulWidget {
  const _Blink({required this.child});
  final Widget child;
  @override
  State<_Blink> createState() => _BlinkState();
}

class _BlinkState extends State<_Blink> {
  bool _on = true;
  Timer? _t;
  @override
  void initState() {
    super.initState();
    _t = Timer.periodic(const Duration(milliseconds: 700), (_) => setState(() => _on = !_on));
  }

  @override
  void dispose() {
    _t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Opacity(opacity: _on ? 1 : 0, child: widget.child);
}

/// The legacy home chatbox (column-right): textarea + Send, then the message list.
class ChatBox extends ConsumerStatefulWidget {
  const ChatBox({super.key});
  @override
  ConsumerState<ChatBox> createState() => _ChatState();
}

class _ChatState extends ConsumerState<ChatBox> {
  final _msg = TextEditingController();
  bool _busy = false;
  String? _status;
  bool _statusError = false;
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _poll = Timer.periodic(const Duration(seconds: 30), (_) => ref.invalidate(chatProvider));
  }

  @override
  void dispose() {
    _poll?.cancel();
    _msg.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    if (_msg.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      final r = await ref.read(apiProvider).post('chat', {'message': _msg.text});
      _msg.clear();
      setState(() {
        _status = r['message'] as String?;
        _statusError = false;
      });
      ref.invalidate(chatProvider);
    } on ApiException catch (e) {
      setState(() {
        _status = e.message;
        _statusError = true;
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final chat = ref.watch(chatProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          decoration: BoxDecoration(
            color: Er.card,
            border: Border.all(color: Er.line),
            borderRadius: BorderRadius.circular(5),
          ),
          padding: const EdgeInsets.all(4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: _msg,
                minLines: 2,
                maxLines: 3,
                style: const TextStyle(fontSize: 12),
                decoration: const InputDecoration(hintText: 'Your message…'),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  GlowButton('Send', busy: _busy, onPressed: _send, small: true),
                  const SizedBox(width: 8),
                  if (_status != null)
                    Expanded(
                      child: Text(_status!, style: TextStyle(fontSize: 11, color: _statusError ? Er.red : Er.accentDark)),
                    ),
                ],
              ),
              const Divider(color: Er.line, height: 8),
              chat.when(
                loading: () => const Loading(),
                error: (e, _) => Text('$e', style: const TextStyle(color: Er.red, fontSize: 11)),
                data: (d) {
                  final ann = d['announce'] as Map<String, dynamic>?;
                  final msgs = (d['messages'] as List).cast<Map<String, dynamic>>();
                  return Column(children: [if (ann != null) _row(ann, announce: true), if (msgs.isEmpty) const EmptyNote('No messages yet.'), for (final m in msgs) _row(m)]);
                },
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _row(Map<String, dynamic> m, {bool announce = false}) => Column(
    children: [
      Padding(
        padding: const EdgeInsets.symmetric(vertical: 3, horizontal: 2),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Avatar(m['avatar'] as String, size: 30),
            const SizedBox(width: 6),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text.rich(
                    TextSpan(
                      children: [
                        TextSpan(
                          text: '${m['name']}: ',
                          style: TextStyle(fontWeight: FontWeight.bold, color: announce ? Er.orange : Er.blue),
                        ),
                        TextSpan(text: m['message'] as String),
                      ],
                    ),
                    style: const TextStyle(fontSize: 11, color: Er.text),
                  ),
                  Text(ago(m['time'] as int), style: const TextStyle(fontSize: 9, color: Er.muted)),
                ],
              ),
            ),
          ],
        ),
      ),
      const Padding(padding: EdgeInsets.symmetric(horizontal: 14), child: Divider(height: 2, color: Er.line)),
    ],
  );
}

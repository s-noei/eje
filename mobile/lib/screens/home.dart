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

/// The home page as a game hub (legacy skin): today's missions, the war room, the citizen sheet,
/// the backpack, then the dispatches (military events / news / Around eJahan / chatbox) in tabs.
class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});
  @override
  ConsumerState<HomeScreen> createState() => _HomeState();
}

class _HomeState extends ConsumerState<HomeScreen> {
  int _dispatch = 0, _eventTab = 0, _newsTab = 0;
  bool _claiming = false;
  Timer? _tick;

  @override
  void initState() {
    super.initState();
    // battle countdowns
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
      onRefresh: () => ref.refresh(homeProvider.future),
      children: [
        home.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(homeProvider)),
          data: (d) => Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (d.quests['vote'] != null) _voteHandler(d.quests['vote'] as Map<String, dynamic>),
              _missions(d),
              const SizedBox(height: 10),
              _warRoom(d),
              const SizedBox(height: 10),
              _citizen(d.citizen),
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

  /// `.vote-handler` — the election-day banner.
  Widget _voteHandler(Map<String, dynamic> v) => InkWell(
    onTap: () => launchUrl(Uri.parse(v['url'] as String), mode: LaunchMode.externalApplication),
    child: Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(color: Colors.white, border: Border.all(color: EjColors.red), borderRadius: BorderRadius.circular(4)),
      child: Row(
        children: [
          legacy('tasks/vote.png', width: 30, height: 30),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'Election day! Cast your vote in the ${(v['type'] as String).toUpperCase()} elections',
              style: const TextStyle(color: EjColors.link, fontWeight: FontWeight.bold, fontSize: 12),
            ),
          ),
        ],
      ),
    ),
  );

  // ---- Today's missions -------------------------------------------------------------------

  Widget _missions(HomeData d) {
    final c = d.citizen;
    final q = d.quests;
    final isCA = c.isCA;
    final missions = <_Mission>[
      if (!isCA) _Mission('Train', 'train', c.trainedToday ? 'day ${c.trainStreak} in a row' : 'weights or cardio · day ${c.trainStreak + 1}', done: c.trainedToday, onGo: () => _tab(1)),
      if (!isCA) _Mission('Work', 'work', 'salary & products', done: c.workedToday, available: q['work'] == true || c.workedToday, onGo: () => _tab(2), hint: 'Find a job first', onHint: () => openWeb(ref, 'jobs-en.html')),
      if (!isCA && (q['explore'] == true || c.exploredToday)) _Mission('Explore the mines', 'explore', 'find resources', done: c.exploredToday, onGo: () => openWeb(ref, 'mines-en.html')),
      if (!isCA) _Mission('Eat', 'food', 'restore wellness', done: c.wellness >= 100, onGo: () => _tab(1)),
      if (d.unitBattle != null) _Mission('Unit order: ${d.unitBattle!.region}', 'damge-booster', 'fight with your military unit', onGo: () => _openBattle(d.unitBattle!.id))
      else if (d.battles.isNotEmpty) _Mission('Fight', 'damge-booster', d.battles.first.region, onGo: () => _openBattle(d.battles.first.id)),
    ];
    final total = missions.where((m) => m.available).length;
    final done = missions.where((m) => m.available && m.done).length;
    final chestOpen = q['dailyReward'] == true;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        BoxTitle(
          "Today's missions",
          trailing: Text('Day ${fmt(d.day)}', style: const TextStyle(fontSize: 11, color: EjColors.black)),
        ),
        if (total > 0)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(
              children: [
                Expanded(child: LegacyBar(value: total == 0 ? 0 : done / total, color: EjColors.lime, height: 12, label: '$done / $total completed')),
              ],
            ),
          ),
        LayoutBuilder(
          builder: (context, box) {
            final cols = box.maxWidth >= 560 ? 3 : 2;
            final w = (box.maxWidth - 4 * (cols - 1)) / cols;
            return Wrap(
              spacing: 4,
              runSpacing: 4,
              children: [for (final m in missions) SizedBox(width: w, height: 64, child: _MissionTile(m))],
            );
          },
        ),
        const SizedBox(height: 6),
        // daily reward chest
        if (!isCA)
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: chestOpen ? const Color(0xFFFFF7DD) : Colors.white,
              border: Border.all(color: chestOpen ? const Color(0xFFE0B000) : EjColors.line, width: chestOpen ? 2 : 1),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Row(
              children: [
                legacy(chestOpen ? 'tasks/welcome.png' : 'tasks/gold-pack.png', width: 44, height: 44),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        c.dailyClaimed ? 'Daily reward collected' : (chestOpen ? 'Daily reward unlocked!' : 'Daily reward'),
                        style: const TextStyle(fontWeight: FontWeight.bold, color: EjColors.black, fontSize: 12),
                      ),
                      Row(
                        children: [
                          legacy('xp_icon.png', width: 14, height: 14),
                          const Text(' 5 EP  +  ', style: TextStyle(fontSize: 11)),
                          legacy('food-icon.png', width: 14, height: 14),
                          const Text(' 1 food ', style: TextStyle(fontSize: 11)),
                          legacy('5_star.gif', height: 8),
                        ],
                      ),
                      if (!chestOpen && !c.dailyClaimed) const Text('Train today to unlock it', style: TextStyle(fontSize: 10)),
                    ],
                  ),
                ),
                if (chestOpen) ImgButton('Get reward', width: 110, busy: _claiming, onPressed: _claim),
                if (c.dailyClaimed) legacy('icon_ok.png', width: 26, height: 26),
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
      BoxTitle(
        'War room',
        trailing: LinkText('All wars »', onTap: () => openWeb(ref, 'wars-en.html')),
      ),
      if (d.unitBattle != null) _BattleCard(d.unitBattle!, label: 'MILITARY UNIT ORDER', onTap: () => _openBattle(d.unitBattle!.id)),
      if (d.battles.isEmpty)
        Row(
          children: [
            legacy('danger.png', width: 22, height: 22),
            const SizedBox(width: 6),
            const Expanded(child: Text('Your country is at peace — there is no active battle.', style: TextStyle(fontSize: 12))),
            ImgButton('Battles', width: 90, onPressed: () => _tab(3)),
          ],
        ),
      for (final b in d.battles) _BattleCard(b, onTap: () => _openBattle(b.id)),
    ],
  );

  // ---- Citizen sheet ----------------------------------------------------------------------

  Widget _citizen(Citizen c) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        BoxTitle(
          'Your citizen',
          trailing: LinkText('Profile »', onTap: () => openWeb(ref, 'profile-${c.id}-en.html')),
        ),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Avatar(c.avatar, size: 64),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(child: Text(c.name, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: EjColors.black))),
                      netImg(c.countryFlag, width: 20, height: 13),
                      const SizedBox(width: 4),
                      Text(c.regionName.isEmpty ? c.countryName : '${c.regionName}, ${c.countryName}', style: const TextStyle(fontSize: 11)),
                    ],
                  ),
                  Text(c.isCA ? 'Co-account' : c.rank, style: const TextStyle(fontSize: 11, color: EjColors.green, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  _statRow('Level ${c.level}', LegacyBar(value: c.xpProgress, color: EjColors.lime, height: 11, label: '${fmt(c.ep)} / ${fmt(c.epNextLevel)} EP')),
                  _statRow('Wellness', LegacyBar(value: c.wellness / 100, color: c.wellness < 40 ? EjColors.red : EjColors.lime, height: 11, label: '${fmt(c.wellness)} / 100')),
                  if (!c.isCA) ...[
                    _statRow('Strength', Row(children: [Expanded(child: ShapeMeter(value: c.strength, color: const Color(0xFFCC3333), height: 10)), Text(' ${c.strength}/$kShapeMax · hit ${fmt(c.hit)}', style: const TextStyle(fontSize: 10))])),
                    _statRow('Stamina', Row(children: [Expanded(child: ShapeMeter(value: c.stamina, color: const Color(0xFF3366CC), height: 10)), Text(' ${c.stamina}/$kShapeMax · fight ${fmt(c.fightCost)}', style: const TextStyle(fontSize: 10))])),
                  ],
                  Wrap(
                    spacing: 8,
                    children: [
                      if (!c.isCA) Text('${c.shapeName} · ${c.trainStreak} day${c.trainStreak == 1 ? '' : 's'} training streak', style: const TextStyle(fontSize: 11)),
                      Text('Work skill ${fmt(c.wSkill)}', style: const TextStyle(fontSize: 11)),
                      if (c.mRankIcon.isNotEmpty) Row(mainAxisSize: MainAxisSize.min, children: [netImg(c.mRankIcon, width: 36, height: 9, fit: BoxFit.contain), Text(' ${c.mRankName}', style: const TextStyle(fontSize: 11))]),
                      if (c.worldRank != null) Text('World rank #${c.worldRank}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: EjColors.black)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _statRow(String label, Widget bar) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 2),
    child: Row(
      children: [
        SizedBox(width: 62, child: Text(label, style: const TextStyle(fontSize: 11))),
        Expanded(child: bar),
      ],
    ),
  );

  // ---- Backpack ---------------------------------------------------------------------------

  Widget _backpack(Citizen c) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      BoxTitle(
        'Backpack',
        trailing: LinkText('Market »', onTap: () => openWeb(ref, 'market-en.html')),
      ),
      if (c.inventory.isEmpty) const EmptyNote('Your inventory is empty — buy food and weapons on the market.'),
      if (c.inventory.isNotEmpty)
        SizedBox(
          height: 74,
          child: ListView(
            scrollDirection: Axis.horizontal,
            children: [for (final i in c.inventory) InventoryBox(icon: i.icon, stars: i.stars, amount: i.amount)],
          ),
        ),
    ],
  );

  // ---- Dispatches -------------------------------------------------------------------------

  Widget _dispatches(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      const BoxTitle('Dispatches', hr: false),
      LegacyTabs(items: const ['Military events', 'News', 'Around eJahan', 'Chatbox'], selected: _dispatch, onSelect: (i) => setState(() => _dispatch = i)),
      const SizedBox(height: 4),
      switch (_dispatch) {
        0 => _events(d),
        1 => _news(d),
        2 => _around(d),
        _ => const ChatBox(),
      },
    ],
  );

  Widget _events(HomeData d) {
    final list = _eventTab == 0 ? d.localEvents : d.worldEvents;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            for (final (i, t) in [d.citizen.countryName, 'International'].indexed)
              Padding(
                padding: const EdgeInsets.only(right: 4),
                child: PlainButton(t, onPressed: () => setState(() => _eventTab = i), active: _eventTab == i),
              ),
          ],
        ),
        const SizedBox(height: 4),
        if (list.isEmpty) const EmptyNote('No military events yet.'),
        for (var i = 0; i < list.length; i++) ...[
          InkWell(
            onTap: () => launchUrl(Uri.parse(list[i].link), mode: LaunchMode.externalApplication),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 3),
              child: Row(
                children: [
                  netImg(list[i].icon, width: 20, height: 20, fit: BoxFit.contain),
                  const SizedBox(width: 6),
                  Expanded(child: Text(list[i].title, style: const TextStyle(fontSize: 11, color: EjColors.link))),
                ],
              ),
            ),
          ),
          if (i < list.length - 1) const Divider(height: 4),
        ],
        Center(child: LinkText('Show all military events', onTap: () => openWeb(ref, 'media-0-eve-en.html'))),
      ],
    );
  }

  Widget _news(HomeData d) {
    final keys = ['top', 'latest', 'international', if (d.news.containsKey('subscriptions') && !d.citizen.isCA) 'subscriptions'];
    final labels = {'top': 'Top', 'latest': 'Latest', 'international': 'International', 'subscriptions': 'My subscriptions'};
    final key = keys[_newsTab.clamp(0, keys.length - 1)];
    final list = d.news[key] ?? [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Wrap(
          spacing: 4,
          runSpacing: 4,
          children: [for (final (i, k) in keys.indexed) PlainButton(labels[k]!, onPressed: () => setState(() => _newsTab = i), active: _newsTab == i)],
        ),
        const SizedBox(height: 4),
        if (list.isEmpty) const EmptyNote('There is no article to show.'),
        for (final a in list) ArticleRow(a, onTap: () => _openArticle(a.id)),
        Center(child: LinkText('Go to Media center', onTap: () => openWeb(ref, 'media-${d.citizen.countryId}-en.html'))),
      ],
    );
  }

  Widget _around(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      for (final a in d.around)
        InkWell(
          onTap: () => _openArticle(a.id),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 3),
            child: Row(
              children: [
                legacy('logo.gif', width: 40, height: 40),
                const SizedBox(width: 6),
                if (a.isNew) const _Blink(child: Text('NEW ', style: TextStyle(color: EjColors.red, fontSize: 12, fontWeight: FontWeight.bold))),
                Expanded(child: Text(a.title, style: const TextStyle(color: EjColors.link, fontSize: 12))),
              ],
            ),
          ),
        ),
    ],
  );
}

class _Mission {
  const _Mission(this.title, this.icon, this.reward, {this.done = false, this.available = true, this.onGo, this.hint, this.onHint});
  final String title, icon, reward;
  final bool done, available;
  final VoidCallback? onGo, onHint;
  final String? hint;
}

/// A mission tile: task icon, title, reward line and a Go button (or the green check when done).
class _MissionTile extends StatelessWidget {
  const _MissionTile(this.m);
  final _Mission m;
  @override
  Widget build(BuildContext context) {
    final locked = !m.available;
    return InkWell(
      onTap: m.done ? null : (locked ? m.onHint : m.onGo),
      child: Container(
        padding: const EdgeInsets.all(5),
        decoration: BoxDecoration(
          color: m.done ? const Color(0xFFEFFAEF) : Colors.white,
          border: Border.all(color: m.done ? EjColors.green : EjColors.line),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Row(
          children: [
            Opacity(opacity: m.done || locked ? .5 : 1, child: legacy('tasks/${m.icon}.png', width: 40, height: 40)),
            const SizedBox(width: 5),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(m.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: m.done ? EjColors.green : EjColors.black)),
                  Text(locked ? (m.hint ?? 'Locked') : m.reward, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 9)),
                  const SizedBox(height: 3),
                  if (m.done)
                    Row(children: [legacy('icon_ok.png', width: 14, height: 14), const Text(' Done', style: TextStyle(fontSize: 10, color: EjColors.green, fontWeight: FontWeight.bold))])
                  else
                    ImgButton(locked ? 'Locked' : 'Go', width: 64, gray: locked, onPressed: locked ? m.onHint : m.onGo),
                ],
              ),
            ),
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
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: label != null ? const Color(0xFFFFF3F3) : Colors.white,
          border: Border.all(color: label != null ? EjColors.red : EjColors.line, width: label != null ? 2 : 1),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (label != null) Text(label!, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: EjColors.red, letterSpacing: 1)),
            Row(
              children: [
                netImg(b.attackerFlag, width: 30, height: 20),
                const SizedBox(width: 6),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(b.region, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black)),
                      Text('${b.attacker} vs ${b.defender}', style: const TextStyle(fontSize: 10)),
                    ],
                  ),
                ),
                netImg(b.defenderFlag, width: 30, height: 20),
                const SizedBox(width: 8),
                Column(
                  children: [
                    Text(hms(b.timeLeft), style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: closing ? EjColors.red : EjColors.black)),
                    ImgButton('Fight', width: 64, onPressed: onTap),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 4),
            Container(
              height: 10,
              decoration: BoxDecoration(border: Border.all(color: EjColors.black, width: .8), borderRadius: BorderRadius.circular(2), image: const DecorationImage(image: AssetImage('assets/legacy/defenders-bg.jpg'), fit: BoxFit.fill)),
              child: Align(
                alignment: Alignment.centerLeft,
                child: FractionallySizedBox(
                  widthFactor: wallPct,
                  child: Container(decoration: const BoxDecoration(image: DecorationImage(image: AssetImage('assets/legacy/attackers-bg.jpg'), fit: BoxFit.fill))),
                ),
              ),
            ),
            Text('Wall ${fmt(b.wall)} / ${fmt(b.securePoint)}', style: const TextStyle(fontSize: 9)),
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
        const BoxTitle('Chatbox'),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: EjColors.black),
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
                  ImgButton('Send', busy: _busy, onPressed: _send),
                  const SizedBox(width: 8),
                  if (_status != null)
                    Expanded(
                      child: Text(_status!, style: TextStyle(fontSize: 11, color: _statusError ? EjColors.red : EjColors.green)),
                    ),
                ],
              ),
              const Divider(color: EjColors.black, height: 8),
              chat.when(
                loading: () => const Loading(),
                error: (e, _) => Text('$e', style: const TextStyle(color: EjColors.red, fontSize: 11)),
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
                          style: TextStyle(fontWeight: FontWeight.bold, color: announce ? EjColors.red : EjColors.link),
                        ),
                        TextSpan(text: m['message'] as String),
                      ],
                    ),
                    style: const TextStyle(fontSize: 11, color: EjColors.black),
                  ),
                  Text(ago(m['time'] as int), style: const TextStyle(fontSize: 9, color: EjColors.text)),
                ],
              ),
            ),
          ],
        ),
      ),
      const Padding(padding: EdgeInsets.symmetric(horizontal: 14), child: Divider(height: 2)),
    ],
  );
}

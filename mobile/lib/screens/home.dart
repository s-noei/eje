import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'article.dart';
import 'battle.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key, required this.goTo});
  final void Function(int tab) goTo;
  @override
  ConsumerState<HomeScreen> createState() => _HomeState();
}

class _HomeState extends ConsumerState<HomeScreen> {
  int _eventTab = 0, _newsTab = 0;
  bool _claiming = false;

  Future<void> _claim() async {
    setState(() => _claiming = true);
    try {
      final r = await ref.read(apiProvider).post('daily-reward');
      ref.read(sessionProvider.notifier).update(r);
      ref.invalidate(homeProvider);
      if (mounted) toast(context, 'Reward claimed: +5 EP and a 5★ food!');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _claiming = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final home = ref.watch(homeProvider);
    return RefreshIndicator(
      onRefresh: () => ref.refresh(homeProvider.future),
      child: home.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(homeProvider)),
        data: (d) => ListView(padding: const EdgeInsets.fromLTRB(14, 8, 14, 24), children: [
          _hero(d.citizen),
          const SizedBox(height: 12),
          _quests(d),
          const SizedBox(height: 12),
          _battles(d),
          const SizedBox(height: 12),
          _events(d),
          const SizedBox(height: 12),
          _news(d),
          const SizedBox(height: 12),
          _around(d),
        ]),
      ),
    );
  }

  Widget _hero(Citizen c) => GlassCard(
        accent: EjColors.gold,
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Row(children: [
            Avatar(c.avatar, size: 68, progress: c.xpProgress, badge: c.isCA ? null : '${c.level}'),
            const SizedBox(width: 14),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(c.name, style: display(size: 24)),
                Wrap(crossAxisAlignment: WrapCrossAlignment.center, spacing: 6, children: [
                  Text(c.isCA ? 'Co-Account' : c.rank, style: const TextStyle(color: EjColors.muted, fontSize: 12)),
                  const Text('·', style: TextStyle(color: EjColors.muted)),
                  Image.network(c.countryFlag, height: 12, errorBuilder: (_, __, ___) => const SizedBox()),
                  Text('${c.countryName} · ${c.regionName}', style: const TextStyle(color: EjColors.muted, fontSize: 12)),
                ]),
              ]),
            ),
          ]),
          if (!c.isCA) ...[
            const SizedBox(height: 14),
            StatBar(label: 'XP', value: c.xpProgress, text: '${fmt(c.ep)} / ${fmt(c.epNextLevel)}'),
            const SizedBox(height: 8),
            StatBar(label: 'Wellness', value: c.wellness / 100, text: '${fmt(c.wellness)} / 100', colors: const [Color(0xFFF97316), EjColors.green]),
          ],
          const SizedBox(height: 12),
          Wrap(spacing: 8, runSpacing: 8, children: [
            Chip2(value: fmt(c.tala), label: 'Tala', image: c.money.where((m) => m.curID == 1).map((m) => m.icon).firstOrNull),
            if (c.local != null) Chip2(value: fmt(c.local!.amount), label: c.local!.name, image: c.local!.icon),
            if (c.worldRank != null) Chip2(value: '#${c.worldRank}', label: 'world rank', icon: Icons.star),
          ]),
        ]),
      );

  Widget _quests(HomeData d) {
    final q = d.quests;
    final items = <Widget>[];
    Widget quest(IconData icon, String title, String desc, Widget action, {bool gold = false}) => Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: gold ? EjColors.gold.withValues(alpha: .4) : EjColors.line),
            gradient: gold ? LinearGradient(colors: [EjColors.gold.withValues(alpha: .16), EjColors.accent.withValues(alpha: .14)]) : null,
            color: gold ? null : Colors.white.withValues(alpha: .05),
          ),
          child: Row(children: [
            Container(width: 42, height: 42, decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), gradient: LinearGradient(colors: [EjColors.accent.withValues(alpha: .35), EjColors.accent2.withValues(alpha: .25)])), child: Icon(icon, color: Colors.white)),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: const TextStyle(fontWeight: FontWeight.w700)), Text(desc, style: const TextStyle(color: EjColors.muted, fontSize: 12))])),
            action,
          ]),
        );
    if (q['vote'] != null) {
      final v = q['vote'] as Map<String, dynamic>;
      items.add(quest(Icons.how_to_vote, 'Election day!', 'Cast your ${(v['type'] as String).toUpperCase()} vote', EjButton('Vote', gold: true, icon: Icons.how_to_vote, onPressed: () => launchUrl(Uri.parse(v['url'] as String), mode: LaunchMode.externalApplication)), gold: true));
    }
    if (q['train'] == true) items.add(quest(Icons.fitness_center, 'Train', 'Daily training is available', EjButton('Go', ghost: true, icon: Icons.arrow_forward, onPressed: () => widget.goTo(1))));
    if (q['work'] == true) items.add(quest(Icons.work, 'Work', 'Your company is waiting', EjButton('Go', ghost: true, icon: Icons.arrow_forward, onPressed: () => widget.goTo(2))));
    if (q['dailyReward'] == true) items.add(quest(Icons.card_giftcard, 'Daily tasks completed', 'Claim +5 EP and a 5★ food', EjButton('Claim', busy: _claiming, icon: Icons.redeem, onPressed: _claim)));
    final ub = d.unitBattle;
    if (ub != null) items.add(quest(Icons.military_tech, 'Military unit order', 'Fight in ${ub.region}', EjButton('Battle', ghost: true, icon: Icons.arrow_forward, onPressed: () => _openBattle(ub.id))));
    if (items.isEmpty) items.add(const EmptyNote('All quests done for today. Come back tomorrow!'));
    return GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      SectionTitle('Quests', icon: Icons.bolt, trailing: Text('Day ${fmt(d.day)}', style: const TextStyle(color: EjColors.muted, fontSize: 12))),
      ...items,
    ]));
  }

  void _openBattle(int id) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: id)));

  Widget _battles(HomeData d) => GlassCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          SectionTitle('Active battles', icon: Icons.shield, trailing: d.battles.isEmpty ? null : _live(d.battles.length)),
          if (d.battles.isEmpty) const EmptyNote('There is no active battle for your country.'),
          for (final b in d.battles) BattleTile(b, onTap: () => _openBattle(b.id)),
        ]),
      );

  Widget _live(int n) => Row(mainAxisSize: MainAxisSize.min, children: [
        Container(width: 7, height: 7, decoration: const BoxDecoration(shape: BoxShape.circle, color: EjColors.red)),
        const SizedBox(width: 6),
        Text('LIVE · $n', style: const TextStyle(color: EjColors.red, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 1)),
      ]);

  Widget _events(HomeData d) {
    final list = _eventTab == 0 ? d.localEvents : d.worldEvents;
    return GlassCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const SectionTitle('Military events', icon: Icons.radar),
        Pills(items: [d.citizen.countryName, 'International'], selected: _eventTab, onSelect: (i) => setState(() => _eventTab = i)),
        const SizedBox(height: 8),
        if (list.isEmpty) const EmptyNote('No military events yet.'),
        for (final e in list)
          ListTile(
            dense: true, contentPadding: const EdgeInsets.symmetric(horizontal: 4),
            leading: Image.network(e.icon, width: 22, height: 22, errorBuilder: (_, __, ___) => const Icon(Icons.flag, size: 20)),
            title: Text(e.title, style: const TextStyle(fontSize: 13)),
            onTap: () => launchUrl(Uri.parse(e.link), mode: LaunchMode.externalApplication),
          ),
      ]),
    );
  }

  Widget _news(HomeData d) {
    final keys = ['top', 'latest', 'international', if (d.news.containsKey('subscriptions') && !d.citizen.isCA) 'subscriptions'];
    final labels = {'top': 'Top', 'latest': 'Latest', 'international': 'International', 'subscriptions': 'My subs'};
    final list = d.news[keys[_newsTab.clamp(0, keys.length - 1)]] ?? [];
    return GlassCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const SectionTitle("What's going on?!", icon: Icons.newspaper),
        Pills(items: keys.map((k) => labels[k]!).toList(), selected: _newsTab, onSelect: (i) => setState(() => _newsTab = i)),
        const SizedBox(height: 8),
        if (list.isEmpty) const EmptyNote('Nothing here yet.'),
        for (final a in list) ArticleTile(a),
      ]),
    );
  }

  Widget _around(HomeData d) => GlassCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          const SectionTitle('Around eJahan', icon: Icons.public),
          for (final a in d.around)
            ListTile(
              dense: true, contentPadding: EdgeInsets.zero,
              leading: const CircleAvatar(backgroundColor: Colors.white, child: Icon(Icons.campaign, color: EjColors.accent)),
              title: Row(children: [
                if (a.isNew) Container(margin: const EdgeInsets.only(right: 6), padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1), decoration: BoxDecoration(color: EjColors.red, borderRadius: BorderRadius.circular(6)), child: const Text('NEW', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800))),
                Expanded(child: Text(a.title, style: const TextStyle(fontSize: 13))),
              ]),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ArticleScreen(id: a.id))),
            ),
        ]),
      );
}

class BattleTile extends StatelessWidget {
  const BattleTile(this.b, {super.key, this.onTap});
  final Battle b;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), color: Colors.white.withValues(alpha: .05), border: Border.all(color: b.mine ? EjColors.accent2.withValues(alpha: .5) : EjColors.line)),
          child: Row(children: [
            _flag(b.attackerFlag),
            const SizedBox(width: 10),
            Expanded(child: Column(children: [
              Text(b.region, style: const TextStyle(fontWeight: FontWeight.w700), textAlign: TextAlign.center),
              Text('${b.attacker} vs ${b.defender}', style: const TextStyle(color: EjColors.muted, fontSize: 11), textAlign: TextAlign.center),
              Text('ends in ${hms(b.timeLeft)}', style: const TextStyle(color: EjColors.accent2, fontSize: 10)),
            ])),
            const SizedBox(width: 10),
            _flag(b.defenderFlag),
          ]),
        ),
      );
  Widget _flag(String url) => ClipRRect(borderRadius: BorderRadius.circular(4), child: Image.network(url, width: 34, height: 24, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox(width: 34, height: 24)));
}

class ArticleTile extends StatelessWidget {
  const ArticleTile(this.a, {super.key});
  final Article a;
  @override
  Widget build(BuildContext context) => InkWell(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ArticleScreen(id: a.id))),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Row(children: [
            Container(
              width: 44, height: 44, alignment: Alignment.center,
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: EjColors.line), gradient: LinearGradient(colors: [EjColors.accent.withValues(alpha: .5), EjColors.accent2.withValues(alpha: .35)])),
              child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Text('${a.votes}', style: display(size: 15)), const Text('VOTES', style: TextStyle(fontSize: 7, color: EjColors.muted, fontWeight: FontWeight.w700))]),
            ),
            const SizedBox(width: 10),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(a.title, style: const TextStyle(fontWeight: FontWeight.w600), maxLines: 2, overflow: TextOverflow.ellipsis),
              Text('${ago(a.time)} · in ${a.npName}', style: const TextStyle(color: EjColors.muted, fontSize: 11)),
            ])),
          ]),
        ),
      );
}

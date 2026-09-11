import 'dart:async';

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
import 'shell.dart';

/// The legacy home page: daily reward, active battles, military events, news tabs,
/// "Around eJahan" and the chatbox — with the hummy tasks / inventory sidebar.
class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});
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
      if (mounted) toast(context, 'You received 5 EP and one 5-star food!');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _claiming = false);
    }
  }

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
              SidebarLayout(
                content: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (d.quests['dailyReward'] == true) _daily(),
                    if (d.unitBattle != null) ...[const BoxTitle('Military Unit'), BattleRow(d.unitBattle!, onTap: () => _openBattle(d.unitBattle!.id)), const SizedBox(height: 8)],
                    const BoxTitle('Active battles for your country'),
                    if (d.battles.isEmpty) const EmptyNote('There is no active battle for your country.'),
                    for (final b in d.battles) BattleRow(b, onTap: () => _openBattle(b.id)),
                    const SizedBox(height: 8),
                    _events(d),
                    const SizedBox(height: 8),
                    _news(d),
                    const SizedBox(height: 8),
                    _around(d),
                    const SizedBox(height: 8),
                    const ChatBox(),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _openBattle(int id) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: id)));

  /// `.vote-handler` — the election-day banner.
  Widget _voteHandler(Map<String, dynamic> v) => InkWell(
    onTap: () => launchUrl(Uri.parse(v['url'] as String), mode: LaunchMode.externalApplication),
    child: Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: EjColors.line),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(
        children: [
          legacy('tasks/vote.png', width: 30, height: 30),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'Election day! - Cast your vote in the ${(v['type'] as String).toUpperCase()} elections',
              style: const TextStyle(color: EjColors.link, fontWeight: FontWeight.bold, fontSize: 12),
            ),
          ),
        ],
      ),
    ),
  );

  Widget _daily() => Column(
    children: [
      const BoxTitle('Daily tasks completed'),
      Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const InventoryBox(icon: 'assets/legacy/food-icon.png', stars: 5, amount: 1),
          Container(
            width: 44,
            margin: const EdgeInsets.all(1),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: EjColors.black),
              borderRadius: BorderRadius.circular(3),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                legacy('xp_icon.png', width: 40, height: 40),
                Container(
                  decoration: const BoxDecoration(
                    border: Border(
                      top: BorderSide(color: EjColors.black),
                      bottom: BorderSide(color: EjColors.black),
                    ),
                  ),
                  child: legacy('0_star.gif', width: 42, height: 9, fit: BoxFit.fill),
                ),
                const Text('5 EP', style: TextStyle(fontSize: 11, color: EjColors.black)),
              ],
            ),
          ),
        ],
      ),
      const SizedBox(height: 8),
      ImgButton('Get reward', busy: _claiming, onPressed: _claim),
      const SizedBox(height: 10),
    ],
  );

  Widget _events(HomeData d) {
    final list = _eventTab == 0 ? d.localEvents : d.worldEvents;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const BoxTitle('Military events', hr: false),
        LegacyTabs(items: [d.citizen.countryName, 'International'], selected: _eventTab, onSelect: (i) => setState(() => _eventTab = i)),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 5, horizontal: 2),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (list.isEmpty) const Text('No military events yet.', style: TextStyle(fontSize: 11)),
              for (var i = 0; i < list.length; i++) ...[
                InkWell(
                  onTap: () => launchUrl(Uri.parse(list[i].link), mode: LaunchMode.externalApplication),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 3),
                    child: Row(
                      children: [
                        netImg(list[i].icon, width: 20, height: 20, fit: BoxFit.contain),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(list[i].title, style: const TextStyle(fontSize: 11, color: EjColors.link)),
                        ),
                      ],
                    ),
                  ),
                ),
                if (i < list.length - 1) const Divider(height: 4),
              ],
            ],
          ),
        ),
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            LinkText('Show all military events', onTap: () => openWeb(ref, 'media-0-eve-en.html')),
            const Text('|', style: TextStyle(fontSize: 12)),
            LinkText('Show active wars', onTap: () => openWeb(ref, 'wars-en.html')),
          ],
        ),
      ],
    );
  }

  Widget _news(HomeData d) {
    final keys = ['top', 'latest', 'international', if (d.news.containsKey('subscriptions') && !d.citizen.isCA) 'subscriptions'];
    final labels = {'top': 'Top news', 'latest': 'Latest news', 'international': 'International', 'subscriptions': 'My subscriptions'};
    final key = keys[_newsTab.clamp(0, keys.length - 1)];
    final list = d.news[key] ?? [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const BoxTitle("What's going on?!", hr: false),
        LegacyTabs(items: keys.map((k) => labels[k]!).toList(), selected: _newsTab, onSelect: (i) => setState(() => _newsTab = i)),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                labels[key]!,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 18, color: EjColors.black),
              ),
              if (list.isEmpty) const EmptyNote('There is no article to show.'),
              for (final a in list) ArticleRow(a, onTap: () => _openArticle(a.id)),
            ],
          ),
        ),
        Center(child: LinkText('Go to Media center', onTap: () => openWeb(ref, 'media-${d.citizen.countryId}-en.html'))),
      ],
    );
  }

  void _openArticle(int id) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ArticleScreen(id: id)));

  Widget _around(HomeData d) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      const BoxTitle('Around eJahan'),
      for (final a in d.around)
        InkWell(
          onTap: () => _openArticle(a.id),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 3),
            child: Row(
              children: [
                legacy('logo.gif', width: 50, height: 50),
                const SizedBox(width: 6),
                if (a.isNew)
                  const _Blink(
                    child: Text(
                      'NEW ',
                      style: TextStyle(color: EjColors.red, fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                Expanded(
                  child: Text(a.title, style: const TextStyle(color: EjColors.link, fontSize: 12)),
                ),
              ],
            ),
          ),
        ),
    ],
  );
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

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'army.dart';
import 'article.dart';
import 'battle.dart';
import 'home.dart';
import 'mail.dart';
import 'work.dart';

/// Test builds (--dart-define=ALLOW_URL_TOKEN=true) on the web also accept ?tab=N&battle=ID&article=ID.
final _devQuery = kIsWeb && const bool.fromEnvironment('ALLOW_URL_TOKEN') ? Uri.base.queryParameters : const <String, String>{};

/// Current app screen: 0 Home · 1 Gym · 2 Workplace · 3 Battles · 4 Mail.
final tabProvider = StateProvider<int>((_) => int.tryParse(_devQuery['tab'] ?? '') ?? 0);

/// Which menubar group has its dock open (null = closed).
final menuOpenProvider = StateProvider<int?>((_) => int.tryParse(_devQuery['menu'] ?? ''));

/// The legacy menubar (Home · My places · Economy · Rankings · Information · Extra) and its docks.
/// Pages the app covers open natively; everything else opens the website page.
const kMenu = ['Home', 'My places', 'Economy', 'Rankings', 'Information', 'Extra'];

List<DockEntry> dockEntries(int group, Citizen? c) {
  final cid = c?.countryId ?? 0;
  final ca = c?.isCA ?? false;
  return switch (group) {
    0 => const [
      DockEntry('Main page', 'menu/home/mainpage.png', tab: 0),
      DockEntry('Messages', 'new_pm.png', tab: 4),
      DockEntry('Battles', 'menu/rankings/battle.png', tab: 3),
      DockEntry('Logout', 'menu/home/logout.png', logout: true),
    ],
    1 => [
      DockEntry('Profile', 'menu/myplaces/profile.png', path: 'profile-${c?.id ?? 0}-en.html'),
      const DockEntry('Company', 'menu/myplaces/company.png', path: 'company-en.html'),
      if (!ca) const DockEntry('Gym', 'tasks/train.png', tab: 1),
      if (!ca) const DockEntry('Workshop', 'tasks/work.png', tab: 2),
      if (!ca) const DockEntry('Explore Mines', 'menu/myplaces/madan.png', path: 'mines-en.html'),
      if (!ca) const DockEntry('Military Unit', 'tasks/damge-booster.png', path: 'military-unit-en.html'),
      const DockEntry('Newspaper', 'menu/myplaces/newspaper.png', path: 'newspaper-en.html'),
      if (!ca) const DockEntry('Party', 'menu/myplaces/party.png', path: 'party-en.html'),
      const DockEntry('Advertisement', 'menu/myplaces/ads.png', path: 'ads-en.html'),
    ],
    2 => const [
      DockEntry('Market', 'menu/economy/market.png', path: 'market-en.html'),
      DockEntry('Exchange Money', 'menu/economy/exchange.png', path: 'exchange-en.html'),
      DockEntry('Job Offers', 'menu/economy/jobs.png', path: 'jobs-en.html'),
      DockEntry('International Market', 'menu/economy/imarket.png', path: 'imarket-en.html'),
      DockEntry('Company Market', 'menu/economy/cmarket.png', path: 'company_market-en.html'),
    ],
    3 => const [
      DockEntry('Citizens', 'menu/rankings/citizen.png', path: 'ranking-citizens-1-0-en.html'),
      DockEntry('Countries', 'menu/rankings/country.png', path: 'ranking-countries-1-1-en.html'),
      DockEntry('Newspapers', 'menu/rankings/newspaper.png', path: 'ranking-newspapers-1-0-en.html'),
      DockEntry('Parties', 'menu/rankings/party.png', path: 'ranking-parties-1-0-en.html'),
      DockEntry('Battles', 'menu/rankings/battle.png', path: 'ranking-battles-1-0-en.html'),
    ],
    4 => [
      const DockEntry('Media Center', 'menu/information/mcenter.png', path: 'media-0-top-1-en.html'),
      DockEntry('Social Info', 'menu/information/social.png', path: 'country-$cid-en.html'),
      DockEntry('Economical Info', 'menu/information/economy.png', path: 'country-$cid-economy-en.html'),
      DockEntry('Political Info', 'menu/information/politics.png', path: 'country-$cid-politics-en.html'),
      DockEntry('Martial Info', 'menu/information/military.png', path: 'country-$cid-military-en.html'),
      DockEntry('View congress', 'menu/information/congress.png', path: 'congress-$cid-1-en.html'),
      const DockEntry('World map', 'menu/information/map.png', path: 'map-en.html'),
    ],
    _ => [
      const DockEntry('Elections', 'menu/extra/elections.png', path: 'elections-en.html'),
      if (!ca) const DockEntry('Invite', 'menu/extra/invite.png', path: 'invite-en.html'),
      if (!ca) const DockEntry('Chance boxes', 'tasks/gold-pack.png', path: 'chancebox-en.html'),
      const DockEntry('Daily Lottery', 'menu/extra/lottery.png', path: 'lottery-en.html'),
      const DockEntry('Special Items', 'menu/extra/rss.png', path: 'special-en.html'),
      const DockEntry('eJahan Store', 'menu/extra/store.png', path: 'ejstore-en.html'),
      const DockEntry('Forum', 'menu/extra/forum.png', path: 'forum-en.html'),
      const DockEntry('Contact', 'menu/extra/contact.png', path: 'contact-en.html'),
      const DockEntry('Wiki', 'menu/extra/wiki.png', url: 'http://wiki.ejahan.com'),
      const DockEntry('Credits', 'menu/extra/credits.png', path: 'extra-en.html'),
    ],
  };
}

/// Opens a page of the website (for parts of the game the app does not cover yet).
Future<void> openWeb(WidgetRef ref, String path) => launchUrl(Uri.parse('${ref.read(apiProvider).baseUrl}/$path'), mode: LaunchMode.externalApplication);

class Shell extends ConsumerStatefulWidget {
  const Shell({super.key});
  @override
  ConsumerState<Shell> createState() => _ShellState();
}

class _ShellState extends ConsumerState<Shell> {
  @override
  void initState() {
    super.initState();
    final battle = int.tryParse(_devQuery['battle'] ?? ''), article = int.tryParse(_devQuery['article'] ?? '');
    if (battle != null || article != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => battle != null ? BattleScreen(id: battle) : ArticleScreen(id: article!)));
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final tab = ref.watch(tabProvider);
    return Scaffold(
      body: Ambient(
        child: SafeArea(
          child: IndexedStack(index: tab, children: const [HomeScreen(), ArmyScreen(), WorkScreen(), BattlesScreen(), MailScreen()]),
        ),
      ),
    );
  }
}

/// The legacy page frame: citizen bar, logo header, menubar, then the white page with [children].
/// Everything scrolls together, as on the website.
class LegacyFrame extends ConsumerWidget {
  const LegacyFrame({super.key, required this.children, this.onRefresh, this.back = false, this.padding = const EdgeInsets.all(8), this.dark = false});
  final List<Widget> children;
  final Future<void> Function()? onRefresh;
  final bool back, dark;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final c = ref.watch(sessionProvider);
    final home = ref.watch(homeProvider).asData?.value;
    final open = ref.watch(menuOpenProvider);
    final list = ListView(
      padding: const EdgeInsets.only(bottom: 24),
      children: [
        if (c != null) CitizenBar(citizen: c, newPM: home?.newPM ?? 0, newNotes: home?.newNotes ?? 0, onLogout: () => ref.read(sessionProvider.notifier).logout(), onMail: () => _go(context, ref, 4)),
        LogoHeader(day: home?.day),
        LegacyMenuBar(
          items: kMenu,
          selected: open ?? -1,
          onSelect: (i) => ref.read(menuOpenProvider.notifier).state = open == i ? null : i,
          badges: {0: (home?.newPM ?? 0) > 0, 1: home?.quests['train'] == true || home?.quests['work'] == true},
        ),
        if (open != null)
          LegacyDock(
            entries: dockEntries(open, c),
            onTap: (e) {
              ref.read(menuOpenProvider.notifier).state = null;
              if (e.logout) {
                ref.read(sessionProvider.notifier).logout();
              } else if (e.tab != null) {
                _go(context, ref, e.tab!);
              } else if (e.url != null) {
                launchUrl(Uri.parse(e.url!), mode: LaunchMode.externalApplication);
              } else if (e.path != null) {
                openWeb(ref, e.path!);
              }
            },
          ),
        PagePanel(
          padding: padding,
          dark: dark,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (back)
                Align(
                  alignment: Alignment.centerLeft,
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: PlainButton('« Back', onPressed: () => Navigator.of(context).maybePop()),
                  ),
                ),
              ...children,
            ],
          ),
        ),
        const _Footer(),
      ],
    );
    final body = onRefresh == null ? list : RefreshIndicator(onRefresh: onRefresh!, child: list);
    return back
        ? Scaffold(
            body: Ambient(child: SafeArea(child: body)),
          )
        : body;
  }

  void _go(BuildContext context, WidgetRef ref, int i) {
    ref.read(tabProvider.notifier).state = i;
    Navigator.of(context).popUntil((r) => r.isFirst);
  }
}

class _Footer extends StatelessWidget {
  const _Footer();
  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.only(top: 10),
    child: Column(
      children: [
        Text(
          'Copyright © 2013 eJahan',
          style: TextStyle(
            fontSize: 10,
            color: Er.muted,
          ),
        ),
        Text(
          'Laws | Blog | Wiki | Forum | Contact | About',
          style: TextStyle(
            fontSize: 10,
            color: Er.muted,
          ),
        ),
      ],
    ),
  );
}

/// Left column of the legacy layout ("YOUR TASKS" baloons + hummy + "Your Inventory").
/// Wide screens get the real 120px column next to [content]; phones get horizontal strips above it.
class SidebarLayout extends ConsumerWidget {
  const SidebarLayout({super.key, required this.content});
  final Widget content;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final home = ref.watch(homeProvider).asData?.value;
    final c = ref.watch(sessionProvider);
    return LayoutBuilder(
      builder: (context, box) {
        final wide = box.maxWidth >= 640;
        final tasks = _tasks(context, ref, home, c);
        final inventory = c?.inventory ?? const <InventoryItem>[];
        if (wide) {
          return Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                width: 124,
                child: Column(
                  children: [
                    const Text('YOUR TASKS', style: TextStyle(color: EjColors.black, fontSize: 12)),
                    ...tasks,
                    legacy('hummy-hole.png', width: 100, height: 110),
                    const SizedBox(height: 4),
                    const SubHead('Your Inventory', center: true),
                    Wrap(
                      alignment: WrapAlignment.center,
                      children: [for (final i in inventory) InventoryBox(icon: i.icon, stars: i.stars, amount: i.amount)],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Expanded(child: content),
            ],
          );
        }
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                const Text('YOUR TASKS', style: TextStyle(color: EjColors.black, fontSize: 12)),
                const Spacer(),
                legacy('hummy-hole.png', height: 34),
              ],
            ),
            SizedBox(
              height: 54,
              child: ListView(scrollDirection: Axis.horizontal, children: tasks),
            ),
            const SizedBox(height: 4),
            if (inventory.isNotEmpty) ...[
              const SubHead('Your Inventory'),
              SizedBox(
                height: 74,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [for (final i in inventory) InventoryBox(icon: i.icon, stars: i.stars, amount: i.amount)],
                ),
              ),
            ],
            const SizedBox(height: 6),
            content,
          ],
        );
      },
    );
  }

  List<Widget> _tasks(BuildContext context, WidgetRef ref, HomeData? home, Citizen? c) {
    void tab(int i) => ref.read(tabProvider.notifier).state = i;
    final q = home?.quests ?? const {};
    final vote = q['vote'] as Map<String, dynamic>?;
    return [
      if (vote != null)
        TaskBaloon(
          icon: 'vote',
          title: 'Vote in ${(vote['type'] as String).toUpperCase()} elections',
          onTap: () => launchUrl(Uri.parse(vote['url'] as String), mode: LaunchMode.externalApplication),
        ),
      if (home?.unitBattle != null)
        TaskBaloon(
          icon: 'damge-booster',
          title: 'Military unit order',
          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BattleScreen(id: home!.unitBattle!.id))),
        ),
      if (q['dailyReward'] == true) TaskBaloon(icon: 'welcome', title: 'Get daily reward', onTap: () => tab(0)),
      if (q['train'] == true) TaskBaloon(icon: 'train', title: 'Go to the gym', onTap: () => tab(1)),
      if (q['work'] == true) TaskBaloon(icon: 'work', title: 'Go to the workshop', onTap: () => tab(2)),
      if (ref.watch(workProvider).asData?.value['employed'] == false) TaskBaloon(icon: 'job', title: 'Find a job', onTap: () => openWeb(ref, 'market-en.html')),
      TaskBaloon(icon: 'gold-pack', title: 'Buy Gold Pack', onTap: () => openWeb(ref, 'store-en.html')),
      TaskBaloon(icon: 'food', title: 'Consume food', onTap: () => tab(1)),
      if (q['explore'] == true) TaskBaloon(icon: 'explore', title: 'Explore the Mines', onTap: () => openWeb(ref, 'mines-en.html')),
      TaskBaloon(icon: 'market', title: 'Visit the market', onTap: () => openWeb(ref, 'market-en.html')),
    ];
  }
}

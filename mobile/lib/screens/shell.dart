import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'army.dart';
import 'battle.dart';
import 'home.dart';
import 'mail.dart';
import 'work.dart';

/// Bottom-navigation shell: Home · Train · Work · Battles · Mail.
class Shell extends ConsumerStatefulWidget {
  const Shell({super.key});
  @override
  ConsumerState<Shell> createState() => _ShellState();
}

class _ShellState extends ConsumerState<Shell> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final c = ref.watch(sessionProvider);
    final home = ref.watch(homeProvider).asData?.value;
    final pages = [HomeScreen(goTo: (i) => setState(() => _tab = i)), const ArmyScreen(), const WorkScreen(), const BattlesScreen(), const MailScreen()];
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: RadialGradient(center: Alignment(-.9, -1.1), radius: 1.3, colors: [Color(0xFF2A1F6B), EjColors.bg])),
        child: SafeArea(
          bottom: false,
          child: Column(children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 8, 4),
              child: Row(children: [
                Text('eJahan', style: display(size: 22, color: Colors.white)),
                const Spacer(),
                if (c != null) ...[
                  Chip2(value: fmt(c.tala), label: 'Tala', image: c.money.where((m) => m.curID == 1).map((m) => m.icon).firstOrNull),
                  const SizedBox(width: 6),
                  Chip2(value: '${fmt(c.wellness)}', label: '', icon: Icons.favorite),
                ],
                IconButton(
                  tooltip: 'Log out',
                  icon: const Icon(Icons.logout, color: EjColors.muted),
                  onPressed: () => ref.read(sessionProvider.notifier).logout(),
                ),
              ]),
            ),
            Expanded(child: IndexedStack(index: _tab, children: pages)),
          ]),
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: [
          const NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
          NavigationDestination(icon: _dot(Icons.fitness_center, home?.quests['train'] == true), label: 'Train'),
          NavigationDestination(icon: _dot(Icons.work_outline, home?.quests['work'] == true), label: 'Work'),
          NavigationDestination(icon: _dot(Icons.shield_outlined, (home?.battles.isNotEmpty) ?? false), label: 'Battles'),
          NavigationDestination(icon: _dot(Icons.mail_outline, (home?.newPM ?? 0) > 0), label: 'Mail'),
        ],
      ),
    );
  }

  Widget _dot(IconData icon, bool on) => Badge(isLabelVisible: on, backgroundColor: EjColors.gold, smallSize: 8, child: Icon(icon));
}

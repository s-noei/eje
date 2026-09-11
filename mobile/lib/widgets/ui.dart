import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../core/theme.dart';
import '../models/models.dart';

final _num = NumberFormat.decimalPattern();
String fmt(num n) => n == n.roundToDouble() ? _num.format(n) : n.toStringAsFixed(2);
String ago(int ts) {
  final d = DateTime.now().difference(DateTime.fromMillisecondsSinceEpoch(ts * 1000));
  if (d.inMinutes < 1) return 'just now';
  if (d.inHours < 1) return '${d.inMinutes} min ago';
  if (d.inDays < 1) return '${d.inHours} h ago';
  return '${d.inDays} d ago';
}

String hms(Duration d) => '${d.inHours.toString().padLeft(2, '0')}:${(d.inMinutes % 60).toString().padLeft(2, '0')}:${(d.inSeconds % 60).toString().padLeft(2, '0')}';
String stripHtml(String html) => html
    .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
    .replaceAll(RegExp(r'</p>', caseSensitive: false), '\n\n')
    .replaceAll(RegExp(r'<[^>]+>'), '')
    .replaceAll('&nbsp;', ' ')
    .replaceAll('&amp;', '&')
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>')
    .replaceAll('&quot;', '"')
    .replaceAll('&#039;', "'")
    .replaceAll(RegExp(r'\n{3,}'), '\n\n')
    .trim();

/// Bundled legacy image (mobile/assets/legacy/*).
Image legacy(String name, {double? width, double? height, BoxFit? fit}) => Image.asset('assets/legacy/$name', width: width, height: height, fit: fit, filterQuality: FilterQuality.medium);

Widget netImg(String url, {double? width, double? height, BoxFit fit = BoxFit.cover}) => Image.network(
  url,
  width: width,
  height: height,
  fit: fit,
  errorBuilder: (_, __, ___) => SizedBox(width: width, height: height),
);

/// The page background (light grey, eRepublik-like).
class Ambient extends StatelessWidget {
  const Ambient({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => ColoredBox(color: Er.bg, child: child);
}

/// A content card on the dark shell (white so legacy-style content stays readable).
class PagePanel extends StatelessWidget {
  const PagePanel({super.key, required this.child, this.padding = const EdgeInsets.all(8), this.dark = false});
  final Widget child;
  final EdgeInsets padding;
  final bool dark;
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.symmetric(horizontal: 8),
    padding: padding,
    decoration: dark ? BoxDecoration(color: Colors.transparent, borderRadius: BorderRadius.circular(8)) : Er.cardBox(),
    child: child,
  );
}

/// Square legacy avatar with a thin border.
class Avatar extends StatelessWidget {
  const Avatar(this.url, {super.key, this.size = 40});
  final String url;
  final double size;
  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      color: Colors.white,
      border: Border.all(color: EjColors.line),
    ),
    padding: const EdgeInsets.all(1),
    child: netImg(url, width: size, height: size),
  );
}

/// One dark-blue rounded box from the citizen info bar (#citInfo).
class InfoBox extends StatelessWidget {
  const InfoBox({super.key, required this.child, this.padding = const EdgeInsets.symmetric(horizontal: 6, vertical: 4), this.onTap});
  final Widget child;
  final EdgeInsets padding;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final box = Container(
      padding: padding,
      decoration: BoxDecoration(
        color: EjColors.infoBox,
        borderRadius: BorderRadius.circular(5),
        border: Border.all(color: EjColors.infoBorder),
        boxShadow: const [BoxShadow(color: Color(0x33000000), blurRadius: 3, offset: Offset(0, 1))],
      ),
      child: DefaultTextStyle(
        style: const TextStyle(color: EjColors.infoText, fontSize: 11, fontFamily: ejFontFamily),
        child: child,
      ),
    );
    return onTap == null ? box : InkWell(onTap: onTap, borderRadius: BorderRadius.circular(5), child: box);
  }
}

/// The player HUD: avatar with the level ring, name + rank insignia, XP and wellness bars,
/// money chips, mail badge and logout.
class CitizenBar extends StatelessWidget {
  const CitizenBar({super.key, required this.citizen, this.newPM = 0, this.newNotes = 0, this.onLogout, this.onMail});
  final Citizen citizen;
  final int newPM, newNotes;
  final VoidCallback? onLogout, onMail;
  @override
  Widget build(BuildContext context) {
    final c = citizen;
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 8),
      decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Er.header2, Er.header])),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                padding: const EdgeInsets.all(3),
                decoration: BoxDecoration(shape: BoxShape.circle, gradient: SweepGradient(colors: [Er.accent, Er.accent, Colors.white24, Colors.white24], stops: [0, c.xpProgress, c.xpProgress, 1])),
                child: ClipOval(child: SizedBox(width: 56, height: 56, child: netImg(c.avatar, width: 56, height: 56))),
              ),
              if (!c.isCA)
                Positioned(
                  right: -6,
                  bottom: -4,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: Er.accent, borderRadius: BorderRadius.circular(10), border: Border.all(color: Er.header, width: 2)),
                    child: Text('${c.level}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w900)),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(child: Text(c.name, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800), overflow: TextOverflow.ellipsis)),
                    if (c.mRankIcon.isNotEmpty) netImg(c.mRankIcon, width: 40, height: 10, fit: BoxFit.contain),
                    const SizedBox(width: 6),
                    netImg(c.countryFlag, width: 20, height: 13),
                  ],
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(child: _bar('XP', c.isCA ? 0 : c.xpProgress, Er.accent, '${fmt(c.ep)} / ${fmt(c.epNextLevel)}')),
                    const SizedBox(width: 8),
                    Expanded(child: _bar('❤', c.wellness / 100, c.wellness < 40 ? Er.red : Er.accentDark, fmt(c.wellness))),
                  ],
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: [
                            _chip('🪙', fmt(c.tala)),
                            const SizedBox(width: 6),
                            if (c.local != null) _chip(null, '${fmt(c.local!.amount)} ${c.local!.name}', flag: c.countryFlag),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    _iconBtn('✉️', newPM, onMail),
                    const SizedBox(width: 4),
                    _iconBtn('⏻', 0, onLogout),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bar(String label, double v, Color color, String text) => Row(
    children: [
      Text(label, style: const TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.bold)),
      const SizedBox(width: 4),
      Expanded(
        child: Stack(
          alignment: Alignment.center,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: Container(
                height: 12,
                color: Colors.white.withValues(alpha: .1),
                alignment: Alignment.centerLeft,
                child: FractionallySizedBox(
                  widthFactor: v.clamp(0, 1).toDouble(),
                  child: Container(decoration: BoxDecoration(color: color, boxShadow: [BoxShadow(color: color.withValues(alpha: .7), blurRadius: 6)])),
                ),
              ),
            ),
            Text(text, style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold, shadows: [Shadow(color: Colors.black, blurRadius: 3)])),
          ],
        ),
      ),
    ],
  );

  Widget _chip(String? emoji, String text, {String? flag}) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(color: Colors.white.withValues(alpha: .12), borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.white24)),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (emoji != null) Text(emoji, style: const TextStyle(fontSize: 11)),
        if (flag != null) netImg(flag, width: 14, height: 10),
        const SizedBox(width: 4),
        Text(text, style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
      ],
    ),
  );

  Widget _iconBtn(String emoji, int badge, VoidCallback? onTap) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(16),
    child: Stack(
      clipBehavior: Clip.none,
      children: [
        Container(
          width: 30,
          height: 26,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: Colors.white.withValues(alpha: .12), borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.white24)),
          child: Text(emoji, style: const TextStyle(fontSize: 13, color: Colors.white)),
        ),
        if (badge > 0)
          Positioned(
            right: -5,
            top: -5,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
              decoration: BoxDecoration(color: Er.red, borderRadius: BorderRadius.circular(8)),
              child: Text('$badge', style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold)),
            ),
          ),
      ],
    ),
  );
}

/// Wordmark + "Day N" + clock.
class LogoHeader extends StatelessWidget {
  const LogoHeader({super.key, this.day});
  final int? day;
  @override
  Widget build(BuildContext context) => Container(
    color: Er.header,
    padding: const EdgeInsets.fromLTRB(14, 0, 14, 6),
    child: Row(
      children: [
        const Text('eJahan', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w900, letterSpacing: -.5)),
        const SizedBox(width: 6),
        const Expanded(child: Text('the reality of your dreams', style: TextStyle(color: Colors.white54, fontSize: 10), overflow: TextOverflow.ellipsis)),
        if (day != null)
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('DAY ${fmt(day!)}', style: const TextStyle(color: Er.accent, fontWeight: FontWeight.w800, fontSize: 11, letterSpacing: 1)),
              _Clock(),
            ],
          ),
      ],
    ),
  );
}

class _Clock extends StatefulWidget {
  @override
  State<_Clock> createState() => _ClockState();
}

class _ClockState extends State<_Clock> {
  @override
  Widget build(BuildContext context) => StreamBuilder<DateTime>(
    stream: Stream.periodic(const Duration(seconds: 1), (_) => DateTime.now()),
    initialData: DateTime.now(),
    builder: (_, s) => Text(
      DateFormat('HH:mm:ss').format(s.data!),
      style: const TextStyle(
        color: Colors.white,
        fontWeight: FontWeight.bold,
        fontSize: 12,
        shadows: [Shadow(color: Colors.black45, blurRadius: 3)],
      ),
    ),
  );
}

/// The menubar: dark pill row, the open group lit in cyan.
class LegacyMenuBar extends StatelessWidget {
  const LegacyMenuBar({super.key, required this.items, required this.selected, required this.onSelect, this.badges = const {}});
  final List<String> items;
  final int selected;
  final ValueChanged<int> onSelect;
  final Map<int, bool> badges;
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 8),
    height: 40,
    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
    decoration: const BoxDecoration(color: Er.header, border: Border(bottom: BorderSide(color: Er.accent, width: 3))),
    child: Row(
      children: [
        for (var i = 0; i < items.length; i++)
          Expanded(
            child: InkWell(
              onTap: () => onSelect(i),
              borderRadius: BorderRadius.circular(9),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: i == selected ? Er.accent : Colors.transparent,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(items[i], maxLines: 1, style: TextStyle(color: i == selected ? Colors.white : Colors.white70, fontSize: 12, fontWeight: FontWeight.w700)),
                    ),
                    if (badges[i] == true) Positioned(right: -8, top: -3, child: Container(width: 6, height: 6, decoration: BoxDecoration(color: Er.orange, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Er.orange.withValues(alpha: .8), blurRadius: 4)]))),
                  ],
                ),
              ),
            ),
          ),
      ],
    ),
  );
}

/// One "YOUR TASKS" baloon: task icon + green bold title.
class TaskBaloon extends StatelessWidget {
  const TaskBaloon({super.key, required this.icon, required this.title, this.onTap, this.width = 118});
  final String icon, title;
  final VoidCallback? onTap;
  final double width;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Container(
      width: width,
      height: 50,
      margin: const EdgeInsets.symmetric(vertical: 1, horizontal: 1),
      padding: const EdgeInsets.all(1),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: EjColors.line),
        borderRadius: BorderRadius.circular(3),
      ),
      child: Row(
        children: [
          legacy('tasks/$icon.png', width: 44, height: 44),
          Expanded(
            child: Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(color: EjColors.green, fontWeight: FontWeight.bold, fontSize: 11, height: 1.1),
            ),
          ),
        ],
      ),
    ),
  );
}

/// Inventory box (icon + N_star + amount), as in the left sidebar.
class InventoryBox extends StatelessWidget {
  const InventoryBox({super.key, required this.icon, required this.stars, required this.amount, this.selectable = false, this.selected = false, this.onTap});
  final String icon;
  final int stars, amount;
  final bool selectable, selected;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Container(
      width: 44,
      margin: const EdgeInsets.all(1),
      decoration: BoxDecoration(
        color: selected ? const Color(0xFFE6F3FF) : Colors.white,
        border: Border.all(color: selected ? EjColors.link : EjColors.black, width: selected ? 2 : 1),
        borderRadius: BorderRadius.circular(3),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          icon.startsWith('http') ? netImg(icon, width: 40, height: 40, fit: BoxFit.contain) : Image.asset(icon, width: 40, height: 40, fit: BoxFit.contain),
          Container(
            decoration: const BoxDecoration(
              border: Border(
                top: BorderSide(color: EjColors.black),
                bottom: BorderSide(color: EjColors.black),
              ),
            ),
            child: legacy('${stars.clamp(0, 5)}_star.gif', width: 42, height: 9, fit: BoxFit.fill),
          ),
          Text('$amount', style: const TextStyle(fontSize: 11, color: EjColors.black)),
        ],
      ),
    ),
  );
}

/// `.home-box-title`: script heading followed by the hairline.
class BoxTitle extends StatelessWidget {
  const BoxTitle(this.title, {super.key, this.hr = true, this.trailing});
  final String title;
  final bool hr;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(top: 6, bottom: 4),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(child: Text(title, style: boxTitle())),
            if (trailing != null) trailing!,
          ],
        ),
        if (hr) const Divider(height: 6, color: EjColors.black, thickness: .5),
      ],
    ),
  );
}

/// Bold 10pt sub-heading with the hairline (e.g. "Your military stats").
class SubHead extends StatelessWidget {
  const SubHead(this.title, {super.key, this.center = false});
  final String title;
  final bool center;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        title,
        textAlign: center ? TextAlign.center : TextAlign.start,
        style: const TextStyle(fontWeight: FontWeight.bold, color: EjColors.black, fontSize: 13),
      ),
      const Divider(height: 8, color: EjColors.line),
    ],
  );
}

/// Blue/red/gray 150×20 image buttons (`.submit-blue-1`, `.submit-red-1`).
class ImgButton extends StatelessWidget {
  const ImgButton(this.label, {super.key, this.onPressed, this.red = false, this.gray = false, this.busy = false, this.width = 150});
  final String label;
  final VoidCallback? onPressed;
  final bool red, gray, busy;
  final double width;
  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || busy;
    final img = gray || disabled ? 'button-gray-1.png' : (red ? 'button-red-1.png' : 'button-blue-1.png');
    return InkWell(
      onTap: disabled ? null : onPressed,
      child: Container(
        width: width,
        height: 20,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          image: DecorationImage(image: AssetImage('assets/legacy/$img'), fit: BoxFit.fill),
          borderRadius: BorderRadius.circular(3),
        ),
        child: busy
            ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2, color: EjColors.black))
            : Text(
                label,
                style: const TextStyle(color: EjColors.black, fontSize: 11, fontWeight: FontWeight.bold),
              ),
      ),
    );
  }
}

/// Grey legacy `<input type=submit>` (e.g. "Show active wars").
class PlainButton extends StatelessWidget {
  const PlainButton(this.label, {super.key, this.onPressed, this.active = false});
  final String label;
  final VoidCallback? onPressed;
  final bool active;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onPressed,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
      decoration: BoxDecoration(color: active ? const Color(0xFF7FA8C9) : const Color(0xFFDDEEFF), border: Border.all(color: const Color(0xFF7FA8C9)), borderRadius: BorderRadius.circular(2)),
          child: Text(label, style: TextStyle(color: active ? Colors.white : EjColors.black, fontSize: 12, fontWeight: active ? FontWeight.bold : FontWeight.normal)),
    ),
  );
}

/// Green (info) / red (error) notice with the check / danger icon (`.infHandle` / `.errHandle`).
class Notice extends StatelessWidget {
  const Notice(this.text, {super.key, this.error = false, this.sub});
  final String text;
  final String? sub;
  final bool error;
  @override
  Widget build(BuildContext context) {
    final color = error ? EjColors.red : EjColors.green;
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 6),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: color),
        borderRadius: BorderRadius.circular(5),
      ),
      child: Row(
        children: [
          legacy(error ? 'danger.png' : 'icon_ok.png', width: 26, height: 26),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  text,
                  style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12),
                ),
                if (sub != null)
                  Text(
                    sub!,
                    style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// `.nButtons` — the rounded-top tab buttons used for events / news.
class LegacyTabs extends StatelessWidget {
  const LegacyTabs({super.key, required this.items, required this.selected, required this.onSelect});
  final List<String> items;
  final int selected;
  final ValueChanged<int> onSelect;
  @override
  Widget build(BuildContext context) => Container(
    decoration: const BoxDecoration(
      border: Border(bottom: BorderSide(color: EjColors.line)),
    ),
    padding: const EdgeInsets.symmetric(horizontal: 6),
    child: SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          for (var i = 0; i < items.length; i++)
            InkWell(
              onTap: () => onSelect(i),
              child: Container(
                margin: const EdgeInsets.only(right: 3, top: 3),
                padding: const EdgeInsets.fromLTRB(9, 4, 9, 4),
                decoration: BoxDecoration(
                  color: i == selected ? Colors.white : null,
                  gradient: i == selected ? null : const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFCDE7F9), Color(0xFFEFF7FD)]),
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                  border: Border.all(color: i == selected ? EjColors.line : const Color(0xFFCCDDEE)),
                  boxShadow: i == selected ? const [BoxShadow(color: Color(0xFFCCCCCC), blurRadius: 3)] : null,
                ),
                child: Text(items[i], style: TextStyle(fontSize: 12, color: i == selected ? EjColors.black : EjColors.link)),
              ),
            ),
        ],
      ),
    ),
  );
}

/// Thin legacy progress bar (military skill / wellness): 1px border, flat fill.
class LegacyBar extends StatelessWidget {
  const LegacyBar({super.key, required this.value, this.color = EjColors.lime, this.height = 8, this.label, this.dark = false});
  final double value;
  final Color color;
  final double height;
  final String? label;
  final bool dark;
  @override
  Widget build(BuildContext context) => Container(
    height: height,
    decoration: BoxDecoration(
      color: dark ? const Color(0xFF1B3050) : Colors.white,
      border: Border.all(color: dark ? const Color(0xFF9FB6D6) : EjColors.black, width: .8),
      borderRadius: BorderRadius.circular(2),
    ),
    child: Stack(
      children: [
        FractionallySizedBox(
          widthFactor: value.clamp(0, 1).toDouble(),
          child: Container(
            decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(1.5)),
          ),
        ),
        if (label != null)
          Center(
            child: Text(
              label!,
              style: TextStyle(fontSize: height - 2, height: 1, color: dark ? Colors.white : EjColors.black, fontWeight: FontWeight.bold),
            ),
          ),
      ],
    ),
  );
}

/// `.battles-holder` row — the battles-bg sprite with two flags and the region name.
class BattleRow extends StatelessWidget {
  const BattleRow(this.b, {super.key, this.onTap});
  final Battle b;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Container(
      height: 40,
      margin: const EdgeInsets.symmetric(vertical: 2),
      decoration: const BoxDecoration(
        image: DecorationImage(image: AssetImage('assets/legacy/battles-bg.png'), fit: BoxFit.fill, alignment: Alignment.topCenter),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 7),
      child: Row(
        children: [
          netImg(b.attackerFlag, width: 26, height: 18),
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  b.region,
                  style: const TextStyle(color: EjColors.black, fontSize: 12),
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  '${b.attacker} vs ${b.defender} · ${hms(b.timeLeft)}',
                  style: const TextStyle(color: EjColors.text, fontSize: 9),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          netImg(b.defenderFlag, width: 26, height: 18),
        ],
      ),
    ),
  );
}

/// Legacy news row: points badge (points-bg.gif) + title + "wrote X ago" + "in Newspaper".
class ArticleRow extends StatelessWidget {
  const ArticleRow(this.a, {super.key, this.onTap});
  final Article a;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 45,
            alignment: const Alignment(0, .3),
            decoration: const BoxDecoration(
              image: DecorationImage(image: AssetImage('assets/legacy/points-bg.gif'), fit: BoxFit.none, alignment: Alignment.topCenter),
            ),
            child: Text(
              '${a.votes}',
              style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold),
            ),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  a.title,
                  style: const TextStyle(color: EjColors.link, fontSize: 12, fontWeight: FontWeight.bold),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                Text('wrote ${ago(a.time)}', style: const TextStyle(fontSize: 10)),
                Text.rich(
                  TextSpan(
                    children: [
                      const TextSpan(text: 'in '),
                      TextSpan(
                        text: a.npName,
                        style: const TextStyle(color: EjColors.link),
                      ),
                    ],
                  ),
                  style: const TextStyle(fontSize: 10),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

/// Underlined-on-hover blue link text, like legacy anchors.
class LinkText extends StatelessWidget {
  const LinkText(this.text, {super.key, this.onTap, this.size = 12, this.color = EjColors.link});
  final String text;
  final VoidCallback? onTap;
  final double size;
  final Color color;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 2),
      child: Text(
        text,
        style: TextStyle(color: EjColors.link, fontSize: size),
      ),
    ),
  );
}

class EmptyNote extends StatelessWidget {
  const EmptyNote(this.text, {super.key});
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
    child: Text(text, style: const TextStyle(fontSize: 12)),
  );
}

class ErrorNote extends StatelessWidget {
  const ErrorNote(this.error, {super.key, this.onRetry});
  final Object error;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.all(12),
    children: [
      Notice('$error', error: true),
      if (onRetry != null) Center(child: ImgButton('Try again', onPressed: onRetry)),
    ],
  );
}

class Loading extends StatelessWidget {
  const Loading({super.key});
  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.all(24),
    child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))),
  );
}

void toast(BuildContext context, String msg, {bool error = false}) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg), backgroundColor: error ? EjColors.red : EjColors.green));
}

/// Food picker used by train/work — inventory boxes; tap to add one, long-press to remove.
class FoodPicker extends StatelessWidget {
  const FoodPicker({super.key, required this.foods, required this.selected, required this.onChanged, required this.maxRecover, this.foodIcon, this.dark = false});
  final Map<String, dynamic> foods;
  final Map<int, int> selected;
  final ValueChanged<Map<int, int>> onChanged;
  final int maxRecover;
  final String? foodIcon;
  /// Light text for the dark gym panel.
  final bool dark;
  @override
  Widget build(BuildContext context) {
    final recover = selected.entries.fold<int>(0, (a, e) => a + e.key * e.value);
    final any = [
      for (var s = 1; s <= 5; s++)
        if ((foods['$s'] ?? 0) > 0) s,
    ].isNotEmpty;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        dark
            ? const Padding(padding: EdgeInsets.only(bottom: 6), child: Text('🍔 Eat during the session', style: TextStyle(color: Color(0xFFE8EEF7), fontSize: 12, fontWeight: FontWeight.bold)))
            : const SubHead('Consume food'),
        if (!any)
          Text('You have no food in your inventory.', style: TextStyle(fontSize: 12, color: dark ? const Color(0xFF9FB3C8) : EjColors.text))
        else ...[
          Wrap(
            children: [
              for (var s = 5; s >= 1; s--)
                if ((foods['$s'] ?? 0) > 0)
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      InventoryBox(
                        icon: foodIcon ?? 'assets/legacy/food-icon.png',
                        stars: s,
                        amount: (foods['$s'] as num).toInt() - (selected[s] ?? 0),
                        selected: (selected[s] ?? 0) > 0,
                        onTap: (selected[s] ?? 0) < (foods['$s'] as num).toInt() ? () => onChanged({...selected, s: (selected[s] ?? 0) + 1}) : null,
                      ),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          _mini('−', (selected[s] ?? 0) > 0 ? () => onChanged({...selected, s: selected[s]! - 1}) : null),
                          Text(
                            '${selected[s] ?? 0}',
                            style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: dark ? Colors.white : EjColors.black),
                          ),
                          _mini('+', (selected[s] ?? 0) < (foods['$s'] as num).toInt() ? () => onChanged({...selected, s: (selected[s] ?? 0) + 1}) : null),
                        ],
                      ),
                    ],
                  ),
            ],
          ),
          Text('Wellness to recover: $recover / $maxRecover', style: TextStyle(fontSize: 11, color: recover > maxRecover ? EjColors.red : (dark ? const Color(0xFF9FB3C8) : EjColors.text))),
        ],
      ],
    );
  }

  Widget _mini(String t, VoidCallback? f) => InkWell(
    onTap: f,
    child: Container(
      width: 16,
      height: 16,
      margin: const EdgeInsets.all(2),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        border: Border.all(color: f == null ? EjColors.line : EjColors.black),
        color: Colors.white,
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(t, style: TextStyle(fontSize: 11, height: 1, color: f == null ? EjColors.line : EjColors.black)),
    ),
  );
}

/// One entry of the `#submenubar` dock (icon + label) under the menubar.
class DockEntry {
  const DockEntry(this.label, this.icon, {this.tab, this.path, this.url, this.logout = false});
  final String label, icon;
  final int? tab; // app screen
  final String? path; // website page (relative to the server)
  final String? url; // external site
  final bool logout;
}

/// The dock: icon grid strip under the menubar.
class LegacyDock extends StatelessWidget {
  const LegacyDock({super.key, required this.entries, required this.onTap});
  final List<DockEntry> entries;
  final ValueChanged<DockEntry> onTap;
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.fromLTRB(8, 0, 8, 8),
    height: 96,
    decoration: Er.cardBox(),
    child: ListView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
      children: [
        for (final e in entries)
          InkWell(
            onTap: () => onTap(e),
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: 78,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(color: Er.surface, borderRadius: BorderRadius.circular(12), border: Border.all(color: Er.line)),
                    child: legacy(e.icon, fit: BoxFit.contain),
                  ),
                  const SizedBox(height: 4),
                  Text(e.label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: Er.text)),
                ],
              ),
            ),
          ),
      ],
    ),
  );
}

/// Section title in the gym skin: bold white with a cyan accent bar and an optional trailing link.
class GymTitle extends StatelessWidget {
  const GymTitle(this.title, {super.key, this.trailing, this.onTrailing});
  final String title;
  final String? trailing;
  final VoidCallback? onTrailing;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(2, 4, 2, 8),
    child: Row(
      children: [
        Container(width: 4, height: 16, decoration: BoxDecoration(color: Er.accent, borderRadius: BorderRadius.circular(2), boxShadow: [BoxShadow(color: Er.accent.withValues(alpha: .8), blurRadius: 6)])),
        const SizedBox(width: 8),
        Expanded(child: Text(title.toUpperCase(), style: const TextStyle(color: Er.text, fontSize: 13, fontWeight: FontWeight.w900, letterSpacing: 1.2))),
        if (trailing != null)
          InkWell(
            onTap: onTrailing,
            child: Text(trailing!, style: TextStyle(color: onTrailing == null ? Er.muted : Er.blue, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
      ],
    ),
  );
}

/// Pill tabs in the gym skin.
class GymTabs extends StatelessWidget {
  const GymTabs({super.key, required this.items, required this.selected, required this.onSelect, this.small = false});
  final List<String> items;
  final int selected;
  final ValueChanged<int> onSelect;
  final bool small;
  @override
  Widget build(BuildContext context) => SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    child: Row(
      children: [
        for (var i = 0; i < items.length; i++)
          Padding(
            padding: const EdgeInsets.only(right: 6),
            child: InkWell(
              onTap: () => onSelect(i),
              borderRadius: BorderRadius.circular(20),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                padding: EdgeInsets.symmetric(horizontal: small ? 10 : 14, vertical: small ? 4 : 7),
                decoration: BoxDecoration(
                  color: i == selected ? Er.accent : Er.surface,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: i == selected ? Er.accent : Er.line),
                ),
                child: Text(items[i], style: TextStyle(color: i == selected ? Colors.white : Er.text, fontSize: small ? 11 : 12, fontWeight: FontWeight.bold)),
              ),
            ),
          ),
      ],
    ),
  );
}

/// The glowing pill button from the gym (cyan by default).
class GlowButton extends StatelessWidget {
  const GlowButton(this.label, {super.key, this.onPressed, this.busy = false, this.small = false, this.color = Er.accent});
  final String label;
  final VoidCallback? onPressed;
  final bool busy, small;
  final Color color;
  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !busy;
    return InkWell(
      onTap: enabled ? onPressed : null,
      borderRadius: BorderRadius.circular(24),
      child: Container(
        height: small ? 30 : 44,
        padding: EdgeInsets.symmetric(horizontal: small ? 14 : 30),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          gradient: enabled ? LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color.lerp(color, Colors.white, .18)!, color]) : null,
          color: enabled ? null : Er.line,
          boxShadow: enabled ? [BoxShadow(color: color.withValues(alpha: .35), blurRadius: small ? 6 : 10, offset: const Offset(0, 2))] : null,
        ),
        child: busy
            ? SizedBox(width: small ? 12 : 18, height: small ? 12 : 18, child: const CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
            : Text(label, style: TextStyle(color: enabled ? Colors.white : Er.muted, fontSize: small ? 12 : 17, fontWeight: FontWeight.w800)),
      ),
    );
  }
}

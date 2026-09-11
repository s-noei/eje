import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../core/theme.dart';

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

/// Glass card with the accent hairline on top.
class GlassCard extends StatelessWidget {
  const GlassCard({super.key, required this.child, this.padding = const EdgeInsets.all(14), this.accent, this.onTap});
  final Widget child;
  final EdgeInsets padding;
  final Color? accent;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final card = Container(
      decoration: BoxDecoration(
        color: EjColors.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: EjColors.line),
        boxShadow: const [BoxShadow(color: Color(0x47000000), blurRadius: 24, offset: Offset(0, 8))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, mainAxisSize: MainAxisSize.min, children: [
        Container(height: 2, decoration: BoxDecoration(gradient: LinearGradient(colors: [accent ?? EjColors.accent, EjColors.accent2]))),
        Padding(padding: padding, child: child),
      ]),
    );
    return onTap == null ? card : InkWell(onTap: onTap, borderRadius: BorderRadius.circular(16), child: card);
  }
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title, {super.key, this.icon, this.trailing});
  final String title;
  final IconData? icon;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Row(children: [
          if (icon != null)
            Container(
              width: 26, height: 26, margin: const EdgeInsets.only(right: 8),
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(8), gradient: const LinearGradient(colors: [EjColors.accent, EjColors.accent2])),
              child: Icon(icon, size: 15, color: Colors.white),
            ),
          Expanded(child: Text(title.toUpperCase(), style: display(size: 16))),
          if (trailing != null) trailing!,
        ]),
      );
}

class StatBar extends StatelessWidget {
  const StatBar({super.key, required this.label, required this.value, required this.text, this.colors = const [EjColors.accent, EjColors.accent2]});
  final String label;
  final double value;
  final String text;
  final List<Color> colors;
  @override
  Widget build(BuildContext context) => Row(children: [
        SizedBox(width: 64, child: Text(label.toUpperCase(), style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: EjColors.muted, letterSpacing: 1))),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: Stack(children: [
              Container(height: 9, color: Colors.white.withValues(alpha: .08)),
              AnimatedFractionallySizedBox(
                duration: const Duration(milliseconds: 700), curve: Curves.easeOutCubic, widthFactor: value.clamp(0, 1),
                child: Container(height: 9, decoration: BoxDecoration(gradient: LinearGradient(colors: colors), boxShadow: [BoxShadow(color: colors.last.withValues(alpha: .5), blurRadius: 10)])),
              ),
            ]),
          ),
        ),
        const SizedBox(width: 8),
        SizedBox(width: 90, child: Text(text, textAlign: TextAlign.right, style: display(size: 12))),
      ]);
}

class EjButton extends StatelessWidget {
  const EjButton(this.label, {super.key, this.onPressed, this.gold = false, this.ghost = false, this.icon, this.busy = false});
  final String label;
  final VoidCallback? onPressed;
  final bool gold, ghost, busy;
  final IconData? icon;
  @override
  Widget build(BuildContext context) {
    final fg = gold ? const Color(0xFF1B1400) : Colors.white;
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(99),
        gradient: ghost ? null : LinearGradient(colors: gold ? const [Color(0xFFFFE08A), Color(0xFFF59E0B)] : const [EjColors.accent, Color(0xFF4F46E5)]),
        color: ghost ? Colors.white.withValues(alpha: .08) : null,
        border: ghost ? Border.all(color: EjColors.line) : null,
        boxShadow: ghost || onPressed == null ? null : [BoxShadow(color: (gold ? EjColors.gold : EjColors.accent).withValues(alpha: .45), blurRadius: 18, offset: const Offset(0, 6))],
      ),
      child: TextButton.icon(
        onPressed: busy ? null : onPressed,
        style: TextButton.styleFrom(foregroundColor: fg, padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12), shape: const StadiumBorder()),
        icon: busy ? SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: fg)) : Icon(icon ?? Icons.bolt, size: 16, color: onPressed == null ? EjColors.muted : fg),
        label: Text(label.toUpperCase(), style: display(size: 14, color: onPressed == null ? EjColors.muted : fg)),
      ),
    );
  }
}

class Chip2 extends StatelessWidget {
  const Chip2({super.key, required this.value, required this.label, this.image, this.icon});
  final String value, label;
  final String? image;
  final IconData? icon;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .06), borderRadius: BorderRadius.circular(12), border: Border.all(color: EjColors.line)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          if (image != null) Image.network(image!, height: 16, errorBuilder: (_, __, ___) => const SizedBox()),
          if (icon != null) Icon(icon, size: 16, color: EjColors.gold),
          const SizedBox(width: 6),
          Text(value, style: display(size: 14)),
          const SizedBox(width: 4),
          Text(label, style: const TextStyle(color: EjColors.muted, fontSize: 11)),
        ]),
      );
}

class Pills extends StatelessWidget {
  const Pills({super.key, required this.items, required this.selected, required this.onSelect});
  final List<String> items;
  final int selected;
  final ValueChanged<int> onSelect;
  @override
  Widget build(BuildContext context) => SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Container(
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(color: Colors.black.withValues(alpha: .3), borderRadius: BorderRadius.circular(99), border: Border.all(color: EjColors.line)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            for (var i = 0; i < items.length; i++)
              GestureDetector(
                onTap: () => onSelect(i),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(99),
                    gradient: i == selected ? const LinearGradient(colors: [EjColors.accent, Color(0xFF4F46E5)]) : null,
                  ),
                  child: Text(items[i], style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: i == selected ? Colors.white : EjColors.muted)),
                ),
              ),
          ]),
        ),
      );
}

class Avatar extends StatelessWidget {
  const Avatar(this.url, {super.key, this.size = 40, this.progress, this.badge});
  final String url;
  final double size;
  final double? progress;
  final String? badge;
  @override
  Widget build(BuildContext context) => SizedBox(
        width: size + 8, height: size + 8,
        child: Stack(clipBehavior: Clip.none, children: [
          if (progress != null)
            SizedBox(width: size + 8, height: size + 8, child: CircularProgressIndicator(value: progress, strokeWidth: 3, color: EjColors.accent2, backgroundColor: Colors.white.withValues(alpha: .1))),
          Positioned(
            left: 4, top: 4,
            child: ClipOval(
              child: Image.network(url, width: size, height: size, fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(width: size, height: size, color: EjColors.bg2, child: const Icon(Icons.person, color: EjColors.muted))),
            ),
          ),
          if (badge != null)
            Positioned(
              right: -4, bottom: -2,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(99), gradient: const LinearGradient(colors: [Color(0xFFFFE08A), EjColors.gold]), boxShadow: [BoxShadow(color: EjColors.gold.withValues(alpha: .45), blurRadius: 10)]),
                child: Text(badge!, style: display(size: 12, color: const Color(0xFF1B1400))),
              ),
            ),
        ]),
      );
}

class EmptyNote extends StatelessWidget {
  const EmptyNote(this.text, {super.key});
  final String text;
  @override
  Widget build(BuildContext context) => Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: Text(text, style: const TextStyle(color: EjColors.muted)));
}

class ErrorNote extends StatelessWidget {
  const ErrorNote(this.error, {super.key, this.onRetry});
  final Object error;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.cloud_off, color: EjColors.red, size: 36),
            const SizedBox(height: 8),
            Text('$error', textAlign: TextAlign.center, style: const TextStyle(color: EjColors.muted)),
            if (onRetry != null) ...[const SizedBox(height: 12), EjButton('Retry', onPressed: onRetry, ghost: true, icon: Icons.refresh)],
          ]),
        ),
      );
}

void toast(BuildContext context, String msg, {bool error = false}) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg), backgroundColor: error ? EjColors.red : EjColors.accent));
}

/// Food picker used by train/work: {stars: amount} to consume.
class FoodPicker extends StatelessWidget {
  const FoodPicker({super.key, required this.foods, required this.selected, required this.onChanged, required this.maxRecover});
  final Map<String, dynamic> foods;
  final Map<int, int> selected;
  final ValueChanged<Map<int, int>> onChanged;
  final int maxRecover;
  @override
  Widget build(BuildContext context) {
    final recover = selected.entries.fold<int>(0, (a, e) => a + e.key * e.value);
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Eat food during the task — recovers $recover / $maxRecover wellness', style: TextStyle(fontSize: 12, color: recover > maxRecover ? EjColors.red : EjColors.muted)),
      const SizedBox(height: 8),
      Wrap(spacing: 6, runSpacing: 6, children: [
        for (var s = 5; s >= 1; s--)
          if ((foods['$s'] ?? 0) > 0)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(color: Colors.black.withValues(alpha: .25), borderRadius: BorderRadius.circular(10), border: Border.all(color: EjColors.line)),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Text('$s★', style: display(size: 13, color: EjColors.gold)),
                Text(' ×${foods['$s']}', style: const TextStyle(color: EjColors.muted, fontSize: 11)),
                IconButton(visualDensity: VisualDensity.compact, icon: const Icon(Icons.remove, size: 16), onPressed: (selected[s] ?? 0) > 0 ? () => onChanged({...selected, s: selected[s]! - 1}) : null),
                Text('${selected[s] ?? 0}', style: display(size: 13)),
                IconButton(visualDensity: VisualDensity.compact, icon: const Icon(Icons.add, size: 16), onPressed: (selected[s] ?? 0) < (foods['$s'] as int) ? () => onChanged({...selected, s: (selected[s] ?? 0) + 1}) : null),
              ]),
            ),
      ]),
    ]);
  }
}

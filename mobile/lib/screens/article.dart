import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

class ArticleScreen extends ConsumerStatefulWidget {
  const ArticleScreen({super.key, required this.id});
  final int id;
  @override
  ConsumerState<ArticleScreen> createState() => _ArticleState();
}

class _ArticleState extends ConsumerState<ArticleScreen> {
  final _comment = TextEditingController();
  bool _busy = false;

  static String _strip(String html) => html
      .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</p>', caseSensitive: false), '\n\n')
      .replaceAll(RegExp(r'<[^>]+>'), '')
      .replaceAll('&nbsp;', ' ').replaceAll('&amp;', '&').replaceAll('&lt;', '<').replaceAll('&gt;', '>').replaceAll('&quot;', '"')
      .replaceAll(RegExp(r'\n{3,}'), '\n\n').trim();

  Future<void> _vote(int v) async {
    try {
      await ref.read(apiProvider).post('articles/${widget.id}/vote', {'vote': v});
      ref.invalidate(articleProvider(widget.id));
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    }
  }

  Future<void> _post() async {
    if (_comment.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      await ref.read(apiProvider).post('articles/${widget.id}/comments', {'body': _comment.text});
      _comment.clear();
      ref.invalidate(articleProvider(widget.id));
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = ref.watch(articleProvider(widget.id));
    return Scaffold(
      appBar: AppBar(title: Text('Article', style: display(size: 18))),
      body: data.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(articleProvider(widget.id))),
        data: (d) {
          final a = d['article'] as Map<String, dynamic>;
          final comments = (d['comments'] as List).cast<Map<String, dynamic>>();
          final voted = a['voted'] == true;
          return ListView(padding: const EdgeInsets.all(14), children: [
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(a['title'] as String, style: display(size: 22)),
              const SizedBox(height: 4),
              Text('${a['newspaper']['name']} · by ${a['author']['name']} · ${ago(a['time'] as int)}', style: const TextStyle(color: EjColors.muted, fontSize: 12)),
              const SizedBox(height: 12),
              if (a['picture'] != null) ClipRRect(borderRadius: BorderRadius.circular(10), child: Image.network(a['picture'] as String, errorBuilder: (_, __, ___) => const SizedBox())),
              Directionality(textDirection: a['rtl'] == true ? TextDirection.rtl : TextDirection.ltr, child: Text(_strip(a['html'] as String), style: const TextStyle(height: 1.5))),
              const SizedBox(height: 14),
              Row(children: [
                Chip2(value: '${a['votes']}', label: 'votes', icon: Icons.thumb_up),
                const SizedBox(width: 8),
                if (!voted) ...[
                  EjButton('Vote up', icon: Icons.thumb_up, onPressed: () => _vote(1)),
                  const SizedBox(width: 6),
                  EjButton('Down', ghost: true, icon: Icons.thumb_down, onPressed: () => _vote(-1)),
                ] else const Text('Voted', style: TextStyle(color: EjColors.muted)),
              ]),
            ])),
            const SizedBox(height: 12),
            GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              SectionTitle('${comments.length} comments', icon: Icons.forum),
              for (final c in comments)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Avatar(c['author']['avatar'] as String, size: 30),
                    const SizedBox(width: 8),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [Text(c['author']['name'] as String, style: const TextStyle(fontWeight: FontWeight.w700, color: EjColors.accent2)), const SizedBox(width: 6), Text(ago(c['time'] as int), style: const TextStyle(color: EjColors.muted, fontSize: 11))]),
                      Text(_strip(c['body'] as String), style: const TextStyle(fontSize: 13, height: 1.35)),
                      Text('👍 ${c['up']}  👎 ${c['down']}', style: const TextStyle(color: EjColors.muted, fontSize: 11)),
                    ])),
                  ]),
                ),
              const SizedBox(height: 8),
              TextField(controller: _comment, minLines: 2, maxLines: 5, decoration: const InputDecoration(hintText: 'Write a comment…')),
              const SizedBox(height: 8),
              EjButton('Post comment', icon: Icons.send, busy: _busy, onPressed: _post),
            ])),
          ]);
        },
      ),
    );
  }
}

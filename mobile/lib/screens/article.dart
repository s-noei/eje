import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'shell.dart';

/// Article page in the legacy newspaper look: title, byline, body, vote box, comments.
class ArticleScreen extends ConsumerStatefulWidget {
  const ArticleScreen({super.key, required this.id});
  final int id;
  @override
  ConsumerState<ArticleScreen> createState() => _ArticleState();
}

class _ArticleState extends ConsumerState<ArticleScreen> {
  final _comment = TextEditingController();
  bool _busy = false;

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
    return LegacyFrame(
      back: true,
      children: [
        data.when(
          loading: () => const Loading(),
          error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(articleProvider(widget.id))),
          data: (d) {
            final a = d['article'] as Map<String, dynamic>;
            final comments = (d['comments'] as List).cast<Map<String, dynamic>>();
            final voted = a['voted'] == true;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // article-points badge, as on the media pages
                    Container(
                      width: 40,
                      height: 45,
                      alignment: const Alignment(0, .3),
                      decoration: const BoxDecoration(
                        image: DecorationImage(image: AssetImage('assets/legacy/points-bg.gif'), fit: BoxFit.none, alignment: Alignment.topCenter),
                      ),
                      child: Text(
                        '${a['votes']}',
                        style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            a['title'] as String,
                            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: EjColors.black),
                          ),
                          Text.rich(
                            TextSpan(
                              children: [
                                const TextSpan(text: 'Published in '),
                                TextSpan(
                                  text: '${a['newspaper']['name']}',
                                  style: const TextStyle(color: EjColors.link),
                                ),
                                const TextSpan(text: ' by '),
                                TextSpan(
                                  text: '${a['author']['name']}',
                                  style: const TextStyle(color: EjColors.link),
                                ),
                                TextSpan(text: ' · ${ago(a['time'] as int)}'),
                              ],
                            ),
                            style: const TextStyle(fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const Divider(color: EjColors.black, thickness: .5),
                if (a['picture'] != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: netImg(a['picture'] as String, fit: BoxFit.contain),
                  ),
                Directionality(
                  textDirection: a['rtl'] == true ? TextDirection.rtl : TextDirection.ltr,
                  child: Text(stripHtml(a['html'] as String), style: const TextStyle(fontSize: 12, height: 1.5, color: EjColors.black)),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: EjColors.line),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Row(
                    children: [
                      Text(
                        '${a['votes']} votes',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
                      ),
                      const Spacer(),
                      if (!voted) ...[
                        ImgButton('Vote up', width: 90, onPressed: () => _vote(1)),
                        const SizedBox(width: 4),
                        ImgButton('Vote down', width: 90, red: true, onPressed: () => _vote(-1)),
                      ] else
                        const Text('You already voted', style: TextStyle(fontSize: 11, color: EjColors.green)),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                SubHead('Comments (${comments.length})'),
                for (final c in comments)
                  Container(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    decoration: const BoxDecoration(
                      border: Border(bottom: BorderSide(color: EjColors.line)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Avatar(c['author']['avatar'] as String, size: 36),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Text(
                                    c['author']['name'] as String,
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: EjColors.link, fontSize: 12),
                                  ),
                                  const SizedBox(width: 6),
                                  Text(ago(c['time'] as int), style: const TextStyle(fontSize: 10)),
                                  const Spacer(),
                                  Text('+${c['up']} / -${c['down']}', style: const TextStyle(fontSize: 10)),
                                ],
                              ),
                              Text(stripHtml(c['body'] as String), style: const TextStyle(fontSize: 12, height: 1.35)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                const SizedBox(height: 8),
                TextField(
                  controller: _comment,
                  minLines: 2,
                  maxLines: 5,
                  style: const TextStyle(fontSize: 12),
                  decoration: const InputDecoration(hintText: 'Write your comment…'),
                ),
                const SizedBox(height: 6),
                Center(
                  child: ImgButton('Post comment', busy: _busy, onPressed: _post),
                ),
                const SizedBox(height: 6),
                Center(child: LinkText('Open in the newspaper', onTap: () => openWeb(ref, 'article-${widget.id}-en.html'))),
              ],
            );
          },
        ),
      ],
    );
  }
}

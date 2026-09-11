import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

class MailScreen extends ConsumerStatefulWidget {
  const MailScreen({super.key});
  @override
  ConsumerState<MailScreen> createState() => _MailState();
}

class _MailState extends ConsumerState<MailScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) => Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 8, 14, 0),
          child: Row(children: [
            Expanded(child: Pills(items: const ['Inbox', 'Notes'], selected: _tab, onSelect: (i) => setState(() => _tab = i))),
            EjButton('Compose', ghost: true, icon: Icons.edit, onPressed: () => showModalBottomSheet(context: context, isScrollControlled: true, backgroundColor: EjColors.bg2, builder: (_) => const ComposeSheet())),
          ]),
        ),
        Expanded(child: _tab == 0 ? const _Inbox() : const _Notes()),
      ]);
}

class _Inbox extends ConsumerWidget {
  const _Inbox();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final inbox = ref.watch(inboxProvider);
    return RefreshIndicator(
      onRefresh: () => ref.refresh(inboxProvider.future),
      child: inbox.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(inboxProvider)),
        data: (list) => list.isEmpty
            ? const Center(child: EmptyNote('Your inbox is empty.'))
            : ListView.builder(
                padding: const EdgeInsets.all(14),
                itemCount: list.length,
                itemBuilder: (_, i) {
                  final m = list[i];
                  return GlassCard(
                    padding: const EdgeInsets.all(10),
                    accent: m.read ? EjColors.accent : EjColors.gold,
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => MessageScreen(m))),
                    child: Row(children: [
                      Avatar(m.fromAvatar, size: 36),
                      const SizedBox(width: 10),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(m.subject, style: TextStyle(fontWeight: m.read ? FontWeight.w500 : FontWeight.w800), maxLines: 1, overflow: TextOverflow.ellipsis),
                        Text('${m.fromName} · ${ago(m.time)}', style: const TextStyle(color: EjColors.muted, fontSize: 11)),
                      ])),
                      if (!m.read) Container(width: 8, height: 8, decoration: const BoxDecoration(shape: BoxShape.circle, color: EjColors.gold)),
                    ]),
                  );
                },
              ),
      ),
    );
  }
}

class _Notes extends ConsumerWidget {
  const _Notes();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notes = ref.watch(notesProvider);
    return RefreshIndicator(
      onRefresh: () => ref.refresh(notesProvider.future),
      child: notes.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(notesProvider)),
        data: (list) => list.isEmpty
            ? const Center(child: EmptyNote('No notes.'))
            : ListView.builder(
                padding: const EdgeInsets.all(14),
                itemCount: list.length,
                itemBuilder: (_, i) => GlassCard(
                  padding: const EdgeInsets.all(10),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(list[i]['text'] as String, style: const TextStyle(fontSize: 13)),
                    Text(ago(list[i]['time'] as int), style: const TextStyle(color: EjColors.muted, fontSize: 11)),
                  ]),
                ),
              ),
      ),
    );
  }
}

class MessageScreen extends ConsumerWidget {
  const MessageScreen(this.m, {super.key});
  final Message m;
  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
        appBar: AppBar(title: Text('Message', style: display(size: 18))),
        body: ListView(padding: const EdgeInsets.all(14), children: [
          GlassCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Avatar(m.fromAvatar, size: 40),
              const SizedBox(width: 10),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(m.fromName, style: display(size: 16)), Text(ago(m.time), style: const TextStyle(color: EjColors.muted, fontSize: 11))])),
            ]),
            const SizedBox(height: 12),
            Text(m.subject, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
            const Divider(color: EjColors.line),
            Text(_strip(m.body), style: const TextStyle(height: 1.4)),
            const SizedBox(height: 14),
            Row(children: [
              EjButton('Reply', icon: Icons.reply, onPressed: () => showModalBottomSheet(context: context, isScrollControlled: true, backgroundColor: EjColors.bg2, builder: (_) => ComposeSheet(to: m.fromName, subject: 'Re: ${m.subject}'))),
              const SizedBox(width: 8),
              EjButton('Delete', ghost: true, icon: Icons.delete_outline, onPressed: () async {
                try {
                  await ref.read(apiProvider).delete('mail/${m.id}');
                  ref.invalidate(inboxProvider);
                  if (context.mounted) Navigator.of(context).pop();
                } on ApiException catch (e) {
                  if (context.mounted) toast(context, e.message, error: true);
                }
              }),
            ]),
          ])),
        ]),
      );

  static String _strip(String html) => html.replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n').replaceAll(RegExp(r'<[^>]+>'), '').replaceAll('&nbsp;', ' ').trim();
}

class ComposeSheet extends ConsumerStatefulWidget {
  const ComposeSheet({super.key, this.to, this.subject});
  final String? to, subject;
  @override
  ConsumerState<ComposeSheet> createState() => _ComposeState();
}

class _ComposeState extends ConsumerState<ComposeSheet> {
  late final _to = TextEditingController(text: widget.to ?? '');
  late final _subject = TextEditingController(text: widget.subject ?? '');
  final _body = TextEditingController();
  bool _busy = false;

  Future<void> _send() async {
    setState(() => _busy = true);
    try {
      await ref.read(apiProvider).post('mail/send', {'to': _to.text, 'subject': _subject.text, 'body': _body.text});
      if (mounted) {
        toast(context, 'Message sent');
        Navigator.of(context).pop();
      }
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(16, 16, 16, MediaQuery.of(context).viewInsets.bottom + 16),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text('New message', style: display(size: 18)),
          const SizedBox(height: 10),
          TextField(controller: _to, decoration: const InputDecoration(hintText: 'To (citizen name)')),
          const SizedBox(height: 8),
          TextField(controller: _subject, decoration: const InputDecoration(hintText: 'Subject')),
          const SizedBox(height: 8),
          TextField(controller: _body, minLines: 4, maxLines: 8, decoration: const InputDecoration(hintText: 'Message')),
          const SizedBox(height: 12),
          EjButton('Send', icon: Icons.send, busy: _busy, onPressed: _send),
        ]),
      );
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../state/session.dart';
import '../widgets/ui.dart';
import 'shell.dart';

/// Mail — legacy look: green link bar, black "Inbox" bar, avatar rows with hairlines.
class MailScreen extends ConsumerStatefulWidget {
  const MailScreen({super.key});
  @override
  ConsumerState<MailScreen> createState() => _MailState();
}

class _MailState extends ConsumerState<MailScreen> {
  int _tab = 0; // 0 inbox, 1 sent, 2 notes, 3 compose

  @override
  Widget build(BuildContext context) {
    final titles = ['Inbox', 'Sent', 'Notes', 'Compose Message'];
    return LegacyFrame(
      padding: const EdgeInsets.fromLTRB(4, 8, 4, 8),
      onRefresh: () async {
        ref.invalidate(inboxProvider);
        ref.invalidate(notesProvider);
        ref.invalidate(sentProvider);
      },
      children: [
        SidebarLayout(
          content: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              MailLinkBar(selected: _tab, onSelect: (i) => setState(() => _tab = i)),
              BlackBar(titles[_tab]),
              switch (_tab) {
                0 => const _Inbox(),
                1 => const _Sent(),
                2 => const _Notes(),
                _ => Padding(
                  padding: const EdgeInsets.all(8),
                  child: ComposeForm(onSent: () => setState(() => _tab = 1)),
                ),
              },
            ],
          ),
        ),
      ],
    );
  }
}

/// `| Compose Message | Inbox | Sent | Notes |` on the pale-green strip.
class MailLinkBar extends StatelessWidget {
  const MailLinkBar({super.key, required this.selected, required this.onSelect});
  final int selected;
  final ValueChanged<int> onSelect;
  @override
  Widget build(BuildContext context) {
    const items = [(3, 'Compose Message'), (0, 'Inbox'), (1, 'Sent'), (2, 'Notes')];
    return Container(
      color: const Color(0xFFC8F0C8),
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Wrap(
        alignment: WrapAlignment.center,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          const Text(
            '|',
            style: TextStyle(color: EjColors.green, fontWeight: FontWeight.bold),
          ),
          for (final (i, t) in items) ...[
            InkWell(
              onTap: () => onSelect(i),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 5),
                child: Text(
                  t,
                  style: TextStyle(color: EjColors.green, fontWeight: FontWeight.bold, fontSize: 12, decoration: i == selected ? TextDecoration.underline : null),
                ),
              ),
            ),
            const Text(
              '|',
              style: TextStyle(color: EjColors.green, fontWeight: FontWeight.bold),
            ),
          ],
        ],
      ),
    );
  }
}

class BlackBar extends StatelessWidget {
  const BlackBar(this.title, {super.key});
  final String title;
  @override
  Widget build(BuildContext context) => Container(
    color: EjColors.black,
    padding: const EdgeInsets.symmetric(vertical: 2),
    alignment: Alignment.center,
    child: Text(
      title,
      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
    ),
  );
}

Widget _header(List<String> cols) => Container(
  padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 6),
  decoration: const BoxDecoration(
    border: Border(bottom: BorderSide(color: EjColors.black)),
  ),
  child: Row(
    children: [
      SizedBox(
        width: 62,
        child: Text(cols[0], textAlign: TextAlign.center, style: const TextStyle(fontSize: 12)),
      ),
      Expanded(child: Text(cols[1], style: const TextStyle(fontSize: 12))),
      Text(cols[2], style: const TextStyle(fontSize: 12)),
    ],
  ),
);

class _Inbox extends ConsumerWidget {
  const _Inbox();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final inbox = ref.watch(inboxProvider);
    return inbox.when(
      loading: () => const Loading(),
      error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(inboxProvider)),
      data: (list) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _header(const ['From', 'Subject', 'Date']),
          if (list.isEmpty) const Center(child: EmptyNote('Your inbox is empty.')),
          for (final m in list)
            MessageRow(
              m,
              who: m.fromName,
              avatar: m.fromAvatar,
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => MessageScreen(m))),
            ),
        ],
      ),
    );
  }
}

class _Sent extends ConsumerWidget {
  const _Sent();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sent = ref.watch(sentProvider);
    return sent.when(
      loading: () => const Loading(),
      error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(sentProvider)),
      data: (list) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _header(const ['To', 'Subject', 'Date']),
          if (list.isEmpty) const Center(child: EmptyNote('You have not sent any message.')),
          for (final m in list)
            MessageRow(
              m,
              who: m.toName,
              avatar: m.fromAvatar,
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => MessageScreen(m, sent: true))),
            ),
        ],
      ),
    );
  }
}

/// One inbox/sent row: avatar + name on the left, subject + excerpt, date on the right.
class MessageRow extends StatelessWidget {
  const MessageRow(this.m, {super.key, required this.who, required this.avatar, this.onTap});
  final Message m;
  final String who, avatar;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final excerpt = stripHtml(m.body).replaceAll(RegExp(r'\s+'), ' ');
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 6),
        decoration: const BoxDecoration(
          border: Border(bottom: BorderSide(color: EjColors.line)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 62,
              child: Column(
                children: [
                  Avatar(avatar, size: 50),
                  Text(who, style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
            const SizedBox(width: 6),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    m.subject.isEmpty ? '(no subject)' : m.subject,
                    style: TextStyle(fontSize: 13, color: EjColors.text, fontWeight: m.read ? FontWeight.normal : FontWeight.bold),
                  ),
                  const SizedBox(height: 2),
                  Text(excerpt.length > 90 ? '${excerpt.substring(0, 90)}…' : excerpt, style: const TextStyle(fontSize: 10), maxLines: 2, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
            const SizedBox(width: 6),
            Text(ago(m.time), style: const TextStyle(fontSize: 11)),
          ],
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
    return notes.when(
      loading: () => const Loading(),
      error: (e, _) => ErrorNote(e, onRetry: () => ref.invalidate(notesProvider)),
      data: (list) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (list.isEmpty) const Center(child: EmptyNote('No notes.')),
          for (final n in list)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
              decoration: const BoxDecoration(
                border: Border(bottom: BorderSide(color: EjColors.line)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  legacy('icon_sendmsg.gif', width: 16, height: 16),
                  const SizedBox(width: 6),
                  Expanded(child: Text(stripHtml(n['text'] as String), style: const TextStyle(fontSize: 12))),
                  const SizedBox(width: 6),
                  Text(ago(n['time'] as int), style: const TextStyle(fontSize: 10)),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// Read one message (legacy "view message" box) with Reply / Delete image buttons.
class MessageScreen extends ConsumerWidget {
  const MessageScreen(this.m, {super.key, this.sent = false});
  final Message m;
  final bool sent;
  @override
  Widget build(BuildContext context, WidgetRef ref) => LegacyFrame(
    back: true,
    children: [
      const BlackBar('Message'),
      Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: EjColors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Avatar(m.fromAvatar, size: 50),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text.rich(
                        TextSpan(
                          children: [
                            TextSpan(text: sent ? 'To: ' : 'From: '),
                            TextSpan(
                              text: sent ? m.toName : m.fromName,
                              style: const TextStyle(color: EjColors.link, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                        style: const TextStyle(fontSize: 12),
                      ),
                      Text('Date: ${ago(m.time)}', style: const TextStyle(fontSize: 12)),
                      Text(
                        'Subject: ${m.subject}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Divider(color: EjColors.black, thickness: .5),
            Text(stripHtml(m.body), style: const TextStyle(fontSize: 12, height: 1.4)),
            const SizedBox(height: 12),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                if (!sent)
                  ImgButton(
                    'Reply',
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => ComposeScreen(to: m.fromName, subject: 'Re: ${m.subject}'),
                      ),
                    ),
                  ),
                ImgButton(
                  'Delete',
                  red: true,
                  onPressed: () async {
                    try {
                      await ref.read(apiProvider).delete('mail/${m.id}');
                      ref.invalidate(inboxProvider);
                      ref.invalidate(sentProvider);
                      if (context.mounted) Navigator.of(context).pop();
                    } on ApiException catch (e) {
                      if (context.mounted) toast(context, e.message, error: true);
                    }
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    ],
  );
}

class ComposeScreen extends StatelessWidget {
  const ComposeScreen({super.key, this.to, this.subject});
  final String? to, subject;
  @override
  Widget build(BuildContext context) => LegacyFrame(
    back: true,
    children: [
      const BlackBar('Compose Message'),
      Padding(
        padding: const EdgeInsets.all(8),
        child: ComposeForm(to: to, subject: subject, onSent: () => Navigator.of(context).pop()),
      ),
    ],
  );
}

/// The legacy compose form: To / Subject / Message + blue "Send" button.
class ComposeForm extends ConsumerStatefulWidget {
  const ComposeForm({super.key, this.to, this.subject, this.onSent});
  final String? to, subject;
  final VoidCallback? onSent;
  @override
  ConsumerState<ComposeForm> createState() => _ComposeState();
}

class _ComposeState extends ConsumerState<ComposeForm> {
  late final _to = TextEditingController(text: widget.to ?? '');
  late final _subject = TextEditingController(text: widget.subject ?? '');
  final _body = TextEditingController();
  bool _busy = false;

  Future<void> _send() async {
    setState(() => _busy = true);
    try {
      await ref.read(apiProvider).post('mail/send', {'to': _to.text, 'subject': _subject.text, 'body': _body.text});
      ref.invalidate(sentProvider);
      if (mounted) {
        toast(context, 'Your message has been sent.');
        widget.onSent?.call();
      }
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _row(String label, Widget field) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 70,
          child: Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              label,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: EjColors.black),
            ),
          ),
        ),
        Expanded(child: field),
      ],
    ),
  );

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _row(
        'To:',
        TextField(
          controller: _to,
          style: const TextStyle(fontSize: 12),
          decoration: const InputDecoration(hintText: 'Citizen name'),
        ),
      ),
      _row('Subject:', TextField(controller: _subject, style: const TextStyle(fontSize: 12))),
      _row('Message:', TextField(controller: _body, minLines: 5, maxLines: 10, style: const TextStyle(fontSize: 12))),
      const SizedBox(height: 8),
      Center(
        child: ImgButton('Send message', busy: _busy, onPressed: _send),
      ),
    ],
  );
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

/// The guest home: logo header, menubar, and the legacy "mini-login" box + welcome text.
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});
  @override
  ConsumerState<LoginScreen> createState() => _LoginState();
}

class _LoginState extends ConsumerState<LoginScreen> {
  final _user = TextEditingController();
  final _pass = TextEditingController();
  late final _server = TextEditingController(text: ref.read(apiProvider).baseUrl);
  bool _busy = false, _showServer = false;
  String? _error;

  Future<void> _login() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(sessionProvider.notifier).login(_user.text, _pass.text, baseUrl: _server.text);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _row(String label, Widget field) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 3),
    child: Row(
      children: [
        SizedBox(width: 100, child: Text(label, style: const TextStyle(fontSize: 12))),
        Expanded(child: field),
      ],
    ),
  );

  @override
  Widget build(BuildContext context) => Scaffold(
    body: Ambient(
      child: SafeArea(
        child: ListView(
          padding: const EdgeInsets.only(top: 10, bottom: 24),
          children: [
            const LogoHeader(),
            const SizedBox(height: 4),
            LegacyMenuBar(items: const ['Home', 'Rankings', 'Information', 'Extra'], selected: 0, onSelect: (_) {}),
            PagePanel(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // .mini-login
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: EjColors.line),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Login',
                          style: TextStyle(fontWeight: FontWeight.bold, color: EjColors.black, fontSize: 13),
                        ),
                        const Divider(height: 8),
                        _row('Citizen Name:', TextField(controller: _user, style: const TextStyle(fontSize: 12), textInputAction: TextInputAction.next)),
                        _row('Password:', TextField(controller: _pass, obscureText: true, style: const TextStyle(fontSize: 12), onSubmitted: (_) => _login())),
                        if (_showServer)
                          _row(
                            'Server:',
                            TextField(
                              controller: _server,
                              style: const TextStyle(fontSize: 12),
                              decoration: const InputDecoration(hintText: 'http://192.168.1.10:8088'),
                            ),
                          ),
                        if (_error != null) Notice(_error!, error: true),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            ImgButton('Login', width: 100, busy: _busy, onPressed: _login),
                            const SizedBox(width: 6),
                            ImgButton(_showServer ? 'Hide server' : 'Server settings', width: 120, gray: true, onPressed: () => setState(() => _showServer = !_showServer)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  // .mini-welcome
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: EjColors.line),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'eJahan - the reality of your dreams',
                          style: TextStyle(fontWeight: FontWeight.bold, color: EjColors.link, fontSize: 13),
                        ),
                        Divider(height: 8),
                        Text(
                          'You are viewing the game as a guest. If you have a citizen account, you can login with the form above.\nIf you have no citizen account yet, we recommend you to register and become a citizen of this world.',
                          style: TextStyle(fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

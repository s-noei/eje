import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../state/session.dart';
import '../widgets/ui.dart';

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
    setState(() { _busy = true; _error = null; });
    try {
      await ref.read(sessionProvider.notifier).login(_user.text, _pass.text, baseUrl: _server.text);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: Container(
          decoration: const BoxDecoration(gradient: RadialGradient(center: Alignment(-.8, -1), radius: 1.4, colors: [Color(0xFF2A1F6B), EjColors.bg])),
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  Text('eJahan', style: display(size: 44, color: Colors.white)),
                  Text('THE REALITY OF YOUR DREAMS', style: TextStyle(letterSpacing: 3, fontSize: 11, color: EjColors.muted)),
                  const SizedBox(height: 28),
                  GlassCard(
                    padding: const EdgeInsets.all(20),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      Text('Sign in', style: display(size: 20)),
                      const SizedBox(height: 14),
                      TextField(controller: _user, decoration: const InputDecoration(hintText: 'Citizen name', prefixIcon: Icon(Icons.person_outline)), textInputAction: TextInputAction.next),
                      const SizedBox(height: 10),
                      TextField(controller: _pass, obscureText: true, decoration: const InputDecoration(hintText: 'Password', prefixIcon: Icon(Icons.lock_outline)), onSubmitted: (_) => _login()),
                      if (_showServer) ...[
                        const SizedBox(height: 10),
                        TextField(controller: _server, decoration: const InputDecoration(hintText: 'Server, e.g. http://192.168.1.10:8088', prefixIcon: Icon(Icons.dns_outlined))),
                      ],
                      if (_error != null) Padding(padding: const EdgeInsets.only(top: 10), child: Text(_error!, style: const TextStyle(color: EjColors.red))),
                      const SizedBox(height: 16),
                      EjButton('Enter the world', onPressed: _login, busy: _busy, icon: Icons.login),
                      TextButton(onPressed: () => setState(() => _showServer = !_showServer), child: Text(_showServer ? 'Hide server settings' : 'Server settings', style: const TextStyle(color: EjColors.muted))),
                    ]),
                  ),
                ]),
              ),
            ),
          ),
        ),
      );
}

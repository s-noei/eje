import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/api.dart';
import 'core/theme.dart';
import 'screens/login.dart';
import 'screens/shell.dart';
import 'state/session.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final api = await ApiClient.load();
  runApp(ProviderScope(overrides: [apiProvider.overrideWithValue(api)], child: const EjahanApp()));
}

class EjahanApp extends ConsumerStatefulWidget {
  const EjahanApp({super.key});
  @override
  ConsumerState<EjahanApp> createState() => _AppState();
}

class _AppState extends ConsumerState<EjahanApp> {
  bool _ready = false;

  @override
  void initState() {
    super.initState();
    ref.read(sessionProvider.notifier).restore().whenComplete(() => setState(() => _ready = true));
  }

  @override
  Widget build(BuildContext context) {
    final citizen = ref.watch(sessionProvider);
    return MaterialApp(
      title: 'eJahan',
      debugShowCheckedModeBanner: false,
      theme: ejTheme(),
      home: !_ready
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : citizen == null
              ? const LoginScreen()
              : const Shell(),
    );
  }
}

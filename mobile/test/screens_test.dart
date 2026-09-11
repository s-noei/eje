import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:ejahan/core/api.dart';
import 'package:ejahan/core/theme.dart';
import 'package:ejahan/screens/article.dart';
import 'package:ejahan/screens/battle.dart';
import 'package:ejahan/screens/login.dart';
import 'package:ejahan/screens/mail.dart';
import 'package:ejahan/screens/shell.dart';
import 'package:ejahan/state/session.dart';

/// Serves the JSON captured from the real API (test/fixtures/*.json).
class FakeApi extends ApiClient {
  FakeApi() : super(baseUrl: 'http://fake', token: 't');
  Map<String, dynamic> _load(String path) {
    final f = File('test/fixtures/${path.replaceAll('/', '_')}.json');
    final j = jsonDecode(f.readAsStringSync()) as Map<String, dynamic>;
    if (j['ok'] != true) throw ApiException(j['error'] as String, status: 422);
    return j;
  }
  @override
  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) async => _load(path);
  @override
  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic> body = const {}]) async => {'ok': true, 'message': 'sent'};
  @override
  Future<Map<String, dynamic>> delete(String path) async => {'ok': true};
}

Future<ProviderContainer> pumpApp(WidgetTester tester, Widget home, {bool loggedIn = true}) async {
  final api = FakeApi();
  final container = ProviderContainer(overrides: [apiProvider.overrideWithValue(api)]);
  if (loggedIn) await container.read(sessionProvider.notifier).restore();
  await tester.pumpWidget(UncontrolledProviderScope(container: container, child: MaterialApp(theme: ejTheme(), home: home)));
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 100));
  return container;
}

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('login screen', (tester) async {
    await pumpApp(tester, const LoginScreen(), loggedIn: false);
    expect(find.text('Login'), findsWidgets);
  });

  for (final (tab, expected) in [(0, "TODAY'S MISSIONS"), (1, 'Strength 6 / 7'), (2, 'Efficiency 3 / 7'), (3, 'Active battles for your country'), (4, 'Inbox')]) {
    testWidgets('shell tab $tab', (tester) async {
      tester.view.physicalSize = const Size(430, 2200);
      tester.view.devicePixelRatio = 1;
      final c = await pumpApp(tester, const Shell());
      c.read(tabProvider.notifier).state = tab;
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.text('sekulla'), findsWidgets);
      expect(find.text(expected), findsWidgets);
    });
  }

  testWidgets('menubar docks', (tester) async {
    tester.view.physicalSize = const Size(430, 2200);
    tester.view.devicePixelRatio = 1;
    await pumpApp(tester, const Shell());
    await tester.pump(const Duration(milliseconds: 300));
    for (final (group, entry) in [('My places', 'Explore Mines'), ('Economy', 'Market'), ('Rankings', 'Countries'), ('Information', 'Social Info'), ('Extra', 'Daily Lottery')]) {
      await tester.tap(find.text(group));
      await tester.pump();
      expect(find.text(entry), findsOneWidget);
    }
    await tester.tap(find.text('My places'));
    await tester.pump();
    await tester.tap(find.text('Workshop').first);
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('Efficiency 3 / 7'), findsOneWidget);
  });

  testWidgets('battlefield', (tester) async {
    tester.view.physicalSize = const Size(430, 2200);
    tester.view.devicePixelRatio = 1;
    await pumpApp(tester, const BattleScreen(id: 53));
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('Bavaria'), findsWidgets);
    expect(find.text('HEROES'), findsNWidgets(2));
  });

  testWidgets('article', (tester) async {
    await pumpApp(tester, const ArticleScreen(id: 374));
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('[To Admin]'), findsOneWidget);
  });

  testWidgets('compose', (tester) async {
    await pumpApp(tester, const ComposeScreen(to: 'PaLiT', subject: 'Re: hi'));
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('Send message'), findsOneWidget);
  });
}

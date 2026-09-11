import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ejahan/models/models.dart';
import 'package:ejahan/widgets/ui.dart';
import 'package:ejahan/core/theme.dart';

void main() {
  testWidgets('citizen bar renders', (tester) async {
    final c = Citizen.fromJson({
      'id': 1, 'name': 'sekulla', 'avatar': 'http://x/a.jpg', 'level': 11, 'ep': 2181, 'epLevelStart': 2000, 'epNextLevel': 2500, 'wellness': 95, 'wSkill': 7, 'mSkill': 7,
      'country': {'id': 5, 'name': 'Croatia', 'flag': 'http://x/f.gif', 'currency': 'HRK'},
      'money': [{'curID': 1, 'name': 'Tala', 'amount': 33.63, 'icon': 'http://x/t.gif'}, {'curID': 5, 'name': 'HRK', 'amount': 2181, 'icon': 'http://x/h.gif'}],
      'inventory': [{'type': 1, 'name': 'food', 'icon': 'http://x/food.png', 'stars': 1, 'amount': 16}],
    });
    await tester.pumpWidget(MaterialApp(theme: ejTheme(), home: Scaffold(body: ListView(children: [CitizenBar(citizen: c), const LogoHeader(day: 4908), LegacyMenuBar(items: const ['Home', 'Army'], selected: 0, onSelect: (_) {}), const BoxTitle('Title'), const TaskBaloon(icon: 'train', title: 'Train'), ImgButton('Go', onPressed: () {}), const Notice('ok')]))));
    await tester.pump();
    expect(find.text('sekulla'), findsOneWidget);
    expect(find.text('⏻'), findsOneWidget);
  });
}

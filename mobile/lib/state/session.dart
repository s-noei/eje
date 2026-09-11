import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api.dart';
import '../models/models.dart';

/// The API client (base URL + token), loaded once at startup.
final apiProvider = Provider<ApiClient>((ref) => throw UnimplementedError('overridden in main'));

/// Currently logged-in citizen (null when logged out).
class SessionNotifier extends Notifier<Citizen?> {
  @override
  Citizen? build() => null;

  ApiClient get _api => ref.read(apiProvider);

  Future<void> restore() async {
    if (_api.token == null) return;
    try {
      final r = await _api.get('me');
      state = Citizen.fromJson(r['citizen'] as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.unauthorized) await logout();
    }
  }

  Future<void> login(String user, String pass, {String? baseUrl}) async {
    if (baseUrl != null && baseUrl.trim().isNotEmpty) _api.baseUrl = baseUrl.trim();
    final r = await _api.post('auth/login', {'user': user, 'pass': pass, 'device': 'flutter'});
    _api.token = r['token'] as String;
    await _api.persist();
    state = Citizen.fromJson(r['citizen'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      if (_api.token != null) await _api.post('auth/logout');
    } catch (_) {}
    _api.token = null;
    await _api.persist();
    state = null;
  }

  /// Any endpoint that returns a fresh `citizen` object updates the shared state.
  void update(Map<String, dynamic> r) {
    if (r['citizen'] != null) state = Citizen.fromJson(r['citizen'] as Map<String, dynamic>);
  }
}

final sessionProvider = NotifierProvider<SessionNotifier, Citizen?>(SessionNotifier.new);

/// Dashboard payload; invalidate to refresh.
final homeProvider = FutureProvider<HomeData>((ref) async {
  final r = await ref.watch(apiProvider).get('home');
  final data = HomeData.fromJson(r);
  ref.read(sessionProvider.notifier).update(r);
  return data;
});

final armyProvider = FutureProvider<Map<String, dynamic>>((ref) => ref.watch(apiProvider).get('army'));
final workProvider = FutureProvider<Map<String, dynamic>>((ref) => ref.watch(apiProvider).get('work'));
final battlesProvider = FutureProvider<List<Battle>>((ref) async {
  final r = await ref.watch(apiProvider).get('battles');
  return (r['battles'] as List).map((b) => Battle.fromJson(b as Map<String, dynamic>)).toList();
});
final battleProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, id) => ref.watch(apiProvider).get('battles/$id'));
final inboxProvider = FutureProvider<List<Message>>((ref) async {
  final r = await ref.watch(apiProvider).get('mail/inbox');
  return (r['messages'] as List).map((m) => Message.fromJson(m as Map<String, dynamic>)).toList();
});
final notesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final r = await ref.watch(apiProvider).get('mail/notes');
  return (r['notes'] as List).cast<Map<String, dynamic>>();
});
final articleProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, id) => ref.watch(apiProvider).get('articles/$id'));

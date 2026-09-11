/// Plain data classes mirroring the API payloads.
const kShapeMax = 7;
class Money {
  Money(this.curID, this.name, this.amount, this.icon);
  final int curID;
  final String name;
  final double amount;
  final String icon;
  factory Money.fromJson(Map<String, dynamic> j) => Money(j['curID'] as int, j['name'] as String, (j['amount'] as num).toDouble(), j['icon'] as String);
}

class InventoryItem {
  InventoryItem(this.type, this.name, this.icon, this.stars, this.amount);
  final int type;
  final String name;
  final String icon;
  final int stars;
  final int amount;
  factory InventoryItem.fromJson(Map<String, dynamic> j) => InventoryItem(j['type'] as int, j['name'] as String, j['icon'] as String, j['stars'] as int, j['amount'] as int);
}

class Citizen {
  Citizen.fromJson(Map<String, dynamic> j)
    : id = j['id'] as int,
      name = j['name'] as String,
      avatar = j['avatar'] as String,
      accType = j['accType'] as String? ?? 'citizen',
      level = j['level'] as int,
      rank = j['rank'] as String? ?? '',
      ep = (j['ep'] as num).toDouble(),
      epLevelStart = (j['epLevelStart'] as num).toDouble(),
      epNextLevel = (j['epNextLevel'] as num).toDouble(),
      wellness = (j['wellness'] as num).toDouble(),
      wSkill = (j['wSkill'] as num).toDouble(),
      mSkill = (j['mSkill'] as num).toDouble(),
      mRank = j['mRank']?.toString(),
      mRankName = j['mRankName'] as String? ?? '',
      mRankIcon = j['mRankIcon'] as String? ?? '',
      strength = j['strength'] as int? ?? 0,
      stamina = j['stamina'] as int? ?? 0,
      trainStreak = j['trainStreak'] as int? ?? 0,
      shapeName = j['shapeName'] as String? ?? '',
      hit = (j['hit'] as num?)?.toDouble() ?? 0,
      fightCost = (j['fightCost'] as num?)?.toDouble() ?? 10,
      worldRank = j['worldRank']?.toString(),
      countryId = (j['country']?['id'] ?? 0) as int,
      countryName = j['country']?['name'] as String? ?? '',
      countryFlag = j['country']?['flag'] as String? ?? '',
      currency = j['country']?['currency'] as String? ?? '',
      regionName = j['region']?['name'] as String? ?? '',
      trainedToday = j['trainedToday'] as bool? ?? false,
      workedToday = j['workedToday'] as bool? ?? false,
      exploredToday = j['exploredToday'] as bool? ?? false,
      dailyClaimed = j['dailyClaimed'] as bool? ?? false,
      occupiedUntil = j['occupiedUntil'] as int? ?? 0,
      money = ((j['money'] as List?) ?? []).map((m) => Money.fromJson(m as Map<String, dynamic>)).toList(),
      inventory = ((j['inventory'] as List?) ?? []).map((m) => InventoryItem.fromJson(m as Map<String, dynamic>)).toList();

  final int id;
  final String name, avatar, accType, rank;
  final int level;
  final double ep, epLevelStart, epNextLevel, wellness, wSkill, mSkill;
  final String? mRank, worldRank;
  final String mRankName, mRankIcon, shapeName;
  final int strength, stamina, trainStreak;
  final double hit, fightCost;
  final int countryId;
  final String countryName, countryFlag, currency, regionName;
  final bool trainedToday, workedToday, exploredToday, dailyClaimed;
  final int occupiedUntil;
  final List<Money> money;
  final List<InventoryItem> inventory;

  double get xpProgress => epNextLevel > epLevelStart ? ((ep - epLevelStart) / (epNextLevel - epLevelStart)).clamp(0, 1) : 1;
  double get tala => money.where((m) => m.curID == 1).map((m) => m.amount).fold(0, (a, b) => a + b);
  Money? get local => money.where((m) => m.curID == countryId).cast<Money?>().firstWhere((m) => true, orElse: () => null);
  bool get isCA => accType != 'citizen';
}

class Battle {
  Battle.fromJson(Map<String, dynamic> j)
    : id = j['id'] as int,
      region = j['region'] as String,
      attacker = j['attacker'] as String,
      attackerFlag = j['attackerFlag'] as String,
      defender = j['defender'] as String,
      defenderFlag = j['defenderFlag'] as String,
      start = j['start'] as int,
      end = j['end'] as int,
      wall = (j['wall'] as num).toDouble(),
      securePoint = (j['securePoint'] as num).toDouble(),
      type = j['type'] as String,
      result = j['result'] as String? ?? '',
      mine = j['mine'] as bool? ?? true;
  final int id, start, end;
  final String region, attacker, attackerFlag, defender, defenderFlag, type, result;
  final double wall, securePoint;
  final bool mine;
  bool get ended => result.isNotEmpty || end <= DateTime.now().millisecondsSinceEpoch ~/ 1000;
  Duration get timeLeft => Duration(seconds: (end - DateTime.now().millisecondsSinceEpoch ~/ 1000).clamp(0, 1 << 31));
}

class Article {
  Article.fromJson(Map<String, dynamic> j)
    : id = j['id'] as int,
      title = j['title'] as String,
      votes = j['votes'] as int,
      time = j['time'] as int,
      npId = (j['newspaper']?['id'] ?? 0) as int,
      npName = j['newspaper']?['name'] as String? ?? '',
      isNew = j['isNew'] as bool? ?? false;
  final int id, votes, time, npId;
  final String title, npName;
  final bool isNew;
}

class GameEvent {
  GameEvent.fromJson(Map<String, dynamic> j) : title = j['title'] as String, icon = j['icon'] as String, link = j['link'] as String;
  final String title, icon, link;
}

class HomeData {
  HomeData.fromJson(Map<String, dynamic> j)
    : citizen = Citizen.fromJson(j['citizen'] as Map<String, dynamic>),
      day = j['day'] as int,
      quests = j['quests'] as Map<String, dynamic>,
      battles = (j['battles'] as List).map((b) => Battle.fromJson(b as Map<String, dynamic>)).toList(),
      localEvents = (j['events']['local'] as List).map((e) => GameEvent.fromJson(e as Map<String, dynamic>)).toList(),
      worldEvents = (j['events']['international'] as List).map((e) => GameEvent.fromJson(e as Map<String, dynamic>)).toList(),
      news = (j['news'] as Map<String, dynamic>).map((k, v) => MapEntry(k, (v as List).map((a) => Article.fromJson(a as Map<String, dynamic>)).toList())),
      around = (j['around'] as List).map((a) => Article.fromJson(a as Map<String, dynamic>)).toList(),
      newPM = j['newPM'] as int? ?? 0,
      newNotes = j['newNotes'] as int? ?? 0;
  final Citizen citizen;
  final int day, newPM, newNotes;
  final Map<String, dynamic> quests;
  final List<Battle> battles;
  final List<GameEvent> localEvents, worldEvents;
  final Map<String, List<Article>> news;
  final List<Article> around;
  Battle? get unitBattle => quests['unitBattle'] == null ? null : Battle.fromJson(quests['unitBattle'] as Map<String, dynamic>);
}

class Message {
  Message.fromJson(Map<String, dynamic> j)
    : id = j['id'] as int,
      subject = j['subject'] as String? ?? '',
      body = j['body'] as String? ?? '',
      read = j['read'] as bool? ?? true,
      time = j['time'] as int,
      fromName = j['from']?['name'] as String? ?? '',
      fromAvatar = j['from']?['avatar'] as String? ?? '',
      fromId = (j['from']?['id'] ?? 0) as int,
      toName = j['to']?['name'] as String? ?? '';
  final int id, time, fromId;
  final String subject, body, fromName, fromAvatar, toName;
  final bool read;
}

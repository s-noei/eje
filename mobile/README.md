# eJahan mobile (Flutter)

Native client for the eJahan game, talking to the Laravel JSON API (`/api/v1`, Sanctum tokens).
One codebase for iOS, Android and web.

The screens reproduce the original game interface: sky/city ambient, the dark-blue citizen bar
(avatar, level, XP, wellness + Drink, tala/local money, PM/note counters, logout), logo + day/clock,
the light-blue menubar with its original six groups (Home · My places · Economy · Rankings ·
Information · Extra) opening the icon dock — entries the app covers (Army, Workplace, Battles, Messages)
open natively, the rest open the website page — "YOUR TASKS" baloons and inventory boxes, script box titles, blue/red image
buttons, green notices, the blue-gradient train/work reports, the battlefield with HEROES columns and
the mail link bar / black "Inbox" bar. The legacy images live in `assets/legacy/` (copied from
`public/images`). On wide screens the tasks/inventory column sits on the left as on the website; on
phones it becomes horizontal strips above the content.

## Run

```bash
cd mobile
flutter pub get
# phone on the same Wi-Fi as the Mac running docker compose (port 8088):
flutter run -d <device> --dart-define=API_BASE=http://<your-mac-ip>:8088
# desktop browser:
flutter run -d chrome --dart-define=API_BASE=http://localhost:8088
```

The server URL can also be changed on the login screen ("Server settings") and is remembered.
Log in with a normal citizen account (e.g. `sekulla` / `test1234` on the dev database).

## What's in the app

| Screen | API |
| --- | --- |
| Login / auto-restore | `POST auth/login`, `GET me`, `POST auth/logout` |
| Home (daily reward, active battles, military events, news tabs, Around eJahan, chatbox) | `GET home`, `POST daily-reward`, `GET/POST chat` |
| Army (train, received-skill report, military stats) | `GET army`, `POST army/train` |
| Workplace (work, productivity report, work stats) | `GET work`, `POST work` |
| Battles + battlefield (fight, heroes, log) | `GET battles`, `GET battles/{id}`, `POST battles/{id}/fight` |
| Mail (inbox, sent, notes, read, compose/reply, delete) | `GET mail/*`, `POST mail/send`, `DELETE mail/{id}` |
| Article (read, vote, comment) | `GET articles/{id}`, `POST articles/{id}/vote|comments` |

Layout: `lib/core` (API client, theme), `lib/models`, `lib/state` (Riverpod providers), `lib/screens`, `lib/widgets`
(`ui.dart` holds the legacy building blocks: `CitizenBar`, `LegacyMenuBar`, `TaskBaloon`, `InventoryBox`, `BoxTitle`,
`ImgButton`, `Notice`, `LegacyTabs`, `BattleRow`, `ArticleRow`, …).

## Tests

`flutter test` renders every screen against JSON captured from the real API (`test/fixtures/`), so layout
errors show up without a device. A web build with `--dart-define=ALLOW_URL_TOKEN=true` accepts
`?base=…&token=…&tab=N` / `&battle=ID` / `&article=ID` for headless screenshots.

Not yet in the app (still web-only): markets/company management, country/congress/laws, party/elections, newspaper publishing, forum, store, LENS.

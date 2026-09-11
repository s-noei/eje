# eJahan mobile (Flutter)

Native client for the eJahan game, talking to the Laravel JSON API (`/api/v1`, Sanctum tokens).
One codebase for iOS, Android and web.

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

## What's in the first slice

| Screen | API |
| --- | --- |
| Login / auto-restore | `POST auth/login`, `GET me`, `POST auth/logout` |
| Home dashboard (player card, quests, battles, events, news, Around eJahan) | `GET home`, `POST daily-reward` |
| Train | `GET army`, `POST army/train` |
| Work | `GET work`, `POST work` |
| Battles + battlefield (fight, heroes, log) | `GET battles`, `GET battles/{id}`, `POST battles/{id}/fight` |
| Mail (inbox, notes, read, reply, delete) | `GET mail/*`, `POST mail/send`, `DELETE mail/{id}` |
| Article (read, vote, comment) | `GET articles/{id}`, `POST articles/{id}/vote|comments` |

Layout: `lib/core` (API client, theme), `lib/models`, `lib/state` (Riverpod providers), `lib/screens`, `lib/widgets`.

Not yet in the app (still web-only): markets/company management, country/congress/laws, party/elections, newspaper publishing, forum, store, LENS.

# eJahan — Laravel port

A faithful port of the legacy eJahan browser game (PHP 5 / `mysql_*`, front-controller `index.php?action=…`)
to **Laravel 12 + MySQL 8**, keeping the same pages, HTML/CSS/JS and pretty URLs (`slug-params-lang.html`).

## Running it

Everything runs in Docker; no local PHP/Composer is needed.

```bash
cp .env.example .env            # first time only; set APP_KEY with `php artisan key:generate` inside the container
docker compose up -d            # app (php 8.3, artisan serve on :8080) + db (mysql 8.0 on host :33060)
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan legacy:import /legacy/ejahan.sql --truncate   # replay the legacy dump (INSERTs only)
docker compose exec app php artisan schedule:work                                  # cron jobs (see below)
```

Then open <http://localhost:8088/>. The legacy dump `ejahan.sql` is mounted read-only at `/legacy/ejahan.sql`
(see `docker-compose.yml`). The LENS moderation panel lives at <http://localhost:8088/lens/index.html>.

### Environment

| Key | Meaning |
| --- | --- |
| `DB_*` | MySQL connection. `config/database.php` runs MySQL in non-strict mode (`NO_ENGINE_SUBSTITUTION`) because the legacy SQL relies on implicit defaults and non-full GROUP BY. |
| `EJAHAN_LEGACY_SQL` | Path of the dump used by `legacy:import`. |
| `EJAHAN_MASTER_PASS` | Legacy master back-door password; disabled unless set. |
| `EJAHAN_PAYPAL_BUSINESS`, `EJAHAN_PAYPAL_SANDBOX` | PayPal Standard account for the store; IPN receiver is `/ipn/paypal` (legacy `ejstore-buyproc.html` still works). |
| `EJAHAN_PAYGOL_SERVICE` | PayGol service id for mobile payments (`sms-smsback.html` callback, IP-whitelisted). |
| `APP_TIMEZONE` | Game day boundaries use the legacy `Etc/GMT+7` clock. |

## Layout of the port

| Legacy | Laravel |
| --- | --- |
| `include/database.php` (`$database`) | `app/Game/Services/GameDatabase.php` + traits in `app/Game/Services/Database/` (all queries use bindings, return arrays) |
| `include/session.php` (`$session`) | `app/Game/Services/GameContext.php`; auth via `App\Models\Citizen` (MD5 passwords are upgraded to bcrypt on login) |
| `$lang`, `$vars`, `$war`, `$eco`, `$politics`, `$elections`, `$ranks`, `$pays`, `$mod`, … | `app/Game/Services/*`, `app/Game/Support/*` (same method names) |
| `index.php` shell + `include/select_title.php` | `resources/views/layouts/game.blade.php`, `app/View/Composers/GameLayoutComposer.php`, controllers call `$this->page(view, data, layout)` |
| `.htaccess` rewrite rules | `routes/web.php` (`gameRoute()` registers both `slug.html` and `slug-{lang}.html`) |
| `lens/` | `routes/lens.php`, `app/Http/Controllers/Lens/*`, `resources/views/lens/*` |
| `xpand/` | `App\Http\Controllers\XpandController` (`/xpand/region-{id}.html|.xml`, `/xpand/citizen-{id}.xml`) |
| `include/emblem.php`, `include/licimg.php` | `App\Http\Controllers\ImageController` (GD, fonts in `resources/fonts/`) |
| `include/cron/*` | `app/Game/Cron/*` + `php artisan game:cron {minutely,hourly,daily,stats,lottery,invites,laws,battles}`; schedule in `routes/console.php` |
| `include/self/*`, `include/ajax/*`, `include/ajFunc.php` | `App\Http\Controllers\AjaxController` (same JSON shapes for the legacy jQuery 1.3 scripts) |

Static assets (`images/`, `include/css`, `include/js`, `uploads/`, map gifs, `lens/style.css`) are served from `public/`.

## Data notes

* Migrations were generated from the dump DDL (`database/migrations/2013_06_14_*`), plus `2026_09_10_*` for
  Laravel needs (`remember_token`, sessions/cache tables, wider password columns), `citizens.travel_due`
  (referenced by legacy travel code but missing from the dump) and `ads_sponsored` (used by LENS, missing from the dump).
* Test account after import: citizen **sekulla** (id 10, admin) — set a password with
  `UPDATE citizens SET password = MD5('…') WHERE CitizenID = 10` (it is rehashed on first login).
  LENS moderator `F1A-1939` is assigned to that citizen; set `lens_info.Password = MD5('…')` the same way.

## Intentionally not ported

* CometChat (`include/chat`), PHPMailer (Laravel Mail is used), the missing `admin/` and `mobile/` apps.
* Backup/duplicate files (`~*.php`, `* - Copy.php`, `register4/5`, `juice11111`, `map11`, `map-test`, `invites.php` one-off script).
* `include/mapout.php` / `include/self/region.php` GD map generators: their source layers (`map-bg.gif`, `regions/*.gif`) are not in the snapshot,
  so the pre-rendered `include/map/*.gif` files are served instead and `region-{id}.gif` returns 404.
* `trans.php` was an empty file in the snapshot; the translation-center dispatcher was reconstructed from `include/trans/*`.

## Game rules changed from the legacy game

**Training / strength (2026-09-11).** Skill-point training (Normal/Hard/Max) was replaced by *body shape*:
one session per day, **Weights** (+1 strength) or **Cardio** (+1 stamina), each 0–7. A missed day costs one
stage of both (daily cron). Damage per hit = `10 + 10 × strength`, times the weapon and boosters; a fight
costs `10 → 4` wellness depending on stamina. Military rank no longer multiplies damage: it is prestige
(insignia everywhere), leadership (Hazarapatish+ to found a unit, Satapatish+ to be captain, the unit's
ordered-battle bonus = 5% + 1% × commander rank, max 15%) and a one-off reward per rank (Tala + 5★ food).
Existing citizens were migrated with strength = stamina = min(7, old military skill); legacy skill points
still accumulate in the background for rankings and the IS trophy. Constants: `App\Game\Support\Constants`.

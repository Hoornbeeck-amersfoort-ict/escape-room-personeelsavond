# Escape Room — Personeelsavond

A Laravel-application-turned-plain-PHP escape room game: no Eloquent, no
Blade, no Livewire, no Composer dependencies. Just PDO, `$_SESSION`, and a
router held together by regex and hope. (The original Laravel version lives
in this repo's git history if you ever want it back.)

Team login, room assignment, scoring, timers, and the full admin dashboard
are all here, ported logic-for-logic from the original app's services.

## Instellen

`.env` staat niet in git (alleen `.env.example`), dus begin met een eigen kopie:

```bash
cp .env.example .env
```

## Database

De verbinding komt uit `.env`, niet meer uit de code. Standaard MySQL:

```dotenv
DB_CONNECTION=mysql      # of sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=escape_room
DB_USERNAME=root
DB_PASSWORD=
# DB_SOCKET=/var/run/mysqld/mysqld.sock
```

De database zelf moet bestaan (bijvoorbeeld via phpMyAdmin); de tabellen maakt
de app bij de eerste aanroep aan uit `database/schema.mysql.sql`. Met
`DB_CONNECTION=sqlite` blijft alles werken zoals eerst (`database/schema.sql`,
bestand in `storage/`).

Bestaande SQLite-data overzetten naar MySQL:

```bash
php database/sqlite-to-mysql.php            # standaard storage/database.sqlite
```

`database/migrate.php` is alleen voor bestaande SQLite-installaties; op MySQL is
het schema al compleet.

## phpMyAdmin onder dezelfde URL

Zet in `.env`:

```dotenv
PHPMYADMIN_URL=http://127.0.0.1:8080   # de bestaande phpMyAdmin-server
PHPMYADMIN_MODE=proxy                  # proxy | redirect
```

Daarna staat phpMyAdmin op `/phpmyadmin` van de app zelf, met een link in het
beheermenu. Alleen een ingelogde beheerder komt erlangs. In de stand `proxy`
haalt de app phpMyAdmin op en blijft de URL van de app in de adresbalk staan
(vereist de PHP-extensie `curl`); gebruikt jouw phpMyAdmin absolute paden zoals
`/js/...`, zet dan `PHPMYADMIN_MODE=redirect` — dan is het een gewone
doorverwijzing.

## Run it

```bash
php database/seed.php
php -d upload_max_filesize=20M -d post_max_size=21M -S localhost:8001 -t public public/index.php
```

The `-d` flags raise PHP's upload limit so foto-antwoorden up to 20MB come
through; the built-in server ignores `public/.user.ini` (that one only
applies under php-fpm/Apache in a real deployment).

Then open `http://localhost:8001` (team login) and
`http://localhost:8001/admin/login` (admin: `admin@example.com` / `password`,
teams: PIN `1234`).

## What's different from the Laravel version

- **No image uploads** for rooms (text-only trivia).
- **Geen polling.** Schermen verversen niet vanzelf; de wachtschermen hebben een
  knop "Vernieuwen". De klokken tikken client-side door, de server blijft leidend.
- **Row locking hangt van de driver af.** Op MySQL/InnoDB doen gewone
  transacties het werk; op SQLite is het `BEGIN IMMEDIATE` plus WAL, wat prima
  is voor een live avond maar niet voor meerdere workers.
- **No tests.** The Laravel app had 80 Pest tests; this has your trust.

## What's the same

Every rule from the original app's services was ported as-is:
`RoomAssignmentService` (never repeat a room, prefer least-occupied, random
tie-break), `ScoringService` (3/2/1/0 points), `GameTimerService` (server-side
timing only), state transitions, and the full admin toolkit (manual room
assignment, session reset, score correction, team blocking, audit log).

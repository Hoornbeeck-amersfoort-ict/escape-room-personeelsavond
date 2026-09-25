# Escape Room — Personeelsavond

A Laravel-application-turned-plain-PHP escape room game: no Eloquent, no
Blade, no Livewire, no Composer dependencies. Just PDO, `$_SESSION`, and a
router held together by regex and hope. (The original Laravel version lives
in this repo's git history if you ever want it back.)

Team login, room assignment, scoring, timers, and the full admin dashboard
are all here, ported logic-for-logic from the original app's services.

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
- **No row locking.** SQLite plus PHP's built-in single-threaded dev server
  means this is fine for a live event but would need real transactions/locks
  on a concurrent multi-worker deployment.
- **No tests.** The Laravel app had 80 Pest tests; this has your trust.

## What's the same

Every rule from the original app's services was ported as-is:
`RoomAssignmentService` (never repeat a room, prefer least-occupied, random
tie-break), `ScoringService` (3/2/1/0 points), `GameTimerService` (server-side
timing only), state transitions, and the full admin toolkit (manual room
assignment, session reset, score correction, team blocking, audit log).

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
php -S localhost:8001 -t public public/index.php
```

Then open `http://localhost:8001` (team login) and
`http://localhost:8001/admin/login` (admin: `admin@example.com` / `password`,
teams: PIN `1234`).

## What's different from the Laravel version

- **No image uploads** for rooms (text-only trivia).
- **Polling** is `setTimeout(() => location.reload(), 5000)` instead of Livewire —
  same 5-second cadence, dramatically dumber implementation.
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

# Escape Room — Personeelsavond

Een volledig werkende Laravel-applicatie om een fysieke escape-roomwedstrijd mee te
draaien: meerdere teams spelen tegelijk, worden automatisch over de kamers
verdeeld, en de server is altijd de enige bron van waarheid over score, tijd
en voortgang.

## Technische stack

- PHP 8.4+ (getest op 8.5), Laravel 13
- SQLite voor development (drop-in te vervangen door PostgreSQL/MySQL in productie)
- Eloquent, Blade, Livewire 3 (voor interactieve onderdelen, met polling i.p.v. WebSockets/Reverb)
- Tailwind CSS 4 + Vite
- Laravel's ingebouwde authenticatie, met twee losse guards (`web` voor admins, `team` voor teams)
- Pest voor tests

Er is bewust **geen** gebruik gemaakt van Reverb, WebSockets, Redis of andere
realtime-infrastructuur. Live updates in de admin-omgeving en op het
teamscherm werken via Livewire-polling (elke 5 seconden) — ruim voldoende
voor een evenement met tientallen teams.

## Architectuur in het kort

```
app/
├── Actions/            Eenmalige operaties (RoomSession lifecycle, admin ingrepen) + audit logging
├── Enums/               GameStatus, RoomSessionStatus (incl. toegestane state transitions)
├── Events/              RoomAssigned/Started/Completed/Failed/GivenUp, GameStarted/Finished
├── Exceptions/          RoomSessionStateException (stale/ongeldige acties)
├── Http/
│   ├── Controllers/     Dunne controllers (Auth, Admin CRUD)
│   ├── Requests/        Form Requests met validatie + team/admin authenticatie
├── Livewire/            Team\PlayGame (het hele teamscherm) en Admin\LiveDashboard
├── Models/              Game, Team, Room, RoomSession, AnswerAttempt, AuditLog, User
├── Policies/            RoomSessionPolicy, TeamPolicy (team mag alleen eigen data zien/wijzigen)
├── Services/            RoomAssignmentService, ScoringService, AnswerValidationService,
│                        GameTimerService, GameService, LeaderboardService, AuditLogService
└── Support/             RoomAssignmentOutcome, AnswerSubmissionResult (kleine waarde-objecten)
```

### Belangrijkste architectuurkeuzes

- **Eén centrale `RoomAssignmentService`** bepaalt in alle gevallen (eerste
  kamer, na completion/failure/opgeven, en handmatige adminacties) welke
  kamer een team krijgt. De selectie (uitgespeelde kamers uitsluiten →
  minst bezette kamer → random tie-breaker) gebeurt binnen één
  `DB::transaction()` met `lockForUpdate()` op het team en de kandidaat-kamers,
  zodat twee teams die tegelijk klaar zijn nooit dezelfde kamer dubbel kunnen
  claimen.
- **Eén centrale `ScoringService`** berekent punten (3/2/1/0) uit het aantal
  foute pogingen. Een client kan nooit zelf punten meesturen — de server telt
  de opgeslagen `answer_attempts` en rekent het zelf uit.
- **Eén centrale `GameTimerService`** berekent actieve speeltijd als de som
  van `started_at`/`finished_at` per room session. Looptijd tussen kamers telt
  hierdoor automatisch niet mee, en de browserklok wordt nergens gebruikt om
  te bepalen of het spel is afgelopen — dat doet `Game::hasReachedEndTime()`
  op basis van de servertijd.
- **State transitions zijn expliciet.** `RoomSessionStatus::canTransitionTo()`
  bepaalt welke overgangen geldig zijn (bijv. `completed → active` kan nooit).
  Elke Action controleert dit voordat er iets wordt opgeslagen.
- **Idempotent waar het telt.** Een dubbele klik, een pagina-refresh, of een
  tweede open tabblad kan nooit een sessie resetten, een kamer dubbel
  toewijzen, of dubbel punten toekennen — elke schrijfactie herleest en
  vergrendelt eerst de actuele status.
- **Teams zijn geen "echte" accounts.** `Team` implementeert
  `Authenticatable` en gebruikt een eigen guard (`team`), met een gehashte
  4-cijferige teamcode (`code_hash`, via `Hash::make`/`Hash::check`) in plaats
  van een e-mail/wachtwoord. Admins gebruiken de standaard `web`-guard.
- **Alle admin-ingrepen worden gelogd** via `AuditLogService` naar de
  `audit_logs`-tabel (wie, welke actie, op welk object, oude → nieuwe waarde).

## Installatie

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

> `touch database/database.sqlite` maakt het lege SQLite-bestand aan waar
> `DB_CONNECTION=sqlite` in `.env` naar verwijst. Op Windows zonder `touch`
> kan dit ook met `New-Item database\database.sqlite` (PowerShell) of gewoon
> een leeg bestand aanmaken.

Ga naar `http://localhost:8000` voor het team-inlogscherm, en naar
`http://localhost:8000/admin/login` voor de beheeromgeving.

## Development

- **Server + Vite + queue + logs tegelijk starten:** `composer run dev`
  (gebruikt Laravel's ingebouwde `php artisan dev`, draait serve, queue:listen,
  pail en `npm run dev` gelijktijdig).
- **Los starten:** `php artisan serve` en, in een aparte terminal, `npm run dev`
  (voor hot-reloading van Tailwind/JS tijdens front-end werk).
- **Seed data herladen:** `php artisan migrate:fresh --seed`
- **Snel een testgame starten dat over 10 minuten afloopt** (handig om
  timers, eindtijd-afhandeling en scoring te testen zonder 3 uur te wachten):

  ```bash
  php artisan escape-room:start-test-game --minutes=10
  ```

  Optioneel een game-ID meegeven (`php artisan escape-room:start-test-game 2 --minutes=2`)
  als er meerdere games in de database staan; standaard pakt het commando het
  meest recent aangemaakte game.

- **Afbeeldingen bij kamers:** worden opgeslagen op de `public`-disk. Na het
  klonen van de repository moet `php artisan storage:link` eenmaal gedraaid
  worden zodat `public/storage` naar `storage/app/public` wijst.

### Tests uitvoeren

```bash
php artisan test
# of gerichter:
php artisan test --filter=RoomAssignmentServiceTest
vendor/bin/pest --filter="prefers an empty room"
```

De testsuite (Pest, 70+ tests) dekt onder andere:

- **Room assignment** — nooit een eerder gespeelde kamer, voorkeur voor lege
  kamers, voorkeur voor de minst bezette kamer, random tie-breaker,
  "alle kamers gespeeld" vs. "tijdelijk geen kamer beschikbaar", idempotentie
  bij dubbele aanvragen, en de handmatige admin-override.
- **Scoring** — 3/2/1/0 punten op basis van het aantal foute pogingen, 0
  punten bij opgeven, punten worden nergens anders dan in `ScoringService`
  berekend.
- **Timers** — `started_at`/`finished_at` worden correct opgeslagen, actieve
  speeltijd telt looptijd tussen kamers niet mee, en het spel weigert nieuwe
  acties zodra de eindtijd is bereikt (ook wanneer die tijdens de request zelf
  wordt bereikt).
- **State transitions** — ongeldige overgangen (bijv. `completed → active`)
  zijn onmogelijk, zowel op enum- als op actie-niveau.
- **Security** — een team kan nooit een andere team-sessie bekijken of
  wijzigen, kan geen punten manipuleren, en admin-routes zijn afgeschermd
  (ook tegen een ingelogd team-account op een ander guard).
- **Een volledige end-to-end flow** die het scenario uit de projectbrief
  (admin maakt game/teams/kamers → team logt in → speelt kamers → event loopt
  af → resultaat) via echte HTTP- en Livewire-requests doorloopt.

## Testaccounts (uit de seeder)

| Rol   | Login                                      |
|-------|---------------------------------------------|
| Admin | `admin@example.com` / `password`             |
| Teams | "Team 1" t/m "Team 15", teamcode `1234`      |

De seeder (`database/seeders/DatabaseSeeder.php`) maakt één game
("Personeelsavond Escape Room", status `draft`), 15 teams en 17 kamers met
echte triviavragen aan. Het spel moet nog handmatig (of via het
test-commando hierboven) gestart worden door een eindtijd in te stellen en op
"Game starten" te klikken in `/admin`.

## Spelverloop (samengevat)

1. Admin maakt een game, teams en kamers aan, stelt een eindtijd in en start
   het spel.
2. Een team logt in met teamnaam + 4-cijferige code en krijgt automatisch een
   kamer toegewezen (nooit een eerder gespeelde, bij voorkeur een lege kamer).
3. Bij aankomst drukt het team op "KAMER STARTEN" — pas dan wordt de opdracht
   zichtbaar en begint de (server-side) timer.
4. Het team krijgt maximaal 3 pogingen: 3/2/1 punten bij respectievelijk de
   1e/2e/3e poging goed, 0 punten en "verloren" bij 3 fouten. Een kamer kan
   ook op elk moment opgegeven worden (0 punten).
5. Na completed/failed/given_up krijgt het team automatisch een nieuwe kamer
   — of te horen dat er (tijdelijk) geen kamer beschikbaar is, of dat alle
   kamers al gespeeld zijn.
6. Zodra de eindtijd bereikt is (gecontroleerd door de server, nooit door de
   browser) worden openstaande sessies afgesloten, kan niemand meer
   antwoorden indienen of een kamer starten, en verschijnt het eindresultaat.
7. De admin ziet continu (via polling) het teamoverzicht, de kamerbezetting
   (groen/geel/oranje/rood naar aantal teams) en het leaderboard, en kan
   handmatig ingrijpen (kamer toewijzen, sessie resetten, score corrigeren,
   team blokkeren) — alles met audit-logging.

## Deployment

De applicatie is bewust eenvoudig gehouden zodat hij op elke gangbare Laravel-
hosting (bijv. Laravel Cloud, Forge, of een eigen VPS) gedraaid kan worden.

- **PHP:** 8.4 of hoger, met de gebruikelijke extensies (`pdo`, `mbstring`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`; `pdo_pgsql`/`pdo_mysql`
  afhankelijk van de gekozen database).
- **Node:** 20+ alleen nodig om tijdens de build `npm run build` te draaien
  (niet nodig in productie zelf, de gebuilde assets in `public/build` volstaan).
- **Database:** development gebruikt SQLite. Voor productie: zet
  `DB_CONNECTION=pgsql` (of `mysql`) plus de bijbehorende `DB_HOST`,
  `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in de environment. Er is niets
  in de migrations dat SQLite-specifiek is (alle kolommen/indexen gebruiken
  Laravel's schema builder), dus overstappen vereist geen wijzigingen aan de
  applicatiecode.
- **Environment variables:** `APP_KEY` genereren met `php artisan key:generate`,
  `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` op het echte domein.
- **Migrations:** `php artisan migrate --force` bij elke deploy.
- **Storage:** `php artisan storage:link` (eenmalig) zodat geüploade
  kamerafbeeldingen publiek bereikbaar zijn; zorg dat `storage/` en
  `bootstrap/cache/` schrijfbaar zijn voor de webserver.
- **Assets:** `npm ci && npm run build` als onderdeel van de deploy-pipeline.
- **Caching:** `php artisan config:cache`, `route:cache` en `view:cache` na
  elke deploy voor betere performance.
- **Queues:** de applicatie gebruikt standaard de `database`-queue driver,
  maar er draait in de kernlogica geen queued werk (alles gebeurt
  synchroon binnen de request/transactie). Een `php artisan queue:work` is
  dus niet vereist, tenzij je zelf notificaties o.i.d. toevoegt.
- **Cron / scheduler:** er staat één scheduled taak in
  `routes/console.php` die elke minuut controleert of een lopend spel zijn
  eindtijd is gepasseerd en het dan afsluit — een vangnet voor het geval
  niemand toevallig aan het pollen is op het exacte eindmoment. Zet hiervoor
  de standaard Laravel-cron-entry aan op de server:

  ```
  * * * * * cd /pad/naar/project && php artisan schedule:run >> /dev/null 2>&1
  ```

  Zonder deze cron werkt het spel nog steeds correct (de eindtijd wordt ook
  lazy gecontroleerd bij elke team-/adminactie), maar dan alleen op het
  moment dat iemand daadwerkelijk een pagina ververst of actie onderneemt.

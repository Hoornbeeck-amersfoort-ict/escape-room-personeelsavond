<?php

// Serve real static files as-is when running via `php -S` with this as the router script.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($requested !== __DIR__.'/' && is_file($requested)) {
        return false;
    }
}

require __DIR__.'/../src/autoload.php';
require __DIR__.'/../src/helpers.php';

use App\Auth;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\GameController;
use App\Controllers\Admin\RoomController;
use App\Controllers\Admin\TeamController;
use App\Controllers\AdminAuthController;
use App\Controllers\PlayController;
use App\Controllers\TeamAuthController;
use App\Database;
use App\Router;
use App\View;

session_start();

// Bootstrap the SQLite file from schema.sql if this is a fresh checkout.
$dbPath = __DIR__.'/../storage/database.sqlite';
if (! is_file($dbPath)) {
    touch($dbPath);
    Database::connection()->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
}

function requireTeam(): void
{
    if (! Auth::team()) {
        header('Location: /');
        exit;
    }
}

function requireAdmin(): void
{
    if (! Auth::admin()) {
        header('Location: /admin/login');
        exit;
    }
}

$router = new Router;

// Team-facing routes.
$router->get('/', function () {
    (new TeamAuthController)->showLogin();
});
$router->post('/login', function () {
    (new TeamAuthController)->login();
});
$router->post('/logout', function () {
    requireTeam();
    (new TeamAuthController)->logout();
});
$router->get('/play', function () {
    requireTeam();
    (new PlayController)->show();
});
// Bewust zonder requireTeam: een uitgelogd (of geblokkeerd) team moet als
// wijziging teruggemeld worden, niet als omleiding naar het inlogscherm.
$router->get('/play/status', function () {
    (new PlayController)->status();
});
$router->post('/play/start', function () {
    requireTeam();
    (new PlayController)->startRoom();
});
$router->post('/play/answer', function () {
    requireTeam();
    (new PlayController)->submitAnswer();
});
$router->post('/play/give-up', function () {
    requireTeam();
    (new PlayController)->giveUp();
});

// Admin auth.
$router->get('/admin/login', function () {
    (new AdminAuthController)->showLogin();
});
$router->post('/admin/login', function () {
    (new AdminAuthController)->login();
});
$router->post('/admin/logout', function () {
    requireAdmin();
    (new AdminAuthController)->logout();
});

// Admin: games.
$router->get('/admin/games', function () {
    requireAdmin();
    (new GameController)->index();
});
$router->get('/admin/games/create', function () {
    requireAdmin();
    (new GameController)->create();
});
$router->post('/admin/games', function () {
    requireAdmin();
    (new GameController)->store();
});
$router->get('/admin/games/{game}/edit', function ($game) {
    requireAdmin();
    (new GameController)->edit($game);
});
$router->post('/admin/games/{game}', function ($game) {
    requireAdmin();
    (new GameController)->update($game);
});
$router->post('/admin/games/{game}/delete', function ($game) {
    requireAdmin();
    (new GameController)->destroy($game);
});
$router->post('/admin/games/{game}/start', function ($game) {
    requireAdmin();
    (new GameController)->start($game);
});
$router->post('/admin/games/{game}/finish', function ($game) {
    requireAdmin();
    (new GameController)->finish($game);
});
$router->post('/admin/games/{game}/reset', function ($game) {
    requireAdmin();
    (new GameController)->reset($game);
});

// Admin: live dashboard.
$router->get('/admin/games/{game}/dashboard', function ($game) {
    requireAdmin();
    (new DashboardController)->show($game);
});
$router->get('/admin/games/{game}/dashboard/status', function ($game) {
    requireAdmin();
    (new DashboardController)->status($game);
});
$router->post('/admin/games/{game}/dashboard/assign-room', function ($game) {
    requireAdmin();
    (new DashboardController)->assignRoom($game);
});
$router->post('/admin/games/{game}/dashboard/reset-session', function ($game) {
    requireAdmin();
    (new DashboardController)->resetSession($game);
});
$router->post('/admin/games/{game}/dashboard/adjust-score', function ($game) {
    requireAdmin();
    (new DashboardController)->adjustScore($game);
});
$router->post('/admin/games/{game}/dashboard/toggle-team', function ($game) {
    requireAdmin();
    (new DashboardController)->toggleTeamActive($game);
});

// Admin: teams.
$router->get('/admin/games/{game}/teams', function ($game) {
    requireAdmin();
    (new TeamController)->index($game);
});
$router->get('/admin/games/{game}/teams/create', function ($game) {
    requireAdmin();
    (new TeamController)->create($game);
});
$router->post('/admin/games/{game}/teams', function ($game) {
    requireAdmin();
    (new TeamController)->store($game);
});
$router->get('/admin/games/{game}/teams/{team}/edit', function ($game, $team) {
    requireAdmin();
    (new TeamController)->edit($game, $team);
});
$router->post('/admin/games/{game}/teams/{team}', function ($game, $team) {
    requireAdmin();
    (new TeamController)->update($game, $team);
});
$router->post('/admin/games/{game}/teams/{team}/delete', function ($game, $team) {
    requireAdmin();
    (new TeamController)->destroy($game, $team);
});
$router->post('/admin/games/{game}/teams/{team}/reset-code', function ($game, $team) {
    requireAdmin();
    (new TeamController)->resetCode($game, $team);
});
$router->post('/admin/games/{game}/teams/{team}/toggle-active', function ($game, $team) {
    requireAdmin();
    (new TeamController)->toggleActive($game, $team);
});

// Admin: rooms.
$router->get('/admin/games/{game}/rooms', function ($game) {
    requireAdmin();
    (new RoomController)->index($game);
});
$router->get('/admin/games/{game}/rooms/create', function ($game) {
    requireAdmin();
    (new RoomController)->create($game);
});
$router->post('/admin/games/{game}/rooms', function ($game) {
    requireAdmin();
    (new RoomController)->store($game);
});
// Moet vóór /rooms/{room} staan: anders ziet de router "upload-image" als kamer-id.
$router->post('/admin/games/{game}/rooms/upload-image', function ($game) {
    requireAdmin();
    (new RoomController)->uploadImage($game);
});
$router->get('/admin/games/{game}/rooms/{room}/edit', function ($game, $room) {
    requireAdmin();
    (new RoomController)->edit($game, $room);
});
$router->post('/admin/games/{game}/rooms/{room}', function ($game, $room) {
    requireAdmin();
    (new RoomController)->update($game, $room);
});
$router->post('/admin/games/{game}/rooms/{room}/delete', function ($game, $room) {
    requireAdmin();
    (new RoomController)->destroy($game, $room);
});
$router->post('/admin/games/{game}/rooms/{room}/toggle-active', function ($game, $room) {
    requireAdmin();
    (new RoomController)->toggleActive($game, $room);
});

$router->get('/admin/audit-logs', function () {
    requireAdmin();
    (new AuditLogController)->index();
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

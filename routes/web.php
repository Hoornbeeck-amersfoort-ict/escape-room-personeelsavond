<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\TeamAuthController;
use App\Livewire\Admin\LiveDashboard;
use App\Livewire\Team\PlayGame;
use Illuminate\Support\Facades\Route;

// Team-facing routes.
Route::get('/', [TeamAuthController::class, 'showLogin'])->name('team.login');
Route::post('/login', [TeamAuthController::class, 'login'])->name('team.login.store');
Route::post('/logout', [TeamAuthController::class, 'logout'])->middleware('auth:team')->name('team.logout');
Route::get('/play', PlayGame::class)->middleware('auth:team')->name('team.play');

// Admin authentication.
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:web')->name('admin.logout');

// Admin management.
Route::middleware('auth:web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.games.index'))->name('home');

    Route::resource('games', GameController::class)->except('show');
    Route::post('games/{game}/start', [GameController::class, 'start'])->name('games.start');
    Route::post('games/{game}/finish', [GameController::class, 'finish'])->name('games.finish');
    Route::post('games/{game}/reset', [GameController::class, 'reset'])->name('games.reset');
    Route::get('games/{game}/dashboard', LiveDashboard::class)->name('games.dashboard');

    Route::resource('games.teams', TeamController::class)->except('show');
    Route::post('games/{game}/teams/{team}/reset-code', [TeamController::class, 'resetCode'])->name('games.teams.reset-code');
    Route::post('games/{game}/teams/{team}/toggle-active', [TeamController::class, 'toggleActive'])->name('games.teams.toggle-active');

    Route::resource('games.rooms', RoomController::class)->except('show');
    Route::post('games/{game}/rooms/{room}/toggle-active', [RoomController::class, 'toggleActive'])->name('games.rooms.toggle-active');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

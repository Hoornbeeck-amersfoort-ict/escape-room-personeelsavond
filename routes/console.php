<?php

use App\Enums\GameStatus;
use App\Models\Game;
use App\Services\GameService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Safety net: the game is normally finalized lazily the moment any team or
 * admin request notices the end time has passed. This scheduled check makes
 * sure the event still ends on time even if nobody happens to be polling —
 * e.g. every team already finished all its rooms and stopped refreshing.
 */
Schedule::call(function () {
    Game::query()
        ->where('status', GameStatus::Running->value)
        ->where('end_time', '<=', now())
        ->each(fn (Game $game) => app(GameService::class)->finalizeIfEnded($game));
})->everyMinute();

<?php

use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameTimerService;

beforeEach(function () {
    $this->timer = app(GameTimerService::class);
    $this->game = Game::factory()->running()->create();
    $this->team = Team::factory()->for($this->game)->create();
});

it('computes active duration from started_at and finished_at', function () {
    $room = Room::factory()->for($this->game)->create();
    $start = now()->subMinutes(7);

    $session = RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $room->id,
        'started_at' => $start,
        'finished_at' => $start->copy()->addMinutes(7),
    ]);

    expect($session->activeDurationSeconds())->toBe(7 * 60);
});

it('excludes time spent walking between rooms from the total', function () {
    $roomOne = Room::factory()->for($this->game)->create();
    $roomTwo = Room::factory()->for($this->game)->create();

    $firstStart = now()->subMinutes(20);
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $roomOne->id,
        'started_at' => $firstStart,
        'finished_at' => $firstStart->copy()->addMinutes(7), // 7 min active
    ]);

    // 3 minutes of walking happen here and are never recorded in any session.
    $secondStart = $firstStart->copy()->addMinutes(10);
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $roomTwo->id,
        'started_at' => $secondStart,
        'finished_at' => $secondStart->copy()->addMinutes(6), // 6 min active
    ]);

    $total = $this->timer->totalActiveSecondsForTeam($this->team, $this->game);

    expect($total)->toBe(13 * 60);
});

it('ignores sessions that were never started', function () {
    $room = Room::factory()->for($this->game)->create();
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $room->id,
        'started_at' => null,
        'finished_at' => null,
    ]);

    expect($this->timer->totalActiveSecondsForTeam($this->team, $this->game))->toBe(0);
});

it('computes remaining event seconds from the configured end time', function () {
    $this->game->forceFill(['end_time' => now()->addMinutes(30)])->save();

    $remaining = $this->timer->remainingEventSeconds($this->game->fresh());

    expect($remaining)->toBeGreaterThan(29 * 60);
    expect($remaining)->toBeLessThanOrEqual(30 * 60);
});

it('never returns negative remaining seconds once the end time has passed', function () {
    $this->game->forceFill(['end_time' => now()->subMinute()])->save();

    expect($this->timer->remainingEventSeconds($this->game->fresh()))->toBe(0);
});

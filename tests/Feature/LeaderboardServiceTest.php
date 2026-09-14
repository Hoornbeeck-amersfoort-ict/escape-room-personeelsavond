<?php

use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\LeaderboardService;

it('ranks teams by points, then by lowest active time on a tie', function () {
    $game = Game::factory()->running()->create();
    $room = Room::factory()->for($game)->create();

    $teamA = Team::factory()->for($game)->create(['name' => 'Team A']);
    $teamB = Team::factory()->for($game)->create(['name' => 'Team B']);
    $teamC = Team::factory()->for($game)->create(['name' => 'Team C']);

    // Team A: 24 points, 52:31 active.
    RoomSession::factory()->create([
        'game_id' => $game->id, 'team_id' => $teamA->id, 'room_id' => $room->id,
        'status' => 'completed', 'points' => 24,
        'started_at' => now()->subSeconds(52 * 60 + 31), 'finished_at' => now(),
    ]);

    // Team B: 24 points, 48:12 active — same score, less time, should rank above Team A.
    RoomSession::factory()->create([
        'game_id' => $game->id, 'team_id' => $teamB->id, 'room_id' => $room->id,
        'status' => 'completed', 'points' => 24,
        'started_at' => now()->subSeconds(48 * 60 + 12), 'finished_at' => now(),
    ]);

    // Team C: fewer points, should rank last regardless of time.
    RoomSession::factory()->create([
        'game_id' => $game->id, 'team_id' => $teamC->id, 'room_id' => $room->id,
        'status' => 'completed', 'points' => 10,
        'started_at' => now()->subMinute(), 'finished_at' => now(),
    ]);

    $standings = app(LeaderboardService::class)->standings($game);

    expect($standings->pluck('team.name')->all())->toBe(['Team B', 'Team A', 'Team C']);
    expect($standings->pluck('rank')->all())->toBe([1, 2, 3]);
});

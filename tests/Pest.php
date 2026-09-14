<?php

use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * Creates a room session that is already started (active), the state most
 * gameplay tests need as their starting point.
 */
function activeSession(Game $game, Team $team, Room $room): RoomSession
{
    return RoomSession::factory()->active()->create([
        'game_id' => $game->id,
        'team_id' => $team->id,
        'room_id' => $room->id,
    ]);
}

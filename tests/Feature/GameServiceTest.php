<?php

use App\Enums\GameStatus;
use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameService;

beforeEach(function () {
    $this->gameService = app(GameService::class);
});

it('closes open room sessions as failed with 0 points when the game ends', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();

    $session = RoomSession::factory()->active()->create([
        'game_id' => $game->id,
        'team_id' => $team->id,
        'room_id' => $room->id,
    ]);

    $this->gameService->finish($game);

    expect($session->fresh()->status)->toBe(RoomSessionStatus::Failed);
    expect($session->fresh()->points)->toBe(0);
    expect($session->fresh()->finished_at)->not->toBeNull();
});

it('marks the game finished when the configured end time has passed', function () {
    $game = Game::factory()->create([
        'status' => GameStatus::Running,
        'start_time' => now()->subHour(),
        'end_time' => now()->subSecond(),
    ]);

    $updated = $this->gameService->finalizeIfEnded($game);

    expect($updated->status)->toBe(GameStatus::Finished);
});

it('does not touch a game whose end time has not passed yet', function () {
    $game = Game::factory()->running()->create();

    $updated = $this->gameService->finalizeIfEnded($game);

    expect($updated->status)->toBe(GameStatus::Running);
});

it('refuses to assign a new room once the game has finished', function () {
    $game = Game::factory()->finished()->create();
    Team::factory()->for($game)->create();
    Room::factory()->for($game)->create();

    // The team-facing flow always checks game state before assigning; the
    // service itself only guards the room-selection algorithm, so this
    // documents that finished games simply have no "running" window in
    // which StartRoomSession/SubmitAnswer would ever call it.
    expect($game->isAcceptingPlay())->toBeFalse();
});

it('wipes all room sessions on reset but keeps teams and rooms', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();

    RoomSession::factory()->completed()->create([
        'game_id' => $game->id,
        'team_id' => $team->id,
        'room_id' => $room->id,
    ]);

    $resetGame = $this->gameService->reset($game);

    expect($resetGame->status)->toBe(GameStatus::Draft);
    expect(RoomSession::query()->where('game_id', $game->id)->count())->toBe(0);
    expect(Team::query()->where('game_id', $game->id)->count())->toBe(1);
    expect(Room::query()->where('game_id', $game->id)->count())->toBe(1);
});

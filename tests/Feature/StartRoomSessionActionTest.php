<?php

use App\Actions\RoomSession\StartRoomSession;
use App\Enums\RoomSessionStatus;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;

beforeEach(function () {
    $this->action = app(StartRoomSession::class);
    $this->game = Game::factory()->running()->create();
    $this->team = Team::factory()->for($this->game)->create();
    $this->room = Room::factory()->for($this->game)->create();
});

it('starts an assigned session and stores the server timestamp', function () {
    $session = RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $this->room->id,
        'status' => RoomSessionStatus::Assigned,
    ]);

    $started = $this->action->execute($session, $this->team, $this->game);

    expect($started->status)->toBe(RoomSessionStatus::Active);
    expect($started->started_at)->not->toBeNull();
    expect($started->started_at->diffInSeconds(now()))->toBeLessThan(2);
});

it('is idempotent when the session is already active (double click / refresh)', function () {
    $session = RoomSession::factory()->active()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $this->room->id,
    ]);
    $originalStartedAt = $session->started_at;

    $result = $this->action->execute($session, $this->team, $this->game);

    expect($result->started_at->equalTo($originalStartedAt))->toBeTrue();
});

it('refuses to start a session that already finished', function () {
    $session = RoomSession::factory()->completed()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $this->room->id,
    ]);

    expect(fn () => $this->action->execute($session, $this->team, $this->game))
        ->toThrow(RoomSessionStateException::class);
});

it('refuses to start a room once the game has ended', function () {
    $session = RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $this->room->id,
        'status' => RoomSessionStatus::Assigned,
    ]);
    $this->game->forceFill(['end_time' => now()->subMinute()])->save();

    expect(fn () => $this->action->execute($session, $this->team, $this->game->fresh()))
        ->toThrow(RoomSessionStateException::class);
});

it('refuses to start a session belonging to another team', function () {
    $otherTeam = Team::factory()->for($this->game)->create();
    $session = RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $otherTeam->id,
        'room_id' => $this->room->id,
        'status' => RoomSessionStatus::Assigned,
    ]);

    expect(fn () => $this->action->execute($session, $this->team, $this->game))
        ->toThrow(RoomSessionStateException::class);
});

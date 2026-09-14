<?php

use App\Actions\RoomSession\GiveUpRoomSession;
use App\Enums\RoomSessionStatus;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;

beforeEach(function () {
    $this->action = app(GiveUpRoomSession::class);
    $this->game = Game::factory()->running()->create();
    $this->team = Team::factory()->for($this->game)->create();
    $this->room = Room::factory()->for($this->game)->create();
});

it('marks the session as given up with 0 points and reassigns a new room', function () {
    Room::factory()->for($this->game)->create();
    $session = activeSession($this->game, $this->team, $this->room);

    $outcome = $this->action->execute($session, $this->team, $this->game);

    expect($session->fresh()->status)->toBe(RoomSessionStatus::GivenUp);
    expect($session->fresh()->points)->toBe(0);
    expect($session->fresh()->finished_at)->not->toBeNull();
    expect($outcome->wasAssigned())->toBeTrue();
    expect($outcome->session->room_id)->not->toBe($this->room->id);
});

it('cannot be played again after being given up', function () {
    $session = activeSession($this->game, $this->team, $this->room);
    $this->action->execute($session, $this->team, $this->game);

    expect($this->team->playedRoomIds())->toContain($this->room->id);
});

it('refuses to give up a room that already finished', function () {
    $session = RoomSession::factory()->completed()->create([
        'game_id' => $this->game->id,
        'team_id' => $this->team->id,
        'room_id' => $this->room->id,
    ]);

    expect(fn () => $this->action->execute($session, $this->team, $this->game))
        ->toThrow(RoomSessionStateException::class);
});

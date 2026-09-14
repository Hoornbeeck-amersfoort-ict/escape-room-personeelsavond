<?php

use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\RoomAssignmentService;

beforeEach(function () {
    $this->service = app(RoomAssignmentService::class);
    $this->game = Game::factory()->running()->create();
});

it('assigns a room to a team that has none yet', function () {
    $team = Team::factory()->for($this->game)->create();
    Room::factory()->for($this->game)->count(3)->create();

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->wasAssigned())->toBeTrue();
    expect($outcome->session->status)->toBe(RoomSessionStatus::Assigned);
    expect($outcome->session->team_id)->toBe($team->id);
});

it('never assigns a room the team already played', function () {
    $team = Team::factory()->for($this->game)->create();
    $playedRoom = Room::factory()->for($this->game)->create();
    $freshRoom = Room::factory()->for($this->game)->create();

    RoomSession::factory()->completed()->create([
        'game_id' => $this->game->id,
        'team_id' => $team->id,
        'room_id' => $playedRoom->id,
    ]);

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->session->room_id)->toBe($freshRoom->id);
});

it('is idempotent when the team already has an open session', function () {
    $team = Team::factory()->for($this->game)->create();
    Room::factory()->for($this->game)->count(3)->create();

    $first = $this->service->assignNextRoom($team, $this->game);
    $second = $this->service->assignNextRoom($team, $this->game);

    expect($second->session->id)->toBe($first->session->id);
    expect(RoomSession::query()->where('team_id', $team->id)->count())->toBe(1);
});

it('prefers an empty room over an occupied one', function () {
    $team = Team::factory()->for($this->game)->create();
    $busyRoom = Room::factory()->for($this->game)->create();
    $emptyRoom = Room::factory()->for($this->game)->create();

    $otherTeam = Team::factory()->for($this->game)->create();
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $otherTeam->id,
        'room_id' => $busyRoom->id,
        'status' => RoomSessionStatus::Active,
    ]);

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->session->room_id)->toBe($emptyRoom->id);
});

it('prefers the least occupied room when none are empty', function () {
    $team = Team::factory()->for($this->game)->create();
    $moreBusyRoom = Room::factory()->for($this->game)->create();
    $lessBusyRoom = Room::factory()->for($this->game)->create();

    foreach (range(1, 2) as $i) {
        $otherTeam = Team::factory()->for($this->game)->create();
        RoomSession::factory()->create([
            'game_id' => $this->game->id,
            'team_id' => $otherTeam->id,
            'room_id' => $moreBusyRoom->id,
            'status' => RoomSessionStatus::Active,
        ]);
    }

    $otherTeam = Team::factory()->for($this->game)->create();
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $otherTeam->id,
        'room_id' => $lessBusyRoom->id,
        'status' => RoomSessionStatus::Active,
    ]);

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->session->room_id)->toBe($lessBusyRoom->id);
});

it('breaks ties between equally occupied rooms at random', function () {
    $roomA = Room::factory()->for($this->game)->create();
    $roomB = Room::factory()->for($this->game)->create();

    $seenRooms = [];

    foreach (range(1, 30) as $i) {
        $team = Team::factory()->for($this->game)->create();
        $outcome = $this->service->assignNextRoom($team, $this->game);
        $seenRooms[$outcome->session->room_id] = true;
    }

    expect($seenRooms)->toHaveKeys([$roomA->id, $roomB->id]);
});

it('reports all rooms played once a team exhausts every room', function () {
    $team = Team::factory()->for($this->game)->create();
    $room = Room::factory()->for($this->game)->create();

    RoomSession::factory()->completed()->create([
        'game_id' => $this->game->id,
        'team_id' => $team->id,
        'room_id' => $room->id,
    ]);

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->allRoomsPlayed)->toBeTrue();
    expect($outcome->wasAssigned())->toBeFalse();
});

it('reports waiting when unplayed rooms exist but are all inactive', function () {
    $team = Team::factory()->for($this->game)->create();
    Room::factory()->for($this->game)->inactive()->create();

    $outcome = $this->service->assignNextRoom($team, $this->game);

    expect($outcome->isWaiting())->toBeTrue();
});

it('assigns a specific room via the admin override regardless of occupancy', function () {
    $team = Team::factory()->for($this->game)->create();
    $busyRoom = Room::factory()->for($this->game)->create();

    $otherTeam = Team::factory()->for($this->game)->create();
    RoomSession::factory()->create([
        'game_id' => $this->game->id,
        'team_id' => $otherTeam->id,
        'room_id' => $busyRoom->id,
        'status' => RoomSessionStatus::Active,
    ]);

    $session = $this->service->assignSpecificRoom($team, $busyRoom);

    expect($session->room_id)->toBe($busyRoom->id);
});

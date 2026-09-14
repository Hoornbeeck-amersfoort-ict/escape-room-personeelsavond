<?php

use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Models\User;

it('renders the team login page', function () {
    $this->get(route('team.login'))->assertOk();
});

it('lists teams from a draft game on the login page so they can log in before the game starts', function () {
    $game = Game::factory()->create(); // defaults to draft, as the seeder produces
    $team = Team::factory()->for($game)->create(['name' => 'Team Draft']);

    $this->get(route('team.login'))->assertOk()->assertSee('Team Draft');
});

it('does not list a blocked team on the login page', function () {
    $game = Game::factory()->running()->create();
    Team::factory()->for($game)->inactive()->create(['name' => 'Team Blocked']);

    $this->get(route('team.login'))->assertOk()->assertDontSee('Team Blocked');
});

it('renders the admin login page', function () {
    $this->get(route('admin.login'))->assertOk();
});

it('renders the play page for a team with no session yet (draft game)', function () {
    $game = Game::factory()->create();
    $team = Team::factory()->for($game)->create();

    $this->actingAs($team, 'team')->get(route('team.play'))->assertOk()->assertSee('Nog even geduld');
});

it('renders the play page for a team waiting to be assigned', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();
    Room::factory()->for($game)->create();

    $this->actingAs($team, 'team')->get(route('team.play'))->assertOk()->assertSee('KAMER STARTEN');
});

it('renders the play page for a team with an active room', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();
    activeSession($game, $team, $room);

    $this->actingAs($team, 'team')->get(route('team.play'))->assertOk()->assertSee('ANTWOORD CONTROLEREN');
});

it('renders the play page for a team that finished the game', function () {
    $game = Game::factory()->finished()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();
    RoomSession::factory()->completed()->create([
        'game_id' => $game->id, 'team_id' => $team->id, 'room_id' => $room->id,
    ]);

    $this->actingAs($team, 'team')->get(route('team.play'))->assertOk()->assertSee('evenement is afgelopen');
});

it('renders the play page for a team that played every room', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();
    RoomSession::factory()->completed()->create([
        'game_id' => $game->id, 'team_id' => $team->id, 'room_id' => $room->id,
    ]);

    $this->actingAs($team, 'team')->get(route('team.play'))->assertOk()->assertSee('alle kamers gespeeld');
});

it('renders the admin games index, create, and game edit pages', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)->get(route('admin.games.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.edit', $game))->assertOk();
});

it('renders the admin live dashboard with teams, rooms, and leaderboard tabs', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->running()->create();
    Team::factory()->for($game)->create();
    Room::factory()->for($game)->create();

    $this->actingAs($admin)->get(route('admin.games.dashboard', $game))->assertOk()->assertSee('Teamoverzicht');
});

it('shows a team that has a room assigned but not yet started as occupying that room', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create(['name' => 'Onderweg Team']);
    $room = Room::factory()->for($game)->create(['name' => 'Kamer Onderweg']);

    RoomSession::factory()->create([
        'game_id' => $game->id,
        'team_id' => $team->id,
        'room_id' => $room->id,
        'status' => RoomSessionStatus::Assigned,
    ]);

    $this->actingAs($admin)->get(route('admin.games.dashboard', $game))
        ->assertOk()
        ->assertSee('Kamer Onderweg')
        ->assertSee('Onderweg')
        ->assertDontSee('Geen actieve kamer');
});

it('renders the admin teams and rooms management pages', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create();
    $team = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();

    $this->actingAs($admin)->get(route('admin.games.teams.index', $game))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.teams.create', $game))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.teams.edit', [$game, $team]))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.rooms.index', $game))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.rooms.create', $game))->assertOk();
    $this->actingAs($admin)->get(route('admin.games.rooms.edit', [$game, $room]))->assertOk();
});

it('renders the admin audit log page', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
});

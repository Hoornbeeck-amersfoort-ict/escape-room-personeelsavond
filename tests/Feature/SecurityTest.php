<?php

use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('does not allow a team to view another team\'s room session', function () {
    $game = Game::factory()->running()->create();
    $teamA = Team::factory()->for($game)->create();
    $teamB = Team::factory()->for($game)->create();
    $room = Room::factory()->for($game)->create();

    $sessionForB = RoomSession::factory()->active()->create([
        'game_id' => $game->id,
        'team_id' => $teamB->id,
        'room_id' => $room->id,
    ]);

    expect(Gate::forUser($teamA)->allows('view', $sessionForB))->toBeFalse();
    expect(Gate::forUser($teamA)->allows('submitAnswer', $sessionForB))->toBeFalse();
    expect(Gate::forUser($teamA)->allows('giveUp', $sessionForB))->toBeFalse();
    expect(Gate::forUser($teamB)->allows('view', $sessionForB))->toBeTrue();
});

it('does not allow a team to view another team\'s profile', function () {
    $game = Game::factory()->running()->create();
    $teamA = Team::factory()->for($game)->create();
    $teamB = Team::factory()->for($game)->create();

    expect(Gate::forUser($teamA)->allows('view', $teamB))->toBeFalse();
    expect(Gate::forUser($teamA)->allows('view', $teamA))->toBeTrue();
});

it('redirects a guest away from the team play page to the team login', function () {
    $this->get(route('team.play'))->assertRedirect(route('team.login'));
});

it('redirects a guest away from the admin dashboard to the admin login', function () {
    $game = Game::factory()->running()->create();

    $this->get(route('admin.games.dashboard', $game))->assertRedirect(route('admin.login'));
});

it('does not allow a logged-in team to reach admin routes', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->create();

    $this->actingAs($team, 'team')
        ->get(route('admin.games.index'))
        ->assertRedirect(route('admin.login'));
});

it('does not allow an admin session to authorize as a team', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('team.play'))
        ->assertRedirect(route('team.login'));
});

it('rejects an incorrect team PIN', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->withCode('1111')->create();

    $this->post(route('team.login.store'), [
        'team_id' => $team->id,
        'code' => '9999',
    ])->assertSessionHasErrors('code');

    $this->assertGuest('team');
});

it('rejects login for a blocked team', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->withCode('1111')->inactive()->create();

    $this->post(route('team.login.store'), [
        'team_id' => $team->id,
        'code' => '1111',
    ])->assertSessionHasErrors('code');

    $this->assertGuest('team');
});

it('logs a team in with the correct PIN', function () {
    $game = Game::factory()->running()->create();
    $team = Team::factory()->for($game)->withCode('4321')->create();

    $this->post(route('team.login.store'), [
        'team_id' => $team->id,
        'code' => '4321',
    ])->assertRedirect(route('team.play'));

    $this->assertAuthenticatedAs($team, 'team');
});

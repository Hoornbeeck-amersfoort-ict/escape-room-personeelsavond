<?php

use App\Models\Game;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;

/**
 * A plain SQL ORDER BY sorts "Team 1".."Team 15" lexicographically as
 * strings (Team 1, Team 10, Team 11, ..., Team 2), which looks broken on a
 * live event dashboard admins are glancing at constantly. Every team/room
 * listing must use natural sort instead.
 */
function assertInNaturalOrder(array $names): void
{
    expect($names)->toBe(['Team 1', 'Team 2', 'Team 3', 'Team 10', 'Team 11']);
}

it('lists teams in natural order on the team login page', function () {
    $game = Game::factory()->create();
    collect(['Team 10', 'Team 2', 'Team 11', 'Team 1', 'Team 3'])
        ->each(fn (string $name) => Team::factory()->for($game)->create(['name' => $name]));

    $response = $this->get(route('team.login'));

    assertInNaturalOrder($response->viewData('teams')->pluck('name')->all());
});

it('lists teams in natural order on the admin teams index', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create();
    collect(['Team 10', 'Team 2', 'Team 11', 'Team 1', 'Team 3'])
        ->each(fn (string $name) => Team::factory()->for($game)->create(['name' => $name]));

    $response = $this->actingAs($admin)->get(route('admin.games.teams.index', $game));

    assertInNaturalOrder($response->viewData('teams')->pluck('name')->all());
});

it('lists rooms in natural order on the admin rooms index', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create();
    collect(['Kamer 10', 'Kamer 2', 'Kamer 11', 'Kamer 1', 'Kamer 3'])
        ->each(fn (string $name) => Room::factory()->for($game)->create(['name' => $name]));

    $response = $this->actingAs($admin)->get(route('admin.games.rooms.index', $game));

    $names = $response->viewData('rooms')->pluck('name')->all();
    expect($names)->toBe(['Kamer 1', 'Kamer 2', 'Kamer 3', 'Kamer 10', 'Kamer 11']);
});

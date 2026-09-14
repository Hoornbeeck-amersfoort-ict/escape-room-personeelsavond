<?php

use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('plays through the full documented game flow end to end over real HTTP requests', function () {
    // Admin creates a game, teams, and rooms, then sets an end time and starts it.
    $admin = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.games.store'), ['name' => 'Personeelsavond'])
        ->assertRedirect();
    $game = Game::query()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.games.teams.store', $game), [
        'name' => 'Team 1',
        'code' => '5555',
    ])->assertRedirect();
    $team = Team::query()->where('game_id', $game->id)->firstOrFail();

    foreach (['Kamer 1', 'Kamer 2'] as $roomName) {
        $this->actingAs($admin)->post(route('admin.games.rooms.store', $game), [
            'name' => $roomName,
            'answer' => 'Amsterdam',
            'active' => '1',
        ])->assertRedirect();
    }

    $this->actingAs($admin)->put(route('admin.games.update', $game), [
        'name' => $game->name,
        'end_time' => now()->addHours(3)->format('Y-m-d\TH:i'),
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.games.start', $game))->assertRedirect();
    $game->refresh();
    expect($game->isRunning())->toBeTrue();

    // Team 1 logs in.
    $this->post(route('team.login.store'), ['team_id' => $team->id, 'code' => '5555'])
        ->assertRedirect(route('team.play'));
    $this->assertAuthenticatedAs($team->fresh(), 'team');

    // Visiting the play page triggers the automatic room assignment.
    $this->get(route('team.play'))->assertOk();

    // Team 1 gets assigned a room automatically.
    $session = RoomSession::query()->where('team_id', $team->id)->firstOrFail();
    expect($session->status)->toBe(RoomSessionStatus::Assigned);
    $firstRoom = $session->room;

    // Team walks to the room and presses "KAMER STARTEN".
    Livewire::actingAs($team, 'team')
        ->test('App\\Livewire\\Team\\PlayGame')
        ->call('startRoom');

    expect($session->fresh()->status)->toBe(RoomSessionStatus::Active);
    expect($session->fresh()->started_at)->not->toBeNull();

    // Team answers wrong once, then correct.
    Livewire::actingAs($team, 'team')
        ->test('App\\Livewire\\Team\\PlayGame')
        ->set('answer', 'wrong answer')
        ->call('submitAnswer')
        ->assertSet('feedback.type', 'incorrect')
        ->assertSet('feedback.attemptsRemaining', 2);

    Livewire::actingAs($team, 'team')
        ->test('App\\Livewire\\Team\\PlayGame')
        ->set('answer', 'AMSTERDAM')
        ->call('submitAnswer')
        ->assertSet('feedback.type', 'correct')
        ->assertSet('feedback.points', 2);

    $session->refresh();
    expect($session->status)->toBe(RoomSessionStatus::Completed);
    expect($session->points)->toBe(2);
    expect($session->finished_at)->not->toBeNull();

    // Team 1 automatically receives a new room.
    $newSession = RoomSession::query()
        ->where('team_id', $team->id)
        ->where('id', '!=', $session->id)
        ->firstOrFail();
    expect($newSession->room_id)->not->toBe($firstRoom->id);
    expect($newSession->status)->toBe(RoomSessionStatus::Assigned);

    // The admin's live dashboard reflects the current state.
    $this->actingAs($admin)->get(route('admin.games.dashboard', $game))
        ->assertOk()
        ->assertSee('Team 1')
        ->assertSee('2'); // points

    // Eindtijd wordt bereikt: admin beëindigt het evenement.
    $this->actingAs($admin)->post(route('admin.games.finish', $game))->assertRedirect();

    expect($game->fresh()->isFinished())->toBeTrue();
    expect($newSession->fresh()->status)->toBe(RoomSessionStatus::Failed);

    // Leaderboard / eigen resultaat is nu beschikbaar.
    Livewire::actingAs($team, 'team')
        ->test('App\\Livewire\\Team\\PlayGame')
        ->assertSee('evenement is afgelopen')
        ->assertSee('2 punten');
});

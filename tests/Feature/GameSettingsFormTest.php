<?php

use App\Models\Game;
use App\Models\User;

it('keeps start_time null when the field is submitted empty instead of storing "now"', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create(['start_time' => null, 'end_time' => null]);

    $this->actingAs($admin)->put(route('admin.games.update', $game), [
        'name' => $game->name,
        'start_time' => '',
        'end_time' => now()->addHours(3)->format('Y-m-d\TH:i'),
    ])->assertRedirect();

    expect($game->fresh()->start_time)->toBeNull();
});

it('keeps end_time null when the field is submitted empty instead of storing "now"', function () {
    $admin = User::factory()->create();
    $game = Game::factory()->create(['start_time' => null, 'end_time' => null]);

    $this->actingAs($admin)->put(route('admin.games.update', $game), [
        'name' => $game->name,
        'start_time' => '',
        'end_time' => '',
    ])->assertRedirect();

    expect($game->fresh()->end_time)->toBeNull();
});

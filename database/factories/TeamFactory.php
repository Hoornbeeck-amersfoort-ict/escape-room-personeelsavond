<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => 'Team '.$this->faker->unique()->numberBetween(1, 500),
            'code_hash' => Hash::make('0000'),
            'active' => true,
        ];
    }

    /**
     * Set a known plain-text PIN so tests can log in with it.
     */
    public function withCode(string $plainCode): static
    {
        return $this->state(fn () => ['code_hash' => Hash::make($plainCode)]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}

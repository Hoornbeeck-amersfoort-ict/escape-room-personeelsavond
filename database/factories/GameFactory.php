<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Escape Room '.$this->faker->year(),
            'status' => GameStatus::Draft,
            'start_time' => null,
            'end_time' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => GameStatus::Running,
            'start_time' => now()->subMinutes(5),
            'end_time' => now()->addHours(3),
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => GameStatus::Finished,
            'start_time' => now()->subHours(3),
            'end_time' => now()->subMinute(),
        ]);
    }

    /**
     * A running game whose end time is only a few minutes away — handy for
     * manually testing the countdown and end-of-game behaviour quickly.
     */
    public function endingSoon(int $minutes = 10): static
    {
        return $this->state(fn () => [
            'status' => GameStatus::Running,
            'start_time' => now()->subMinute(),
            'end_time' => now()->addMinutes($minutes),
        ]);
    }
}

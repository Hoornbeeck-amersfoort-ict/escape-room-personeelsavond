<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
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
            'name' => 'Kamer '.$this->faker->unique()->numberBetween(1, 500),
            'description' => $this->faker->sentence(),
            'instructions' => $this->faker->paragraph(),
            'images' => [],
            'answer' => $this->faker->word(),
            'alternative_answers' => [],
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}

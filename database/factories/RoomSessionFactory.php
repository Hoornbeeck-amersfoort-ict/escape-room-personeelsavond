<?php

namespace Database\Factories;

use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomSession>
 */
class RoomSessionFactory extends Factory
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
            'team_id' => Team::factory(),
            'room_id' => Room::factory(),
            'status' => RoomSessionStatus::Assigned,
            'started_at' => null,
            'finished_at' => null,
            'points' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => RoomSessionStatus::Active,
            'started_at' => now()->subMinutes(2),
        ]);
    }

    public function completed(int $points = 3): static
    {
        return $this->state(fn () => [
            'status' => RoomSessionStatus::Completed,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'points' => $points,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => RoomSessionStatus::Failed,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'points' => 0,
        ]);
    }

    public function givenUp(): static
    {
        return $this->state(fn () => [
            'status' => RoomSessionStatus::GivenUp,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
            'points' => 0,
        ]);
    }
}

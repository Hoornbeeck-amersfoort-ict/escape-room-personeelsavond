<?php

namespace Database\Factories;

use App\Models\AnswerAttempt;
use App\Models\RoomSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnswerAttempt>
 */
class AnswerAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_session_id' => RoomSession::factory(),
            'answer' => $this->faker->word(),
            'correct' => false,
            'attempt_number' => 1,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => ['correct' => true]);
    }
}

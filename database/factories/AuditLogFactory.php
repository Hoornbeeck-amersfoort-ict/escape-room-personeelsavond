<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'action' => 'team.reset_code',
            'target_type' => 'App\\Models\\Team',
            'target_id' => 1,
            'old_values' => [],
            'new_values' => [],
        ];
    }
}

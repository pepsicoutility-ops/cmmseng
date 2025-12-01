<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WoProcesse>
 */
class WoProcesseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_order_id' => \App\Models\WorkOrder::factory(),
            'performed_by_gpid' => \App\Models\User::factory()->create(['role' => 'technician'])->gpid,
            'action' => fake()->randomElement(['review', 'approve', 'start', 'complete']),
            'timestamp' => now(),
            'notes' => fake()->sentence(),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PmChecklistItem>
 */
class PmChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pm_schedule_id' => \App\Models\PmSchedule::factory(),
            'item_name' => fake()->sentence(3),
            'item_type' => fake()->randomElement(['checkbox', 'input', 'photo', 'dropdown']),
            'options' => json_encode([]),
            'order' => fake()->numberBetween(1, 20),
            'is_required' => fake()->boolean(70), // 70% chance of being required
        ];
    }
}

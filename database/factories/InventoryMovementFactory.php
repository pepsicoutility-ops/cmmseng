<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_id' => \App\Models\Part::factory(),
            'movement_type' => fake()->randomElement(['in', 'out', 'adjustment']),
            'quantity' => fake()->numberBetween(1, 100),
            'reference_type' => fake()->randomElement(['manual', 'work_order', 'pm_execution']),
            'reference_id' => null,
            'performed_by_gpid' => \App\Models\User::factory()->create(['role' => 'tech_store'])->gpid,
            'notes' => fake()->sentence(),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockAlert>
 */
class StockAlertFactory extends Factory
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
            'alert_type' => fake()->randomElement(['low_stock', 'out_of_stock']),
            'triggered_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by_gpid' => null,
        ];
    }
}

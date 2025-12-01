<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_number' => fake()->unique()->bothify('PN-####-????'),
            'name' => fake()->randomElement(['Motor', 'Bearing', 'Belt', 'Filter', 'Seal', 'Gasket']),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['mechanical', 'electrical', 'consumable']),
            'unit' => fake()->randomElement(['pcs', 'set', 'meter', 'liter']),
            'current_stock' => fake()->numberBetween(0, 100),
            'min_stock' => 10,
            'unit_price' => fake()->numberBetween(10000, 500000),
            'location' => fake()->bothify('Rack-?-##'),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventorie>
 */
class InventorieFactory extends Factory
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
            'area_id' => \App\Models\Area::factory(),
            'sub_area_id' => null,
            'asset_id' => null,
            'sub_asset_id' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'min_stock' => 10,
            'location' => fake()->bothify('Rack-?-##'),
        ];
    }
}

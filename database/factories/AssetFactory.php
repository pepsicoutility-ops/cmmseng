<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sub_area_id' => \App\Models\SubArea::factory(),
            'name' => fake()->randomElement(['Processing', 'VMM', 'EXTRUDER', 'Cooling', 'Sealing']),
            'code' => fake()->unique()->regexify('[A-Z]{4}'),
            'model' => fake()->word(),
            'serial_number' => fake()->bothify('SN-####-????'),
            'installation_date' => fake()->dateTimeBetween('-2 years', '-1 year'),
            'is_active' => true,
        ];
    }
}

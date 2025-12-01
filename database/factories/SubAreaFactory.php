<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubArea>
 */
class SubAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => \App\Models\Area::factory(),
            'name' => fake()->randomElement(['EP', 'PC', 'TC', 'DBM', 'LBCSS']),
            'code' => fake()->unique()->regexify('[A-Z]{2}'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

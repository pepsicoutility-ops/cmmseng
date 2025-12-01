<?php

namespace Database\Factories;

use App\Models\BarcodeToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarcodeTokenFactory extends Factory
{
    protected $model = BarcodeToken::class;

    public function definition(): array
    {
        return [
            'token' => (string) Str::uuid(),
            'equipment_type' => $this->faker->randomElement(['asset', 'sub_asset', 'part']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

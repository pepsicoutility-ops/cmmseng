<?php

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Inventory::class;

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

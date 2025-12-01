<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yearMonth = date('Ym');
        $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create the hierarchy: Area → SubArea → Asset → SubAsset
        $area = \App\Models\Area::factory()->create();
        $subArea = \App\Models\SubArea::factory()->create(['area_id' => $area->id]);
        $asset = \App\Models\Asset::factory()->create(['sub_area_id' => $subArea->id]);
        $subAsset = \App\Models\SubAsset::factory()->create(['asset_id' => $asset->id]);
        
        return [
            'wo_number' => "WO-{$yearMonth}-{$randomNum}",
            'area_id' => $area->id,
            'sub_area_id' => $subArea->id,
            'asset_id' => $asset->id,
            'sub_asset_id' => $subAsset->id,
            'created_by_gpid' => \App\Models\User::factory()->create(['role' => 'operator'])->gpid,
            'operator_name' => fake()->name(),
            'shift' => fake()->randomElement(['1', '2', '3']),
            'description' => fake()->sentence(),
            'problem_type' => fake()->randomElement(['abnormality', 'breakdown', 'request_consumable', 'improvement', 'inspection']),
            'assign_to' => fake()->randomElement(['utility', 'electric', 'mechanic']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'submitted',
            'photos' => [],
        ];
    }
}

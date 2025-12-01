<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PmSchedule>
 */
class PmScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yearMonth = date('Ym');
        $randomNum = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return [
            'code' => "PM-{$yearMonth}-{$randomNum}",
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'schedule_type' => fake()->randomElement(['weekly', 'running_hours', 'cycle']),
            'frequency' => fake()->numberBetween(1, 4),
            'week_day' => fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'estimated_duration' => fake()->numberBetween(30, 240),
            'asset_id' => \App\Models\Asset::factory(),
            'sub_asset_id' => null,
            'department' => fake()->randomElement(['utility', 'electric', 'mechanic']),
            'assigned_to_gpid' => \App\Models\User::factory()->create(['role' => 'technician'])->gpid,
            'assigned_by_gpid' => \App\Models\User::factory()->create(['role' => 'manager'])->gpid,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}

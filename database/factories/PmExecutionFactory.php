<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PmExecution>
 */
class PmExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actualStart = fake()->dateTimeBetween('-1 week', 'now');
        $actualEnd = (clone $actualStart)->modify('+' . rand(30, 180) . ' minutes');
        
        return [
            'pm_schedule_id' => \App\Models\PmSchedule::factory(),
            'executed_by_gpid' => \App\Models\User::factory()->create(['role' => 'technician'])->gpid,
            'scheduled_date' => $actualStart,
            'actual_start' => $actualStart,
            'actual_end' => $actualEnd,
            'duration' => date_diff($actualStart, $actualEnd)->i,
            'status' => 'completed',
            'compliance_status' => 'on_time',
            'checklist_data' => json_encode([]),
            'notes' => fake()->paragraph(),
            'photos' => json_encode([]),
            'is_on_time' => true,
        ];
    }
}

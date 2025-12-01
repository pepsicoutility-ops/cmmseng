<?php

use App\Services\PmService;
use App\Models\PmExecution;
use App\Models\PmSchedule;
use App\Models\PmCost;
use App\Models\Part;
use App\Models\PmPartsUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pmService = new PmService();
});

test('pm service calculates labour cost correctly', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 60, // 60 minutes
    ]);
    
    $this->pmService->calculateCost($pmExecution);
    
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Labour cost = 60/60 * 50000 = 50000
    expect($pmCost)->not->toBeNull();
    expect($pmCost->labour_cost)->toBe('50000.00');
});

test('pm service calculates labour cost with partial hour', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 30, // 30 minutes = 0.5 hour
    ]);
    
    $this->pmService->calculateCost($pmExecution);
    
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Labour cost = 30/60 * 50000 = 25000
    expect($pmCost->labour_cost)->toBe('25000.00');
});

test('pm service calculates parts cost correctly', function () {
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 45,
    ]);
    
    $part1 = Part::factory()->create(['unit_price' => 100000]);
    $part2 = Part::factory()->create(['unit_price' => 50000]);
    
    // Add parts usage
    PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part1->id,
        'quantity' => 2,
        'cost' => 200000, // 2 * 100000
    ]);
    
    PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part2->id,
        'quantity' => 1,
        'cost' => 50000,
    ]);
    
    $this->pmService->calculateCost($pmExecution);
    
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Parts cost = 200000 + 50000 = 250000
    expect($pmCost->parts_cost)->toBe('250000.00');
});

test('pm service calculates overhead cost correctly', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 60,
    ]);
    
    $part = Part::factory()->create(['unit_price' => 100000]);
    
    PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part->id,
        'quantity' => 1,
        'cost' => 100000,
    ]);
    
    $this->pmService->calculateCost($pmExecution);
    
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Labour: 60/60 * 50000 = 50000
    // Parts: 100000
    // Overhead: (50000 + 100000) * 0.1 = 15000
    expect($pmCost->labour_cost)->toBe('50000.00');
    expect($pmCost->parts_cost)->toBe('100000.00');
    expect($pmCost->overhead_cost)->toBe('15000.00');
});

test('pm service calculates total cost correctly', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 120, // 2 hours
    ]);
    
    $part = Part::factory()->create(['unit_price' => 200000]);
    
    PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part->id,
        'quantity' => 1,
        'cost' => 200000,
    ]);
    
    $this->pmService->calculateCost($pmExecution);
    
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Labour: 120/60 * 50000 = 100000
    // Parts: 200000
    // Overhead: (100000 + 200000) * 0.1 = 30000
    // Total: 100000 + 200000 + 30000 = 330000
    expect($pmCost->total_cost)->toBe('330000.00');
});

test('pm service completes pm execution with cost calculation', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'actual_start' => now()->subHours(2),
        'actual_end' => now(),
        'duration' => null, // Should calculate from start/end
    ]);
    
    $this->pmService->completePmExecution($pmExecution, []);
    
    $pmExecution->refresh();
    
    // Duration should be calculated: 2 hours = 120 minutes
    expect($pmExecution->duration)->toBe(120);
    
    // Cost should be calculated
    $pmCost = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    expect($pmCost)->not->toBeNull();
    expect($pmCost->labour_cost)->toBe('100000.00'); // 120/60 * 50000
});

test('pm service updates existing cost on recalculation', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create([
        'pm_schedule_id' => $pmSchedule->id,
        'duration' => 60,
    ]);
    
    // First calculation
    $this->pmService->calculateCost($pmExecution);
    $pmCost1 = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    $cost1Id = $pmCost1->id;
    
    // Change duration and recalculate
    $pmExecution->update(['duration' => 120]);
    $this->pmService->recalculateCost($pmExecution);
    
    $pmCost2 = PmCost::where('pm_execution_id', $pmExecution->id)->first();
    
    // Should update the same record, not create a new one
    expect($pmCost2->id)->toBe($cost1Id);
    expect($pmCost2->labour_cost)->toBe('100000.00'); // Updated cost
});

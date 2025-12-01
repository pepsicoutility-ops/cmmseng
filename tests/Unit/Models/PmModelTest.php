<?php

use App\Models\PmSchedule;
use App\Models\PmExecution;
use App\Models\PmChecklistItem;
use App\Models\PmPartsUsage;
use App\Models\PmCost;
use App\Models\User;
use App\Models\Asset;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pm schedule has correct fillable attributes', function () {
    $pmSchedule = new PmSchedule();
    
    expect($pmSchedule->getFillable())->toContain('code', 'title', 'description', 'schedule_type');
});

test('pm schedule casts dates correctly', function () {
    $pmSchedule = PmSchedule::factory()->create();
    
    // PM schedules don't have next_due_date - it's calculated dynamically
    // Test timestamps are cast correctly
    expect($pmSchedule->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($pmSchedule->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('pm schedule belongs to asset', function () {
    $asset = Asset::factory()->create();
    $pmSchedule = PmSchedule::factory()->create(['asset_id' => $asset->id]);
    
    expect($pmSchedule->asset)->toBeInstanceOf(Asset::class);
    expect($pmSchedule->asset->id)->toBe($asset->id);
});

test('pm schedule belongs to assigned user', function () {
    $user = User::factory()->create(['gpid' => 'TECH001']);
    $pmSchedule = PmSchedule::factory()->create(['assigned_to_gpid' => 'TECH001']);
    
    expect($pmSchedule->assignedTo)->toBeInstanceOf(User::class);
    expect($pmSchedule->assignedTo->gpid)->toBe('TECH001');
});

test('pm schedule has executions relationship', function () {
    $pmSchedule = PmSchedule::factory()->create();
    
    expect($pmSchedule->pmExecutions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('pm schedule has checklist items relationship', function () {
    $pmSchedule = PmSchedule::factory()->create();
    
    expect($pmSchedule->checklistItems())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('pm schedule can have multiple checklist items', function () {
    $pmSchedule = PmSchedule::factory()->create();
    PmChecklistItem::factory()->count(5)->create(['pm_schedule_id' => $pmSchedule->id]);
    
    expect($pmSchedule->checklistItems)->toHaveCount(5);
});

test('pm execution belongs to pm schedule', function () {
    $pmSchedule = PmSchedule::factory()->create();
    $pmExecution = PmExecution::factory()->create(['pm_schedule_id' => $pmSchedule->id]);
    
    expect($pmExecution->pmSchedule)->toBeInstanceOf(PmSchedule::class);
    expect($pmExecution->pmSchedule->id)->toBe($pmSchedule->id);
});

test('pm execution belongs to executed by user', function () {
    $user = User::factory()->create(['gpid' => 'TECH002']);
    $pmExecution = PmExecution::factory()->create(['executed_by_gpid' => 'TECH002']);
    
    expect($pmExecution->executedBy)->toBeInstanceOf(User::class);
    expect($pmExecution->executedBy->gpid)->toBe('TECH002');
});

test('pm execution has parts usage relationship', function () {
    $pmExecution = PmExecution::factory()->create();
    
    expect($pmExecution->partsUsage())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('pm execution has cost relationship', function () {
    $pmExecution = PmExecution::factory()->create();
    
    expect($pmExecution->cost())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
});

test('pm execution casts checklist to array', function () {
    $pmExecution = PmExecution::factory()->create([
        'checklist_data' => ['item1' => 'OK', 'item2' => 'NG'],
    ]);
    
    expect($pmExecution->checklist_data)->toBeArray();
});

test('pm execution casts photos to array', function () {
    $pmExecution = PmExecution::factory()->create([
        'photos' => ['photo1.jpg', 'photo2.jpg'],
    ]);
    
    expect($pmExecution->photos)->toBeArray();
});

test('pm parts usage belongs to pm execution', function () {
    $pmExecution = PmExecution::factory()->create();
    $part = Part::factory()->create();
    $pmPartsUsage = PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part->id,
        'quantity' => 2,
    ]);
    
    expect($pmPartsUsage->pmExecution)->toBeInstanceOf(PmExecution::class);
    expect($pmPartsUsage->pmExecution->id)->toBe($pmExecution->id);
});

test('pm parts usage belongs to part', function () {
    $pmExecution = PmExecution::factory()->create();
    $part = Part::factory()->create();
    $pmPartsUsage = PmPartsUsage::create([
        'pm_execution_id' => $pmExecution->id,
        'part_id' => $part->id,
        'quantity' => 2,
    ]);
    
    expect($pmPartsUsage->part)->toBeInstanceOf(Part::class);
    expect($pmPartsUsage->part->id)->toBe($part->id);
});

test('pm cost belongs to pm execution', function () {
    $pmExecution = PmExecution::factory()->create();
    $pmCost = PmCost::create([
        'pm_execution_id' => $pmExecution->id,
        'labour_cost' => 50000,
        'parts_cost' => 100000,
        'total_cost' => 150000,
    ]);
    
    expect($pmCost->pmExecution)->toBeInstanceOf(PmExecution::class);
    expect($pmCost->pmExecution->id)->toBe($pmExecution->id);
});

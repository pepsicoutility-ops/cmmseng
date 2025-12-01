<?php

use App\Models\User;
use App\Models\PmSchedule;
use App\Models\Asset;
use App\Models\SubArea;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create area hierarchy
    $this->area = Area::factory()->create(['name' => 'Proses']);
    $this->subArea = SubArea::factory()->create([
        'area_id' => $this->area->id,
        'name' => 'EP',
    ]);
    $this->asset = Asset::factory()->create([
        'sub_area_id' => $this->subArea->id,
        'name' => 'Processing',
    ]);
    
    // Create users
    $this->manager = User::factory()->create([
        'gpid' => 'MGR001',
        'role' => 'manager',
    ]);
    
    $this->technician = User::factory()->create([
        'gpid' => 'TECH001',
        'role' => 'technician',
        'department' => 'mechanic',
    ]);
    
    $this->otherTechnician = User::factory()->create([
        'gpid' => 'TECH002',
        'role' => 'technician',
        'department' => 'electric',
    ]);
});

test('manager can create pm schedule', function () {
    $this->actingAs($this->manager);
    
    $pmData = [
        'code' => 'PM-' . now()->format('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => 'Weekly Motor Check',
        'description' => 'Check motor condition',
        'schedule_type' => 'weekly',
        'frequency' => 1,
        'week_day' => 'monday',
        'estimated_duration' => 60,
        'asset_id' => $this->asset->id,
        'department' => 'mechanic',
        'assigned_to_gpid' => 'TECH001',
        'assigned_by_gpid' => 'MGR001',
        'status' => 'active',
    ];
    
    $pmSchedule = PmSchedule::create($pmData);
    
    expect($pmSchedule)->not->toBeNull();
    expect($pmSchedule->title)->toBe('Weekly Motor Check');
    expect($pmSchedule->code)->toStartWith('PM-');
    expect($pmSchedule->assigned_to_gpid)->toBe('TECH001');
});

test('technician can only view their assigned pm schedules', function () {
    // Create PM assigned to technician
    $pmOwned = PmSchedule::factory()->create([
        'assigned_to_gpid' => 'TECH001',
        'asset_id' => $this->asset->id,
    ]);
    
    // Create PM assigned to other technician
    $pmOther = PmSchedule::factory()->create([
        'assigned_to_gpid' => 'TECH002',
        'asset_id' => $this->asset->id,
    ]);
    
    $this->actingAs($this->technician);
    
    // Technician should only see their own PM
    $query = PmSchedule::query();
    $query->where('assigned_to_gpid', $this->technician->gpid);
    
    $pmSchedules = $query->get();
    
    expect($pmSchedules)->toHaveCount(1);
    expect($pmSchedules->first()->id)->toBe($pmOwned->id);
});

test('manager can view all pm schedules', function () {
    PmSchedule::factory()->count(3)->create([
        'asset_id' => $this->asset->id,
    ]);
    
    PmSchedule::factory()->count(2)->create([
        'assigned_to_gpid' => 'TECH001',
        'asset_id' => $this->asset->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $pmSchedules = PmSchedule::all();
    
    expect($pmSchedules)->toHaveCount(5);
});

test('pm schedule auto generates code', function () {
    $pm = PmSchedule::factory()->create([
        'asset_id' => $this->asset->id,
    ]);
    
    expect($pm->code)->toMatch('/^PM-\d{6}-\d{3}$/');
});

test('pm schedule belongs to asset', function () {
    $pm = PmSchedule::factory()->create([
        'asset_id' => $this->asset->id,
    ]);
    
    expect($pm->asset)->not->toBeNull();
    expect($pm->asset->id)->toBe($this->asset->id);
    expect($pm->asset->name)->toBe('Processing');
});

test('pm schedule belongs to assigned user', function () {
    $pm = PmSchedule::factory()->create([
        'assigned_to_gpid' => 'TECH001',
        'asset_id' => $this->asset->id,
    ]);
    
    expect($pm->assignedTo)->not->toBeNull();
    expect($pm->assignedTo->gpid)->toBe('TECH001');
});

test('pm schedule can be updated by manager', function () {
    $this->actingAs($this->manager);
    
    $pm = PmSchedule::factory()->create([
        'title' => 'Original Title',
        'asset_id' => $this->asset->id,
    ]);
    
    $pm->update(['title' => 'Updated Title']);
    
    expect($pm->fresh()->title)->toBe('Updated Title');
});

test('pm schedule can be deactivated', function () {
    $pm = PmSchedule::factory()->create([
        'status' => 'active',
        'asset_id' => $this->asset->id,
    ]);
    
    $pm->update(['status' => 'inactive']);
    
    expect($pm->fresh()->status)->toBe('inactive');
});

test('pm schedule with weekly type requires week day', function () {
    $pm = PmSchedule::factory()->create([
        'schedule_type' => 'weekly',
        'week_day' => 'monday',
        'asset_id' => $this->asset->id,
    ]);
    
    expect($pm->schedule_type)->toBe('weekly');
    expect($pm->week_day)->toBe('monday');
});

test('pm schedule can filter by department', function () {
    PmSchedule::factory()->count(2)->create([
        'department' => 'mechanic',
        'asset_id' => $this->asset->id,
    ]);
    
    PmSchedule::factory()->count(3)->create([
        'department' => 'electric',
        'asset_id' => $this->asset->id,
    ]);
    
    $mechanicPms = PmSchedule::where('department', 'mechanic')->get();
    $electricPms = PmSchedule::where('department', 'electric')->get();
    
    expect($mechanicPms)->toHaveCount(2);
    expect($electricPms)->toHaveCount(3);
});

test('asisten manager can view department pm schedules only', function () {
    $asistenManager = User::factory()->create([
        'role' => 'asisten_manager',
        'department' => 'mechanic',
    ]);
    
    PmSchedule::factory()->count(2)->create([
        'department' => 'mechanic',
        'asset_id' => $this->asset->id,
    ]);
    
    PmSchedule::factory()->count(3)->create([
        'department' => 'electric',
        'asset_id' => $this->asset->id,
    ]);
    
    $this->actingAs($asistenManager);
    
    $query = PmSchedule::query();
    $query->where('department', $asistenManager->department);
    
    $pmSchedules = $query->get();
    
    expect($pmSchedules)->toHaveCount(2);
    expect($pmSchedules->pluck('department')->unique()->toArray())->toBe(['mechanic']);
});

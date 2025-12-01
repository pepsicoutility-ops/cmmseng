<?php

use App\Models\WorkOrder;
use App\Models\WoProcesse;
use App\Models\WoPartsUsage;
use App\Models\WoCost;
use App\Models\User;
use App\Models\Asset;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('work order has correct fillable attributes', function () {
    $workOrder = new WorkOrder();

    expect($workOrder->getFillable())->toContain('wo_number', 'description', 'priority', 'status');
});test('work order casts dates correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'reviewed_at' => '2025-11-25 10:00:00',
        'approved_at' => '2025-11-25 11:00:00',
    ]);
    
    expect($workOrder->reviewed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($workOrder->approved_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('work order casts photos to array', function () {
    $workOrder = WorkOrder::factory()->create([
        'photos' => ['photo1.jpg', 'photo2.jpg'],
    ]);
    
    expect($workOrder->photos)->toBeArray()->toHaveCount(2);
});

test('work order belongs to asset', function () {
    $asset = Asset::factory()->create();
    $workOrder = WorkOrder::factory()->create(['asset_id' => $asset->id]);
    
    expect($workOrder->asset)->toBeInstanceOf(Asset::class);
    expect($workOrder->asset->id)->toBe($asset->id);
});

test('work order belongs to created by user', function () {
    $user = User::factory()->create(['gpid' => 'OP001']);
    $workOrder = WorkOrder::factory()->create(['created_by_gpid' => 'OP001']);
    
    expect($workOrder->createdBy)->toBeInstanceOf(User::class);
    expect($workOrder->createdBy->gpid)->toBe('OP001');
});

test('work order has processes relationship', function () {
    $workOrder = WorkOrder::factory()->create();
    
    expect($workOrder->woProcesses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('work order has parts usage relationship', function () {
    $workOrder = WorkOrder::factory()->create();
    
    expect($workOrder->woPartsUsage())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('work order has cost relationship', function () {
    $workOrder = WorkOrder::factory()->create();
    
    expect($workOrder->woCost())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
});

test('work order can have multiple processes', function () {
    $workOrder = WorkOrder::factory()->create();
    $user = User::factory()->create(['gpid' => 'TECH001']);
    
    WoProcesse::factory()->count(3)->create([
        'work_order_id' => $workOrder->id,
        'performed_by_gpid' => 'TECH001',
    ]);
    
    expect($workOrder->woProcesses)->toHaveCount(3);
});

test('wo process belongs to work order', function () {
    $workOrder = WorkOrder::factory()->create();
    $user = User::factory()->create(['gpid' => 'TECH001']);
    $woProcess = WoProcesse::create([
        'work_order_id' => $workOrder->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'review',
        'notes' => 'Reviewed',
    ]);
    
    expect($woProcess->workOrder)->toBeInstanceOf(WorkOrder::class);
    expect($woProcess->workOrder->id)->toBe($workOrder->id);
});

test('wo process belongs to performed by user', function () {
    $workOrder = WorkOrder::factory()->create();
    $user = User::factory()->create(['gpid' => 'TECH002']);
    $woProcess = WoProcesse::create([
        'work_order_id' => $workOrder->id,
        'performed_by_gpid' => 'TECH002',
        'action' => 'approve',
        'notes' => 'Approved',
    ]);
    
    expect($woProcess->performedBy)->toBeInstanceOf(User::class);
    expect($woProcess->performedBy->gpid)->toBe('TECH002');
});

test('wo parts usage belongs to work order', function () {
    $workOrder = WorkOrder::factory()->create();
    $part = Part::factory()->create();
    $woPartsUsage = WoPartsUsage::create([
        'work_order_id' => $workOrder->id,
        'part_id' => $part->id,
        'quantity' => 3,
    ]);
    
    expect($woPartsUsage->workOrder)->toBeInstanceOf(WorkOrder::class);
    expect($woPartsUsage->workOrder->id)->toBe($workOrder->id);
});

test('wo parts usage belongs to part', function () {
    $workOrder = WorkOrder::factory()->create();
    $part = Part::factory()->create();
    $woPartsUsage = WoPartsUsage::create([
        'work_order_id' => $workOrder->id,
        'part_id' => $part->id,
        'quantity' => 3,
    ]);
    
    expect($woPartsUsage->part)->toBeInstanceOf(Part::class);
    expect($woPartsUsage->part->id)->toBe($part->id);
});

test('wo cost belongs to work order', function () {
    $workOrder = WorkOrder::factory()->create();
    $woCost = WoCost::create([
        'work_order_id' => $workOrder->id,
        'labour_cost' => 75000,
        'parts_cost' => 200000,
        'downtime_cost' => 50000,
        'total_cost' => 325000,
    ]);
    
    expect($woCost->workOrder)->toBeInstanceOf(WorkOrder::class);
    expect($woCost->workOrder->id)->toBe($workOrder->id);
});

test('wo cost casts decimal values correctly', function () {
    $workOrder = WorkOrder::factory()->create();
    $woCost = WoCost::create([
        'work_order_id' => $workOrder->id,
        'labour_cost' => 75000.50,
        'parts_cost' => 200000.75,
        'downtime_cost' => 50000.25,
        'total_cost' => 325051.50,
    ]);
    
    // MySQL decimal columns return string values
    expect($woCost->labour_cost)->toBe('75000.50');
    expect($woCost->parts_cost)->toBe('200000.75');
    expect($woCost->downtime_cost)->toBe('50000.25');
    expect($woCost->total_cost)->toBe('325051.50');
});

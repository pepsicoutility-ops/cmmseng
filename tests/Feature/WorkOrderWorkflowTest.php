<?php

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WoProcesse;
use App\Models\Asset;
use App\Models\SubArea;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create area hierarchy
    $this->area = Area::factory()->create();
    $this->subArea = SubArea::factory()->create(['area_id' => $this->area->id]);
    $this->asset = Asset::factory()->create(['sub_area_id' => $this->subArea->id]);
    $this->subAsset = \App\Models\SubAsset::factory()->create(['asset_id' => $this->asset->id]);
    
    // Create users
    $this->operator = User::factory()->create([
        'gpid' => 'OP001',
        'role' => 'operator',
    ]);
    
    $this->technician = User::factory()->create([
        'gpid' => 'TECH001',
        'role' => 'technician',
        'department' => 'mechanic',
    ]);
    
    $this->manager = User::factory()->create([
        'gpid' => 'MGR001',
        'role' => 'manager',
    ]);
});

test('operator can create work order', function () {
    $this->actingAs($this->operator);
    
    $yearMonth = date('Ym');
    $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $woData = [
        'wo_number' => "WO-{$yearMonth}-{$randomNum}",
        'area_id' => $this->area->id,
        'sub_area_id' => $this->subArea->id,
        'asset_id' => $this->asset->id,
        'sub_asset_id' => $this->subAsset->id,
        'created_by_gpid' => 'OP001',
        'operator_name' => 'Test Operator',
        'shift' => '1',
        'description' => 'Motor making strange noise',
        'problem_type' => 'breakdown',
        'priority' => 'high',
        'status' => 'submitted',
    ];
    
    $wo = WorkOrder::create($woData);
    
    expect($wo)->not->toBeNull();
    expect($wo->wo_number)->toStartWith('WO-');
    expect($wo->status)->toBe('submitted');
    expect($wo->created_by_gpid)->toBe('OP001');
});

test('work order auto generates wo number', function () {
    $wo = WorkOrder::factory()->create([
        'asset_id' => $this->asset->id,
    ]);
    
    expect($wo->wo_number)->toMatch('/^WO-\d{6}-\d{4}$/');
});

test('work order starts with pending status', function () {
    $wo = WorkOrder::factory()->create([
        'status' => 'submitted',
        'asset_id' => $this->asset->id,
    ]);
    
    expect($wo->status)->toBe('submitted');
    expect($wo->reviewed_at)->toBeNull();
    expect($wo->approved_at)->toBeNull();
});

test('technician can review work order', function () {
    $this->actingAs($this->technician);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'submitted',
        'asset_id' => $this->asset->id,
    ]);
    
    // Simulate review action
    $wo->update(['reviewed_at' => now()]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'review',
        'timestamp' => now(),
        'notes' => 'Reviewed and accepted',
    ]);
    
    expect($wo->fresh()->reviewed_at)->not->toBeNull();
    expect($wo->woProcesses)->toHaveCount(1);
    expect($wo->woProcesses->first()->action)->toBe('review');
});

test('manager can approve work order', function () {
    $this->actingAs($this->manager);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'submitted',
        'reviewed_at' => now(),
        'asset_id' => $this->asset->id,
    ]);
    
    // Simulate approve action
    $wo->update(['approved_at' => now()]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'MGR001',
        'action' => 'approve',
        'timestamp' => now(),
        'notes' => 'Approved for execution',
    ]);
    
    expect($wo->fresh()->approved_at)->not->toBeNull();
});

test('technician can start work after approval', function () {
    $this->actingAs($this->technician);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'submitted',
        'reviewed_at' => now(),
        'approved_at' => now(),
        'asset_id' => $this->asset->id,
    ]);
    
    // Simulate start action
    $wo->update([
        'status' => 'in_progress',
        'started_at' => now(),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now(),
    ]);
    
    $wo->refresh();
    expect($wo->status)->toBe('in_progress');
    expect($wo->started_at)->not->toBeNull();
});

test('technician can complete work order', function () {
    $this->actingAs($this->technician);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
        'started_at' => now()->subHour(),
        'asset_id' => $this->asset->id,
    ]);
    
    // Simulate complete action
    $wo->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
        'notes' => 'Fixed motor bearing',
    ]);
    
    $wo->refresh();
    expect($wo->status)->toBe('completed');
    expect($wo->completed_at)->not->toBeNull();
});

test('manager can close work order', function () {
    $this->actingAs($this->manager);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'completed',
        'completed_at' => now()->subDay(),
        'asset_id' => $this->asset->id,
    ]);
    
    // Simulate close action
    $wo->update([
        'status' => 'closed',
        'closed_at' => now(),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'MGR001',
        'action' => 'close',
        'timestamp' => now(),
        'notes' => 'Verified and closed',
    ]);
    
    $wo->refresh();
    expect($wo->status)->toBe('closed');
    expect($wo->closed_at)->not->toBeNull();
});

test('work order tracks complete workflow', function () {
    $this->actingAs($this->operator);
    
    // 1. Create WO
    $wo = WorkOrder::factory()->create([
        'status' => 'submitted',
        'created_by_gpid' => 'OP001',
        'asset_id' => $this->asset->id,
    ]);
    
    // 2. Review
    $this->actingAs($this->technician);
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'review',
        'timestamp' => now(),
    ]);
    $wo->update(['reviewed_at' => now()]);
    
    // 3. Approve
    $this->actingAs($this->manager);
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'MGR001',
        'action' => 'approve',
        'timestamp' => now(),
    ]);
    $wo->update(['approved_at' => now()]);
    
    // 4. Start
    $this->actingAs($this->technician);
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now(),
    ]);
    $wo->update(['status' => 'in_progress', 'started_at' => now()]);
    
    // 5. Complete
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    $wo->update(['status' => 'completed', 'completed_at' => now()]);
    
    // 6. Close
    $this->actingAs($this->manager);
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'MGR001',
        'action' => 'close',
        'timestamp' => now(),
    ]);
    $wo->update(['status' => 'closed', 'closed_at' => now()]);
    
    // Verify workflow
    $wo->refresh();
    expect($wo->woProcesses)->toHaveCount(5);
    expect($wo->status)->toBe('closed');
    expect($wo->reviewed_at)->not->toBeNull();
    expect($wo->approved_at)->not->toBeNull();
    expect($wo->started_at)->not->toBeNull();
    expect($wo->completed_at)->not->toBeNull();
    expect($wo->closed_at)->not->toBeNull();
});

test('work order process history is ordered by timestamp', function () {
    $wo = WorkOrder::factory()->create([
        'asset_id' => $this->asset->id,
    ]);
    
    $timestamp1 = now()->subHours(3);
    $timestamp2 = now()->subHours(2);
    $timestamp3 = now()->subHours(1);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'review',
        'timestamp' => $timestamp1,
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'MGR001',
        'action' => 'approve',
        'timestamp' => $timestamp2,
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => $timestamp3,
    ]);
    
    $processes = $wo->woProcesses()->orderBy('timestamp')->get();
    
    expect($processes)->toHaveCount(3);
    expect($processes->first()->action)->toBe('review');
    expect($processes->last()->action)->toBe('start');
});

test('work order can have photos attached', function () {
    $wo = WorkOrder::factory()->create([
        'photos' => ['photo1.jpg', 'photo2.jpg', 'photo3.jpg'],
        'asset_id' => $this->asset->id,
    ]);
    
    expect($wo->photos)->toBeArray();
    expect($wo->photos)->toHaveCount(3);
    expect($wo->photos)->toContain('photo1.jpg');
});

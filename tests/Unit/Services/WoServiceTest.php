<?php

use App\Services\WoService;
use App\Models\WorkOrder;
use App\Models\WoProcesse;
use App\Models\WoCost;
use App\Models\Part;
use App\Models\WoPartsUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->woService = new WoService();
    $this->user = User::factory()->create(['gpid' => 'TECH001']);
    $this->actingAs($this->user);
});

test('wo service calculates downtime correctly', function () {
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(30),
    ]);
    
    // Create process records
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subMinutes(30),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    // Complete the work order
    $this->woService->completeWorkOrder($wo, []);
    
    $wo->refresh();
    expect($wo->total_downtime)->toBe(30);
    expect($wo->mttr)->toBe(30);
});

test('wo service calculates downtime with fractional minutes correctly', function () {
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
    ]);
    
    // Create process records with fractional minutes (should round up)
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subSeconds(90), // 1.5 minutes
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    $this->woService->completeWorkOrder($wo, []);
    
    $wo->refresh();
    // 1.5 minutes should round up to 2
    expect($wo->total_downtime)->toBe(2);
});

test('wo service calculates labour cost correctly', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
    ]);
    
    // 60 minutes work
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subMinutes(60),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    $this->woService->completeWorkOrder($wo, []);
    
    $woCost = WoCost::where('work_order_id', $wo->id)->first();
    
    // Labour cost = 60 min / 60 * 50000 = 50000
    expect($woCost->labour_cost)->toBe('50000.00');
});

test('wo service calculates parts cost correctly', function () {
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
    ]);
    
    $part = Part::factory()->create([
        'unit_price' => 100000,
        'current_stock' => 10,
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subMinutes(10),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    // Complete with parts usage
    $this->woService->completeWorkOrder($wo, [
        'parts_usage' => [
            [
                'part_id' => $part->id,
                'quantity' => 2,
            ]
        ]
    ]);
    
    $woCost = WoCost::where('work_order_id', $wo->id)->first();
    
    // Parts cost = 2 * 100000 = 200000
    expect($woCost->parts_cost)->toBe('200000.00');
});

test('wo service calculates total cost correctly', function () {
    config(['cmms.labour_hourly_rate' => 50000]);
    config(['cmms.downtime_cost_per_hour' => 100000]);
    
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
    ]);
    
    $part = Part::factory()->create([
        'unit_price' => 150000,
        'current_stock' => 10,
    ]);
    
    // 30 minutes work
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subMinutes(30),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    $this->woService->completeWorkOrder($wo, [
        'parts_usage' => [
            [
                'part_id' => $part->id,
                'quantity' => 1,
            ]
        ]
    ]);
    
    $woCost = WoCost::where('work_order_id', $wo->id)->first();
    
    // Labour: 30/60 * 50000 = 25000
    // Parts: 1 * 150000 = 150000
    // Downtime: 30/60 * 100000 = 50000
    // Total: 225000
    expect($woCost->labour_cost)->toBe('25000.00');
    expect($woCost->parts_cost)->toBe('150000.00');
    expect($woCost->downtime_cost)->toBe('50000.00');
    expect($woCost->total_cost)->toBe('225000.00');
});

test('wo service sets status to completed', function () {
    $wo = WorkOrder::factory()->create([
        'status' => 'in_progress',
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'start',
        'timestamp' => now()->subMinutes(15),
    ]);
    
    WoProcesse::create([
        'work_order_id' => $wo->id,
        'performed_by_gpid' => 'TECH001',
        'action' => 'complete',
        'timestamp' => now(),
    ]);
    
    $this->woService->completeWorkOrder($wo, []);
    
    $wo->refresh();
    expect($wo->status)->toBe('completed');
    expect($wo->completed_at)->not->toBeNull();
});

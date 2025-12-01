<?php

use App\Models\User;
use App\Models\Part;
use App\Models\Inventorie;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->techStore = User::factory()->create([
        'gpid' => 'TS001',
        'role' => 'tech_store',
    ]);
    
    $this->part = Part::factory()->create([
        'part_number' => 'PN-001',
        'name' => 'Motor Bearing',
        'current_stock' => 50,
        'min_stock' => 10,
        'unit_price' => 100000,
    ]);
    
    $this->inventoryService = new InventoryService();
});

test('tech store can create inventory record', function () {
    $this->actingAs($this->techStore);
    
    $inventory = Inventorie::create([
        'part_id' => $this->part->id,
        'quantity' => 20,
        'min_stock' => 10,
        'location' => 'Rack-A-01',
    ]);
    
    expect($inventory)->not->toBeNull();
    expect($inventory->part_id)->toBe($this->part->id);
    expect($inventory->quantity)->toBe(20);
});

test('inventory belongs to part', function () {
    $inventory = Inventorie::factory()->create([
        'part_id' => $this->part->id,
    ]);
    
    expect($inventory->part)->not->toBeNull();
    expect($inventory->part->id)->toBe($this->part->id);
    expect($inventory->part->name)->toBe('Motor Bearing');
});

test('adding stock creates movement record', function () {
    $this->actingAs($this->techStore);
    
    $this->inventoryService->addStock($this->part->id, 20, 'Monthly restock');
    
    $movement = InventoryMovement::where('part_id', $this->part->id)
        ->where('movement_type', 'in')
        ->first();
    
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(20);
    expect($movement->notes)->toBe('Monthly restock');
    expect($movement->performed_by_gpid)->toBe('TS001');
});

test('adding stock increases part current stock', function () {
    $this->actingAs($this->techStore);
    
    $initialStock = $this->part->current_stock;
    
    $this->inventoryService->addStock($this->part->id, 30);
    
    $this->part->refresh();
    expect($this->part->current_stock)->toBe($initialStock + 30);
});

test('deducting stock decreases part current stock', function () {
    $this->actingAs($this->techStore);
    
    $initialStock = $this->part->current_stock;
    
    $this->inventoryService->deductPart($this->part->id, 15, 'work_order', 1);
    
    $this->part->refresh();
    expect($this->part->current_stock)->toBe($initialStock - 15);
});

test('deducting stock creates out movement record', function () {
    $this->actingAs($this->techStore);
    
    $this->inventoryService->deductPart($this->part->id, 10, 'pm_execution', 5);
    
    $movement = InventoryMovement::where('part_id', $this->part->id)
        ->where('movement_type', 'out')
        ->first();
    
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(10);
    expect($movement->reference_type)->toBe('pm_execution');
    expect($movement->reference_id)->toBe(5);
});

test('low stock triggers alert', function () {
    $this->actingAs($this->techStore);
    
    // Deduct to below minimum
    $this->inventoryService->deductPart($this->part->id, 45, 'work_order', 1);
    
    $this->part->refresh();
    expect($this->part->current_stock)->toBe(5);
    
    $alert = StockAlert::where('part_id', $this->part->id)
        ->where('alert_type', 'low_stock')
        ->where('is_resolved', false)
        ->first();
    
    expect($alert)->not->toBeNull();
    expect($alert->alert_type)->toBe('low_stock');
});

test('out of stock triggers alert', function () {
    $this->actingAs($this->techStore);
    
    // Deduct all stock
    $this->inventoryService->deductPart($this->part->id, 50, 'work_order', 2);
    
    $this->part->refresh();
    expect($this->part->current_stock)->toBe(0);
    
    $alert = StockAlert::where('part_id', $this->part->id)
        ->where('alert_type', 'out_of_stock')
        ->where('is_resolved', false)
        ->first();
    
    expect($alert)->not->toBeNull();
});

test('restocking above minimum resolves alert', function () {
    $this->actingAs($this->techStore);
    
    // Create low stock situation
    $this->part->update(['current_stock' => 5]);
    
    StockAlert::create([
        'part_id' => $this->part->id,
        'alert_type' => 'low_stock',
        'triggered_at' => now(),
        'is_resolved' => false,
    ]);
    
    // Restock above minimum
    $this->inventoryService->addStock($this->part->id, 20);
    
    $alert = StockAlert::where('part_id', $this->part->id)->first();
    
    expect($alert->is_resolved)->toBeTrue();
    expect($alert->resolved_at)->not->toBeNull();
});

test('stock movements are tracked chronologically', function () {
    $this->actingAs($this->techStore);
    
    // Multiple movements
    $this->inventoryService->addStock($this->part->id, 20, 'Initial stock');
    sleep(1);
    $this->inventoryService->deductPart($this->part->id, 5, 'work_order', 1);
    sleep(1);
    $this->inventoryService->addStock($this->part->id, 10, 'Restock');
    
    $movements = InventoryMovement::where('part_id', $this->part->id)
        ->orderBy('created_at')
        ->get();
    
    expect($movements)->toHaveCount(3);
    expect($movements->first()->movement_type)->toBe('in');
    expect($movements->first()->quantity)->toBe(20);
    expect($movements->last()->movement_type)->toBe('in');
    expect($movements->last()->quantity)->toBe(10);
});

test('part stock status is calculated correctly', function () {
    // Sufficient stock
    $part1 = Part::factory()->create([
        'current_stock' => 50,
        'min_stock' => 10,
    ]);
    expect($part1->current_stock)->toBeGreaterThan($part1->min_stock);
    
    // Low stock
    $part2 = Part::factory()->create([
        'current_stock' => 5,
        'min_stock' => 10,
    ]);
    expect($part2->current_stock)->toBeLessThan($part2->min_stock);
    
    // Out of stock
    $part3 = Part::factory()->create([
        'current_stock' => 0,
        'min_stock' => 10,
    ]);
    expect($part3->current_stock)->toBe(0);
});

test('inventory can be adjusted to specific quantity', function () {
    $this->actingAs($this->techStore);
    
    $this->inventoryService->adjustStock($this->part->id, 75, 'Physical count adjustment');
    
    $this->part->refresh();
    expect($this->part->current_stock)->toBe(75);
    
    $movement = InventoryMovement::where('part_id', $this->part->id)
        ->where('movement_type', 'adjustment')
        ->first();
    
    expect($movement)->not->toBeNull();
});

test('multiple inventories for same part sum correctly', function () {
    $inventory1 = Inventorie::factory()->create([
        'part_id' => $this->part->id,
        'quantity' => 20,
    ]);
    
    $inventory2 = Inventorie::factory()->create([
        'part_id' => $this->part->id,
        'quantity' => 15,
    ]);
    
    $inventory3 = Inventorie::factory()->create([
        'part_id' => $this->part->id,
        'quantity' => 10,
    ]);
    
    $totalQuantity = Inventorie::where('part_id', $this->part->id)->sum('quantity');
    
    expect($totalQuantity)->toBe('45');
});

test('inventory location can be updated', function () {
    $inventory = Inventorie::factory()->create([
        'part_id' => $this->part->id,
        'location' => 'Rack-A-01',
    ]);
    
    $inventory->update(['location' => 'Rack-B-05']);
    
    expect($inventory->fresh()->location)->toBe('Rack-B-05');
});

test('stock alert can be manually resolved', function () {
    $alert = StockAlert::create([
        'part_id' => $this->part->id,
        'alert_type' => 'low_stock',
        'triggered_at' => now(),
        'is_resolved' => false,
    ]);
    
    $alert->update([
        'is_resolved' => true,
        'resolved_at' => now(),
    ]);
    
    expect($alert->fresh()->is_resolved)->toBeTrue();
    expect($alert->fresh()->resolved_at)->not->toBeNull();
});

test('part shows last restocked date after adding stock', function () {
    $this->actingAs($this->techStore);
    
    expect($this->part->last_restocked_at)->toBeNull();
    
    $this->inventoryService->addStock($this->part->id, 10);
    
    $this->part->refresh();
    expect($this->part->last_restocked_at)->not->toBeNull();
});

<?php

use App\Services\InventoryService;
use App\Models\Part;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->inventoryService = new InventoryService();
    $this->user = User::factory()->create(['gpid' => 'TS001']);
    Auth::login($this->user);
});

test('inventory service deducts part stock correctly', function () {
    $part = Part::factory()->create([
        'current_stock' => 100,
        'min_stock' => 10,
    ]);
    
    $this->inventoryService->deductPart($part->id, 20, 'work_order', 1);
    
    $part->refresh();
    expect($part->current_stock)->toBe(80);
});

test('inventory service creates movement record when deducting', function () {
    $part = Part::factory()->create([
        'current_stock' => 100,
    ]);
    
    $this->inventoryService->deductPart($part->id, 15, 'pm_execution', 5);
    
    $movement = InventoryMovement::where('part_id', $part->id)->first();
    
    expect($movement)->not->toBeNull();
    expect($movement->movement_type)->toBe('out');
    expect($movement->quantity)->toBe(15);
    expect($movement->reference_type)->toBe('pm_execution');
    expect($movement->reference_id)->toBe(5);
    expect($movement->performed_by_gpid)->toBe('TS001');
});

test('inventory service adds stock and updates last restocked date', function () {
    $part = Part::factory()->create([
        'current_stock' => 50,
    ]);
    
    $this->inventoryService->addStock($part->id, 30, 'Restocking');
    
    $part->refresh();
    expect($part->current_stock)->toBe(80);
});

test('inventory service creates movement record when adding stock', function () {
    $part = Part::factory()->create([
        'current_stock' => 50,
    ]);
    
    $this->inventoryService->addStock($part->id, 25, 'Weekly restock');
    
    $movement = InventoryMovement::where('part_id', $part->id)
        ->where('movement_type', 'in')
        ->first();
    
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(25);
    expect($movement->notes)->toBe('Weekly restock');
    expect($movement->performed_by_gpid)->toBe('TS001');
});

test('inventory service creates low stock alert when below minimum', function () {
    $part = Part::factory()->create([
        'current_stock' => 20,
        'min_stock' => 10,
    ]);
    
    // Deduct to below min
    $this->inventoryService->deductPart($part->id, 15, 'work_order', 1);
    
    $part->refresh();
    expect($part->current_stock)->toBe(5);
    
    $alert = StockAlert::where('part_id', $part->id)
        ->where('alert_type', 'low_stock')
        ->where('is_resolved', false)
        ->first();
    
    expect($alert)->not->toBeNull();
});

test('inventory service creates out of stock alert when depleted', function () {
    $part = Part::factory()->create([
        'current_stock' => 5,
        'min_stock' => 10,
    ]);
    
    // Deduct all stock
    $this->inventoryService->deductPart($part->id, 5, 'work_order', 2);
    
    $part->refresh();
    expect($part->current_stock)->toBe(0);
    
    $alert = StockAlert::where('part_id', $part->id)
        ->where('alert_type', 'out_of_stock')
        ->where('is_resolved', false)
        ->first();
    
    expect($alert)->not->toBeNull();
});

test('inventory service resolves alerts when stock is sufficient', function () {
    $part = Part::factory()->create([
        'current_stock' => 5,
        'min_stock' => 10,
    ]);
    
    // Create low stock alert
    StockAlert::create([
        'part_id' => $part->id,
        'alert_type' => 'low_stock',
        'triggered_at' => now(),
        'is_resolved' => false,
    ]);
    
    // Add stock to above minimum
    $this->inventoryService->addStock($part->id, 20);
    
    $part->refresh();
    expect($part->current_stock)->toBe(25);
    
    // Alert should be resolved
    $alert = StockAlert::where('part_id', $part->id)->first();
    expect($alert->is_resolved)->toBeTrue();
    expect($alert->resolved_at)->not->toBeNull();
});

test('inventory service does not create duplicate alerts', function () {
    $part = Part::factory()->create([
        'current_stock' => 15,
        'min_stock' => 10,
    ]);
    
    // Deduct to low stock
    $this->inventoryService->deductPart($part->id, 10, 'work_order', 1);
    
    // Deduct again (still low)
    $this->inventoryService->deductPart($part->id, 2, 'work_order', 2);
    
    $alertCount = StockAlert::where('part_id', $part->id)
        ->where('is_resolved', false)
        ->count();
    
    // Should only have one unresolved alert
    expect($alertCount)->toBe(1);
});

test('inventory service adjusts stock correctly', function () {
    $part = Part::factory()->create([
        'current_stock' => 100,
    ]);
    
    // Adjust to specific quantity
    $this->inventoryService->adjustStock($part->id, 75, 'Stock count adjustment');
    
    $part->refresh();
    expect($part->current_stock)->toBe(75);
    
    $movement = InventoryMovement::where('part_id', $part->id)
        ->where('movement_type', 'adjustment')
        ->first();
    
    expect($movement)->not->toBeNull();
    expect($movement->notes)->toBe('Stock count adjustment');
});

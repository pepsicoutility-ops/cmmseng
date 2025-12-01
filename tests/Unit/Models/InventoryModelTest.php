<?php

use App\Models\Inventorie;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\Part;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inventory has correct fillable attributes', function () {
    $inventory = new Inventorie();
    
    expect($inventory->getFillable())->toContain('part_id', 'quantity', 'min_stock', 'location');
});

test('inventory belongs to part', function () {
    $part = Part::factory()->create();
    $inventory = Inventorie::factory()->create(['part_id' => $part->id]);
    
    expect($inventory->part)->toBeInstanceOf(Part::class);
    expect($inventory->part->id)->toBe($part->id);
});

test('inventory belongs to asset', function () {
    $asset = Asset::factory()->create();
    $part = Part::factory()->create();
    $inventory = Inventorie::factory()->create([
        'part_id' => $part->id,
        'asset_id' => $asset->id,
    ]);
    
    expect($inventory->asset)->toBeInstanceOf(Asset::class);
    expect($inventory->asset->id)->toBe($asset->id);
});

test('inventory has movements relationship', function () {
    $part = Part::factory()->create();
    
    expect($part->inventoryMovements())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('inventory can have multiple movements', function () {
    $part = Part::factory()->create();
    $user = User::factory()->create(['gpid' => 'TS001']);
    
    InventoryMovement::factory()->count(3)->create([
        'part_id' => $part->id,
        'performed_by_gpid' => 'TS001',
    ]);
    
    expect($part->inventoryMovements)->toHaveCount(3);
});

test('inventory movement belongs to part', function () {
    $part = Part::factory()->create();
    $user = User::factory()->create(['gpid' => 'TS001']);
    $movement = InventoryMovement::create([
        'part_id' => $part->id,
        'movement_type' => 'in',
        'quantity' => 10,
        'performed_by_gpid' => 'TS001',
    ]);
    
    expect($movement->part)->toBeInstanceOf(Part::class);
    expect($movement->part->id)->toBe($part->id);
});

test('inventory movement belongs to performed by user', function () {
    $part = Part::factory()->create();
    $user = User::factory()->create(['gpid' => 'TS002']);
    $movement = InventoryMovement::create([
        'part_id' => $part->id,
        'movement_type' => 'out',
        'quantity' => 5,
        'performed_by_gpid' => 'TS002',
    ]);
    
    expect($movement->performedBy)->toBeInstanceOf(User::class);
    expect($movement->performedBy->gpid)->toBe('TS002');
});

test('inventory movement has morph to reference relationship', function () {
    $part = Part::factory()->create();
    $user = User::factory()->create(['gpid' => 'TS001']);
    $movement = InventoryMovement::create([
        'part_id' => $part->id,
        'movement_type' => 'out',
        'quantity' => 2,
        'performed_by_gpid' => 'TS001',
    ]);
    
    expect($movement->reference())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

test('part has inventories relationship', function () {
    $part = Part::factory()->create();
    
    expect($part->inventories())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('part has inventory movements relationship', function () {
    $part = Part::factory()->create();
    
    expect($part->inventoryMovements())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('part has stock alerts relationship', function () {
    $part = Part::factory()->create();
    
    expect($part->stockAlerts())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('part casts prices to decimal', function () {
    $part = Part::factory()->create([
        'unit_price' => 150000.50,
    ]);

    // Decimal casts return strings in Laravel/MySQL
    expect($part->unit_price)->toBe('150000.50');
});

test('stock alert belongs to part', function () {
    $part = Part::factory()->create();
    
    $stockAlert = StockAlert::create([
        'part_id' => $part->id,
        'alert_type' => 'low_stock',
        'triggered_at' => now(),
        'is_resolved' => false,
    ]);
    
    expect($stockAlert->part)->toBeInstanceOf(Part::class);
    expect($stockAlert->part->id)->toBe($part->id);
});

test('stock alert casts is_resolved to boolean', function () {
    $part = Part::factory()->create();
    $stockAlert = StockAlert::create([
        'part_id' => $part->id,
        'alert_type' => 'out_of_stock',
        'triggered_at' => now(),
        'is_resolved' => 0,
    ]);
    
    expect($stockAlert->is_resolved)->toBeBool()->toBeFalse();
});

test('stock alert casts resolved_at to datetime', function () {
    $part = Part::factory()->create();
    $stockAlert = StockAlert::create([
        'part_id' => $part->id,
        'alert_type' => 'low_stock',
        'triggered_at' => now(),
        'is_resolved' => true,
        'resolved_at' => '2025-11-25 12:00:00',
    ]);
    
    expect($stockAlert->resolved_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

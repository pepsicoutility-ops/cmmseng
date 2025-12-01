<?php

use App\Models\Area;
use App\Models\SubArea;
use App\Models\Asset;
use App\Models\SubAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('area has correct fillable attributes', function () {
    $fillable = ['name', 'code', 'description', 'is_active'];
    $area = new Area();
    
    expect($area->getFillable())->toBe($fillable);
});

test('area is_active is cast to boolean', function () {
    $area = Area::factory()->create(['is_active' => 1]);
    
    expect($area->is_active)->toBeBool()->toBeTrue();
});

test('area has sub areas relationship', function () {
    $area = Area::factory()->create();
    
    expect($area->subAreas())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('area has assets relationship', function () {
    $area = Area::factory()->create();
    
    expect($area->assets())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('area has work orders relationship', function () {
    $area = Area::factory()->create();
    
    expect($area->workOrders())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('area has inventories relationship', function () {
    $area = Area::factory()->create();
    
    expect($area->inventories())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('area can have multiple sub areas', function () {
    $area = Area::factory()->create();
    SubArea::factory()->count(3)->create(['area_id' => $area->id]);
    
    expect($area->subAreas)->toHaveCount(3);
});

test('sub area belongs to area', function () {
    $area = Area::factory()->create();
    $subArea = SubArea::factory()->create(['area_id' => $area->id]);
    
    expect($subArea->area)->toBeInstanceOf(Area::class);
    expect($subArea->area->id)->toBe($area->id);
});

test('sub area has assets relationship', function () {
    $subArea = SubArea::factory()->create();
    
    expect($subArea->assets())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('asset belongs to sub area', function () {
    $subArea = SubArea::factory()->create();
    $asset = Asset::factory()->create(['sub_area_id' => $subArea->id]);
    
    expect($asset->subArea)->toBeInstanceOf(SubArea::class);
    expect($asset->subArea->id)->toBe($subArea->id);
});

test('asset has sub assets relationship', function () {
    $asset = Asset::factory()->create();
    
    expect($asset->subAssets())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('asset can have multiple sub assets', function () {
    $asset = Asset::factory()->create();
    SubAsset::factory()->count(2)->create(['asset_id' => $asset->id]);
    
    expect($asset->subAssets)->toHaveCount(2);
});

test('sub asset belongs to asset', function () {
    $asset = Asset::factory()->create();
    $subAsset = SubAsset::factory()->create(['asset_id' => $asset->id]);
    
    expect($subAsset->asset)->toBeInstanceOf(Asset::class);
    expect($subAsset->asset->id)->toBe($asset->id);
});

test('cascade area to sub area to asset to sub asset works', function () {
    $area = Area::factory()->create(['name' => 'Proses']);
    $subArea = SubArea::factory()->create(['area_id' => $area->id, 'name' => 'EP']);
    $asset = Asset::factory()->create(['sub_area_id' => $subArea->id, 'name' => 'Processing']);
    $subAsset = SubAsset::factory()->create(['asset_id' => $asset->id, 'name' => 'Fryer']);
    
    expect($subAsset->asset->subArea->area->name)->toBe('Proses');
    expect($subAsset->asset->subArea->name)->toBe('EP');
    expect($subAsset->asset->name)->toBe('Processing');
});

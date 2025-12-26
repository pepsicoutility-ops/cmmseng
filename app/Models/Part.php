<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Part Model (Master Data)
 *
 * Represents a spare part or consumable in the CMMS inventory system.
 * Syncs two-way with Inventories table for location-based stock tracking.
 * Automatically triggers stock alerts when current_stock <= min_stock.
 *
 * @property int $id Primary key
 * @property string $part_number Unique part number (e.g., PART-001)
 * @property string $name Part name
 * @property string|null $description Part description
 * @property string|null $category Part category (Spare Part/Consumable/Tool/etc.)
 * @property string $unit Unit of measurement (pcs/box/liter/etc.)
 * @property int $min_stock Minimum stock level (alert threshold)
 * @property int $current_stock Current stock quantity (synced with inventories)
 * @property float $unit_price Price per unit
 * @property string|null $location Storage location
 * @property Carbon|null $last_restocked_at Last restock timestamp
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at Soft delete timestamp
 *
 * @property-read Collection|Inventorie[] $inventories Location-based inventory records
 * @property-read Collection|InventoryMovement[] $inventoryMovements
 * @property-read Collection|PmPartsUsage[] $pmPartsUsages
 * @property-read Collection|WoPartsUsage[] $woPartsUsages
 * @property-read Collection|StockAlert[] $stockAlerts
 *
 * @method static Builder|Part newModelQuery()
 * @method static Builder|Part newQuery()
 * @method static Builder|Part query()
 * @method static Builder|Part whereCategory(string $category)
 * @method static Builder|Part lowStock() Parts at or below min_stock
 *
 * @package App\Models
 * @mixin Builder
 */
class Part extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'part_number',
        'name',
        'description',
        'category',
        'unit',
        'min_stock',
        'current_stock',
        'unit_price',
        'location',
        'last_restocked_at',
    ];

    protected $casts = [
        'min_stock' => 'integer',
        'current_stock' => 'integer',
        'unit_price' => 'decimal:2',
        'last_restocked_at' => 'datetime',
    ];

    // Relationships
    
    /**
     * Get all inventory records for this part (location-based stock)
     * 
     * @return HasMany<Inventory>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get all inventory movement history for this part
     * 
     * @return HasMany<InventoryMovement>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get all PM executions that used this part
     * 
     * @return HasMany<PmPartsUsage>
     */
    public function pmPartsUsages(): HasMany
    {
        return $this->hasMany(PmPartsUsage::class);
    }

    /**
     * Get all work orders that used this part
     * 
     * @return HasMany<WoPartsUsage>
     */
    public function woPartsUsages(): HasMany
    {
        return $this->hasMany(WoPartsUsage::class);
    }

    /**
     * Get all stock alerts for this part
     * 
     * @return HasMany<StockAlert>
     */
    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    // Helper methods
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock == 0;
    }

    /**
     * Get total stock from all inventories
     */
    public function getTotalInventoryStock(): int
    {
        return $this->inventories()->sum('quantity');
    }

    /**
     * Sync current_stock from all inventories
     */
    public function syncStockFromInventories(): void
    {
        $this->current_stock = $this->getTotalInventoryStock();
        $this->saveQuietly();
    }
}

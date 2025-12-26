<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     * Explicitly set because class name is Inventory (singular)
     * but table is inventories (plural).
     */
    protected $table = 'inventories';

    protected $fillable = [
        'part_id',
        'area_id',
        'sub_area_id',
        'asset_id',
        'sub_asset_id',
        'quantity',
        'min_stock',
        'max_stock',
        'location',
        'last_restocked_at',
    ];

    protected static function booted()
    {
        // Sync Parts current_stock when Inventory is created
        static::created(function ($inventory) {
            $inventory->syncPartStock();
            $inventory->syncPartMetadata();
        });

        // Sync Parts current_stock when Inventory quantity is updated
        static::updated(function ($inventory) {
            if ($inventory->wasChanged('quantity') || $inventory->wasChanged('part_id')) {
                $inventory->syncPartStock();
                // If part_id changed, also sync old part
                if ($inventory->wasChanged('part_id')) {
                    $oldPartId = $inventory->getOriginal('part_id');
                    if ($oldPartId) {
                        $oldPart = Part::find($oldPartId);
                        if ($oldPart) {
                            $oldPart->current_stock = $oldPart->inventories()->sum('quantity');
                            $oldPart->saveQuietly();
                        }
                    }
                }
            }
            
            // Sync min_stock and location to Part
            if ($inventory->wasChanged('min_stock') || $inventory->wasChanged('location')) {
                $inventory->syncPartMetadata();
            }
        });

        // Sync Parts current_stock when Inventory is deleted
        static::deleted(function ($inventory) {
            $inventory->syncPartStock();
        });
    }

    protected $casts = [
        'quantity' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'last_restocked_at' => 'datetime',
    ];

    // Relationships
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function subAsset(): BelongsTo
    {
        return $this->belongsTo(SubAsset::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Sync the parent Part's current_stock with sum of all inventories
     */
    public function syncPartStock(): void
    {
        if ($this->part_id) {
            $part = $this->part;
            if ($part) {
                $totalStock = $part->inventories()->sum('quantity');
                $part->current_stock = $totalStock;
                $part->saveQuietly(); // Use saveQuietly to avoid triggering observers
            }
        }
    }

    /**
     * Sync min_stock and location to the parent Part
     */
    public function syncPartMetadata(): void
    {
        if ($this->part_id) {
            $part = $this->part;
            if ($part) {
                $needsUpdate = false;
                
                if ($part->min_stock != $this->min_stock) {
                    $part->min_stock = $this->min_stock;
                    $needsUpdate = true;
                }
                
                if ($part->location != $this->location) {
                    $part->location = $this->location;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    $part->saveQuietly();
                }
            }
        }
    }
}

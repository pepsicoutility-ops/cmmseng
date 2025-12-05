<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Models\Part;
use Illuminate\Support\Facades\DB;

class InventoryMovementObserver
{
    /**
     * Handle the InventoryMovement "creating" event.
     * Update part stock before creating the movement record.
     *
     * @param  \App\Models\InventoryMovement  $inventoryMovement
     * @return void
     */
    public function creating(InventoryMovement $inventoryMovement): void
    {
        $part = Part::find($inventoryMovement->part_id);
        
        if (!$part) {
            return;
        }

        // Store quantity before movement
        $inventoryMovement->quantity_before = $part->current_stock;

        // Calculate new stock based on movement type
        if ($inventoryMovement->movement_type === 'in') {
            $newStock = $part->current_stock + $inventoryMovement->quantity;
        } elseif ($inventoryMovement->movement_type === 'out') {
            $newStock = $part->current_stock - $inventoryMovement->quantity;
        } else {
            // For adjustment type, the quantity is the new absolute value
            $newStock = $inventoryMovement->quantity;
        }

        // Ensure stock doesn't go negative
        $newStock = max(0, $newStock);

        // Store quantity after movement
        $inventoryMovement->quantity_after = $newStock;

        // Update part's current stock
        $part->current_stock = $newStock;
        
        // Update last_restocked_at if it's an 'in' movement
        if ($inventoryMovement->movement_type === 'in') {
            $part->last_restocked_at = now();
        }
        
        $part->save();
    }

    /**
     * Handle the InventoryMovement "deleted" event.
     * Reverse the stock change when a movement is deleted.
     *
     * @param  \App\Models\InventoryMovement  $inventoryMovement
     * @return void
     */
    public function deleted(InventoryMovement $inventoryMovement): void
    {
        $part = Part::find($inventoryMovement->part_id);
        
        if (!$part) {
            return;
        }

        // Reverse the stock change
        if ($inventoryMovement->movement_type === 'in') {
            $part->current_stock -= $inventoryMovement->quantity;
        } elseif ($inventoryMovement->movement_type === 'out') {
            $part->current_stock += $inventoryMovement->quantity;
        }

        // Ensure stock doesn't go negative
        $part->current_stock = max(0, $part->current_stock);
        
        $part->save();
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Part;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\PmExecution;
use App\Models\WorkOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Service
 * 
 * Business logic service for Inventory Management operations.
 * Handles stock deduction, movement tracking, alert generation, and two-way sync
 * between Parts (master data) and Inventories (location-based stock).
 * 
 * Key Features:
 * - Automatic stock deduction when parts are used in WO/PM
 * - Two-way sync between parts.current_stock and inventories.quantity
 * - Stock movement history (in/out tracking)
 * - Low stock alert generation when current_stock <= min_stock
 * - Inventory restocking with timestamp tracking
 * 
 * @package App\Services
 */
class InventoryService
{
    /**
     * Deduct parts used in PM Execution
     * 
     * Iterates through all parts usage records in a PM execution
     * and deducts each part quantity from inventory.
     * Creates inventory movement records for audit trail.
     * 
     * @param PmExecution $execution PM execution with parts usage
     * @return void
     * 
     * @see deductPart() For individual part deduction logic
     */
    public function deductPartsFromPmExecution(PmExecution $execution): void
    {
        $partsUsage = $execution->partsUsage;
        
        foreach ($partsUsage as $usage) {
            $this->deductPart(
                $usage->part_id,
                $usage->quantity,
                'pm_execution',
                $execution->id
            );
        }
    }
    
    /**
     * Deduct parts used in Work Order
     * 
     * Iterates through all parts usage records in a work order
     * and deducts each part quantity from inventory.
     * Creates inventory movement records for audit trail.
     * 
     * @param WorkOrder $wo Work order with parts usage
     * @return void
     * 
     * @see deductPart() For individual part deduction logic
     */
    public function deductPartsFromWorkOrder(WorkOrder $wo): void
    {
        $partsUsage = $wo->partsUsage;
        
        foreach ($partsUsage as $usage) {
            $this->deductPart(
                $usage->part_id,
                $usage->quantity,
                'work_order',
                $wo->id
            );
        }
    }
    
    /**
     * Deduct parts from inventory
     *
     * Core method that handles:
     * 1. Decrement parts.current_stock
     * 2. Create inventory_movements record (movement_type = 'out')
     * 3. Check if stock falls below min_stock and create alert if needed
     * 4. Two-way sync with inventories table
     *
     * @param int $partId ID of the part to deduct
     * @param int $quantity Quantity to deduct (must be positive)
     * @param string $referenceType Source of deduction ('work_order' or 'pm_execution')
     * @param int $referenceId ID of the work order or PM execution
     * @return void
     *
     * @throws ModelNotFoundException If part not found
     *
     * @example
     * // Deduct 3 units of part #5 for work order #123
     * $service->deductPart(5, 3, 'work_order', 123);
     */
    public function deductPart(
        int $partId,
        int $quantity,
        string $referenceType,
        int $referenceId
    ): void {
        $part = Part::findOrFail($partId);

        // Create inventory movement (observer adjusts stock)
        InventoryMovement::create([
            'part_id' => $partId,
            'movement_type' => 'out',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by_gpid' => Auth::user()->gpid ?? 'SYSTEM',
            'notes' => "Auto deduct from {$referenceType} #{$referenceId}"
        ]);
        
        // Reload part after observer updated stock, then check alerts
        $part->refresh();

        $this->checkStockAlert($part);
    }
    
    /**
     * Add stock to inventory
     */
    public function addStock(int $partId, int $quantity, ?string $notes = null): void
    {
        $part = Part::findOrFail($partId);

        // Create inventory movement (observer adjusts stock and restock timestamp)
        InventoryMovement::create([
            'part_id' => $partId,
            'movement_type' => 'in',
            'quantity' => $quantity,
            'reference_type' => 'manual',
            'performed_by_gpid' => Auth::user()->gpid ?? 'SYSTEM',
            'notes' => $notes ?? 'Manual stock addition'
        ]);

        $part->refresh();

        // Resolve stock alerts if stock is sufficient now
        if ($part->current_stock >= $part->min_stock) {
            StockAlert::where('part_id', $partId)
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolved_by_gpid' => Auth::user()->gpid ?? 'SYSTEM',
                ]);
        }
    }
    
    /**
     * Adjust stock to a specific quantity
     */
    public function adjustStock(int $partId, int $quantity, ?string $notes = null): void
    {
        $part = Part::findOrFail($partId);

        // For adjustment, quantity represents the new exact stock level; observer will set before/after.
        InventoryMovement::create([
            'part_id' => $partId,
            'movement_type' => 'adjustment',
            'quantity' => $quantity,
            'reference_type' => 'manual',
            'performed_by_gpid' => Auth::user()->gpid ?? 'SYSTEM',
            'notes' => $notes ?? 'Stock adjustment'
        ]);
        
        $part->refresh();

        // Check stock alert with updated stock
        $this->checkStockAlert($part);
        
        // Resolve alerts if stock is sufficient now
        if ($part->current_stock >= $part->min_stock) {
            StockAlert::where('part_id', $partId)
                ->where('is_resolved', false)
                ->update(['is_resolved' => true]);
        }
    }
    
    /**
     * Check if stock alert should be created
     */
    private function checkStockAlert(Part $part): void
    {
        // Only create alert if not already exists
        $existingAlert = StockAlert::where('part_id', $part->id)
            ->where('is_resolved', false)
            ->first();
            
        if ($existingAlert) {
            return; // Alert already exists
        }
        
        // Determine alert type
        $alertType = null;
        if ($part->current_stock == 0) {
            $alertType = 'out_of_stock';
        } elseif ($part->current_stock <= $part->min_stock) {
            $alertType = 'low_stock';
        }
        
        // Create alert if necessary
        if ($alertType) {
            StockAlert::create([
                'part_id' => $part->id,
                'alert_type' => $alertType,
                'triggered_at' => now(),
                'is_resolved' => false
            ]);
            
            // Send WhatsApp notification to tech_store users
            $this->sendStockAlertNotification($part, $alertType);
        }
    }
    
    /**
     * Send WhatsApp notification to tech_store users about stock alert
     */
    private function sendStockAlertNotification(Part $part, string $alertType): void
    {
        try {
            $emoji = $alertType === 'out_of_stock' ? '🚨' : '⚠️';
            $severity = $alertType === 'out_of_stock' ? 'CRITICAL' : 'WARNING';
            
            $message = "{$emoji} *STOCK ALERT - {$severity}*\n\n";
            $message .= "📦 *Part:* {$part->name}\n";
            $message .= "🔢 *Part Number:* {$part->part_number}\n";
            $message .= "📊 *Current Stock:* {$part->current_stock}\n";
            $message .= "📉 *Minimum Stock:* {$part->min_stock}\n";
            $message .= "📍 *Location:* " . ($part->location ?? 'N/A') . "\n";
            $message .= "⏰ *Time:* " . now()->format('d/m/Y H:i') . "\n\n";
            
            if ($alertType === 'out_of_stock') {
                $message .= "❌ *Status:* OUT OF STOCK - Immediate restock required!";
            } else {
                $deficit = $part->min_stock - $part->current_stock;
                $message .= "⚡ *Status:* LOW STOCK - Restock {$deficit} units recommended.";
            }
            
            // Send via WhatsApp service
            $whatsAppService = app(WhatsAppService::class);
            $whatsAppService->sendMessage($message);
            
            Log::info('Stock alert notification sent', [
                'part_id' => $part->id,
                'alert_type' => $alertType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send stock alert notification', [
                'part_id' => $part->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

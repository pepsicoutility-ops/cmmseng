<?php

namespace App\Console\Commands;

use App\Models\Part;
use Illuminate\Console\Command;

class SyncInventoryStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Parts current_stock, min_stock, and location with all Inventories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing inventory data...');

        $parts = Part::with('inventories')->get();
        $stockSynced = 0;
        $metadataSynced = 0;

        foreach ($parts as $part) {
            $needsUpdate = false;
            $changes = [];
            
            // Sync current_stock from inventories
            $totalInventoryStock = $part->inventories()->sum('quantity');
            if ($part->current_stock != $totalInventoryStock) {
                $changes[] = "stock: {$part->current_stock} → {$totalInventoryStock}";
                $part->current_stock = $totalInventoryStock;
                $needsUpdate = true;
                $stockSynced++;
            }
            
            // Sync min_stock and location to all inventories
            foreach ($part->inventories as $inventory) {
                $inventoryNeedsUpdate = false;
                
                if ($inventory->min_stock != $part->min_stock) {
                    $inventory->min_stock = $part->min_stock;
                    $inventoryNeedsUpdate = true;
                }
                
                if ($inventory->location != $part->location) {
                    $inventory->location = $part->location;
                    $inventoryNeedsUpdate = true;
                }
                
                if ($inventoryNeedsUpdate) {
                    $inventory->saveQuietly();
                    $metadataSynced++;
                }
            }
            
            if ($needsUpdate) {
                $part->saveQuietly();
                $this->line("Part #{$part->id} ({$part->name}): " . implode(', ', $changes));
            }
        }

        $this->info("Sync complete!");
        $this->info("- Updated {$stockSynced} parts stock");
        $this->info("- Updated {$metadataSynced} inventories metadata (min_stock, location)");
        
        return Command::SUCCESS;
    }
}

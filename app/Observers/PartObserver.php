<?php

namespace App\Observers;

use App\Models\Part;
use App\Services\TelegramService;
use Filament\Notifications\Notification;
use App\Models\User;

class PartObserver
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle the Part "updated" event.
     */
    public function updated(Part $part): void
    {
        // Check if current stock fell below minimum stock
        if ($part->current_stock < $part->min_stock) {
            // Send Telegram notification
            $this->telegram->sendStockAlert([
                'name' => $part->name,
                'part_number' => $part->part_number,
                'current_stock' => $part->current_stock,
                'min_stock' => $part->min_stock,
                'unit' => $part->unit,
                'location' => $part->location
            ]);

            // Send in-app notification to tech_store users
            $techStoreUsers = User::where('role', 'tech_store')->get();
            
            foreach ($techStoreUsers as $user) {
                Notification::make()
                    ->title('Stock Alert: Low Stock')
                    ->body("Part {$part->name} ({$part->part_number}) is below minimum stock. Current: {$part->current_stock} {$part->unit}, Min: {$part->min_stock} {$part->unit}")
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }
        }
    }
}

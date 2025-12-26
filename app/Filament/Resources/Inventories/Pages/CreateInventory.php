<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Models\Part;
use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set last_restocked_at to now if quantity is added
        if (!isset($data['last_restocked_at']) && isset($data['quantity']) && $data['quantity'] > 0) {
            $data['last_restocked_at'] = now();
        }

        // Sync min_stock and location from Part
        if (isset($data['part_id'])) {
            $part = Part::find($data['part_id']);
            if ($part) {
                $data['min_stock'] = $part->min_stock;
                $data['location'] = $part->location;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Ensure Part has the same values
        $part = $this->record->part;
        if ($part) {
            $needsUpdate = false;
            if ($part->min_stock != $this->record->min_stock) {
                $part->min_stock = $this->record->min_stock;
                $needsUpdate = true;
            }
            if ($part->location != $this->record->location) {
                $part->location = $this->record->location;
                $needsUpdate = true;
            }
            if ($needsUpdate) {
                $part->saveQuietly();
            }
        }
    }
}

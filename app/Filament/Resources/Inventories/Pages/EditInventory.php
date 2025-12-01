<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => \Illuminate\Support\Facades\Auth::user()->role === 'super_admin'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load min_stock and location from Part when editing
        if (isset($data['part_id'])) {
            $part = \App\Models\Part::find($data['part_id']);
            if ($part) {
                $data['min_stock'] = $part->min_stock;
                $data['location'] = $part->location;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update last_restocked_at if quantity increased
        $originalQuantity = $this->record->quantity ?? 0;
        if (isset($data['quantity']) && $data['quantity'] > $originalQuantity) {
            $data['last_restocked_at'] = now();
        }

        // Always sync min_stock and location from Part
        if (isset($data['part_id'])) {
            $part = \App\Models\Part::find($data['part_id']);
            if ($part) {
                $data['min_stock'] = $part->min_stock;
                $data['location'] = $part->location;
            }
        }

        return $data;
    }

    protected function afterSave(): void
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

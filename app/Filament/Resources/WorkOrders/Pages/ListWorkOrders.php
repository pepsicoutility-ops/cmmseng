<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        
        // Operators can only create WO via barcode
        if ($user && $user->role === 'operator') {
            return [];
        }
        
        return [
            CreateAction::make(),
        ];
    }
}

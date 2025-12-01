<?php

namespace App\Filament\Resources\BarcodeTokens\Pages;

use App\Filament\Resources\BarcodeTokens\BarcodeTokenResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBarcodeToken extends CreateRecord
{
    protected static string $resource = BarcodeTokenResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate token if not provided
        if (empty($data['token'])) {
            $data['token'] = (string) Str::uuid();
        }
        
        return $data;
    }
}

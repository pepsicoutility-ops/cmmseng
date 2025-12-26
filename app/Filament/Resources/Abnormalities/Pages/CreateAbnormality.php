<?php

namespace App\Filament\Resources\Abnormalities\Pages;

use App\Filament\Resources\Abnormalities\AbnormalityResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAbnormality extends CreateRecord
{
    protected static string $resource = AbnormalityResource::class;

    public function getTitle(): string
    {
        return 'Report Abnormality';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reported_by'] = Auth::user()->gpid;
        $data['status'] = 'open';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

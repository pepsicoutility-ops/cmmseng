<?php

namespace App\Filament\Resources\RootCauseAnalyses\Pages;

use App\Filament\Resources\RootCauseAnalyses\RootCauseAnalysisResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRootCauseAnalysis extends CreateRecord
{
    protected static string $resource = RootCauseAnalysisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_gpid'] = Auth::user()->gpid;
        $data['status'] = 'draft';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

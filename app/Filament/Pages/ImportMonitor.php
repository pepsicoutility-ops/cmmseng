<?php

namespace App\Filament\Pages;

use App\Models\ExcelImport;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ImportMonitor extends Page
{
    protected static ?string $title = 'Import Monitor';
    protected static ?string $slug = 'import-monitor/{import}';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.import-monitor';

    public int $importId;

    public function mount(int $import): void
    {
        $this->importId = $import;
    }

    public function getImportProperty(): ExcelImport
    {
        return ExcelImport::query()
            ->whereKey($this->importId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}

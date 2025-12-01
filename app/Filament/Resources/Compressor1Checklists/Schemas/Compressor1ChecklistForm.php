<?php

namespace App\Filament\Resources\Compressor1Checklists\Schemas;

use App\Filament\Resources\Shared\CompressorChecklistFormSchema;
use Filament\Schemas\Schema;

class Compressor1ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(CompressorChecklistFormSchema::getSchema());
    }
}

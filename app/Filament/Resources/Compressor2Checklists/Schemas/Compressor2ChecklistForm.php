<?php

namespace App\Filament\Resources\Compressor2Checklists\Schemas;

use App\Filament\Resources\Shared\CompressorChecklistFormSchema;
use Filament\Schemas\Schema;

class Compressor2ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(CompressorChecklistFormSchema::getSchema());
    }
}

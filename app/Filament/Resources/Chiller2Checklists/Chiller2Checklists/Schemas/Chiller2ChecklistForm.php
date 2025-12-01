<?php

namespace App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Schemas;

use App\Filament\Resources\Shared\ChillerChecklistFormSchema;
use Filament\Schemas\Schema;

class Chiller2ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(ChillerChecklistFormSchema::getSchema());
    }
}

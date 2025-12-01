<?php

namespace App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Schemas;

use App\Filament\Resources\Shared\ChillerChecklistFormSchema;
use Filament\Schemas\Schema;

class Chiller1ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(ChillerChecklistFormSchema::getSchema());
    }
}

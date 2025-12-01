<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\User;

class AhuChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Operator details and shift information')
                    ->columns(3)
                    ->schema([
                        Select::make('shift')
                            ->label('Shift')
                            ->options([
                                '1' => 'Shift 1',
                                '2' => 'Shift 2',
                                '3' => 'Shift 3',
                            ])
                            ->required()
                            ->native(false),
                        
                        TextInput::make('gpid')
                            ->label('GPID')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $user = User::where('gpid', $state)->first();
                                    $set('name', $user?->name);
                                } else {
                                    $set('name', null);
                                }
                            }),
                        
                        TextInput::make('name')
                            ->label('Operator Name')
                            ->disabled()
                            ->dehydrated(),
                    ]),
                
                Section::make('AHU MB-1 Measurements')
                    ->description('Air Handling Unit MB-1 filter measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ahu_mb_1_1_hf')->label('AHU MB-1.1: HF'),
                        TextInput::make('ahu_mb_1_1_pf')->label('AHU MB-1.1: PF'),
                        TextInput::make('ahu_mb_1_1_mf')->label('AHU MB-1.1: MF'),
                        TextInput::make('ahu_mb_1_2_hf')->label('AHU MB-1.2: HF'),
                        TextInput::make('ahu_mb_1_2_mf')->label('AHU MB-1.2: MF'),
                        TextInput::make('ahu_mb_1_2_pf')->label('AHU MB-1.2: PF'),
                        TextInput::make('ahu_mb_1_3_hf')->label('AHU MB-1.3: HF'),
                        TextInput::make('ahu_mb_1_3_mf')->label('AHU MB-1.3: MF'),
                        TextInput::make('ahu_mb_1_3_pf')->label('AHU MB-1.3: PF'),
                    ]),
                
                Section::make('PAU MB Measurements')
                    ->description('Pre-cooling AHU measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('pau_mb_1_pf')->label('PAU MB-1: PF')->columnSpan(3),
                        TextInput::make('pau_mb_pr_1a_hf')->label('PAU MB PR-1A: HF'),
                        TextInput::make('pau_mb_pr_1a_mf')->label('PAU MB PR-1A: MF'),
                        TextInput::make('pau_mb_pr_1a_pf')->label('PAU MB PR-1A: PF'),
                        TextInput::make('pau_mb_pr_1b_hf')->label('PAU MB PR-1B: HF'),
                        TextInput::make('pau_mb_pr_1b_mf')->label('PAU MB PR-1B: MF'),
                        TextInput::make('pau_mb_pr_1b_pf')->label('PAU MB PR-1B: PF'),
                        TextInput::make('pau_mb_pr_1c_hf')->label('PAU MB PR-1C: HF'),
                        TextInput::make('pau_mb_pr_1c_pf')->label('PAU MB PR-1C: PF'),
                        TextInput::make('pau_mb_pr_1c_mf')->label('PAU MB PR-1C: MF'),
                    ]),
                
                Section::make('AHU VRF MB Measurements')
                    ->description('VRF Main Supply and Sub Supply measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ahu_vrf_mb_ms_1a_pf')->label('VRF MB-MS-1A: PF'),
                        TextInput::make('ahu_vrf_mb_ms_1b_pf')->label('VRF MB-MS-1B: PF'),
                        TextInput::make('ahu_vrf_mb_ms_1c_pf')->label('VRF MB-MS-1C: PF'),
                        TextInput::make('ahu_vrf_mb_ss_1a_pf')->label('VRF MB-SS-1A: PF'),
                        TextInput::make('ahu_vrf_mb_ss_1b_pf')->label('VRF MB-SS-1B: PF'),
                        TextInput::make('ahu_vrf_mb_ss_1c_pf')->label('VRF MB-SS-1C: PF'),
                    ]),
                
                Section::make('Inline Filters A & B')
                    ->description('IF A & B filter measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('if_pre_filter_a')->label('IF Pre Filter A'),
                        TextInput::make('if_medium_a')->label('IF Medium A'),
                        TextInput::make('if_hepa_a')->label('IF HEPA A'),
                        TextInput::make('if_pre_filter_b')->label('IF Pre Filter B'),
                        TextInput::make('if_medium_b')->label('IF Medium B'),
                        TextInput::make('if_hepa_b')->label('IF HEPA B'),
                    ]),
                
                Section::make('Inline Filters C & D')
                    ->description('IF C & D filter measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('if_pre_filter_c')->label('IF Pre Filter C'),
                        TextInput::make('if_medium_c')->label('IF Medium C'),
                        TextInput::make('if_hepa_c')->label('IF HEPA C'),
                        TextInput::make('if_pre_filter_d')->label('IF Pre Filter D'),
                        TextInput::make('if_medium_d')->label('IF Medium D'),
                        TextInput::make('if_hepa_d')->label('IF HEPA D'),
                    ]),
                
                Section::make('Inline Filters E & F')
                    ->description('IF E & F filter measurements')
                    ->columns(3)
                    ->schema([
                        TextInput::make('if_pre_filter_e')->label('IF Pre Filter E'),
                        TextInput::make('if_medium_e')->label('IF Medium E'),
                        TextInput::make('if_hepa_e')->label('IF HEPA E'),
                        TextInput::make('if_pre_filter_f')->label('IF Pre Filter F'),
                        TextInput::make('if_medium_f')->label('IF Medium F'),
                        TextInput::make('if_hepa_f')->label('IF HEPA F'),
                    ]),
                
                Section::make('Additional Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->columnSpan('full'),
                    ]),
            ]);
    }
}

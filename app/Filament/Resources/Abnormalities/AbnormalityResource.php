<?php

namespace App\Filament\Resources\Abnormalities;

use App\Filament\Resources\Abnormalities\Pages\CreateAbnormality;
use App\Filament\Resources\Abnormalities\Pages\EditAbnormality;
use App\Filament\Resources\Abnormalities\Pages\ListAbnormalities;
use App\Filament\Resources\Abnormalities\Pages\ViewAbnormality;
use App\Filament\Resources\Abnormalities\Tables\AbnormalitiesTable;
use App\Models\Abnormality;
use App\Models\Area;
use App\Models\Asset;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Traits\HasRoleBasedAccess;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AbnormalityResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = Abnormality::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Abnormalities';

    protected static string | UnitEnum | null $navigationGroup = 'Performance KPIs';

    protected static ?int $navigationSort = 2;

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }

    public static function form(Schema $schema): Schema
    {
        $user = Auth::user();
        $isManager = $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);

        return $schema->components([
            Section::make('Abnormality Information')
                ->columns(2)
                ->components([
                    Placeholder::make('abnormality_no_display')
                        ->label('Abnormality No')
                        ->content(fn ($record) => $record?->abnormality_no ?? 'Auto-generated')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Brief description of the abnormality')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),

                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->rows(3)
                        ->placeholder('Detailed description of the abnormality found')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),

                    TextInput::make('location')
                        ->label('Location')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Where was this abnormality found?')
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),

                    Select::make('area_id')
                        ->label('Area')
                        ->options(Area::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('asset_id', null)),

                    Select::make('asset_id')
                        ->label('Asset')
                        ->options(fn (callable $get) => 
                            Asset::when($get('area_id'), fn ($q) => $q->where('area_id', $get('area_id')))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload(),

                    DatePicker::make('found_date')
                        ->label('Found Date')
                        ->required()
                        ->default(now())
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),

                    Select::make('severity')
                        ->label('Severity')
                        ->required()
                        ->options(Abnormality::getSeverities())
                        ->default(Abnormality::SEVERITY_MEDIUM)
                        ->helperText('Deadline will be auto-calculated based on severity')
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),

                    Placeholder::make('deadline_display')
                        ->label('Deadline')
                        ->content(fn ($record) => $record?->deadline?->format('d/m/Y') ?? 'Auto-calculated after save')
                        ->visible(fn ($record) => $record !== null),

                    FileUpload::make('photo')
                        ->label('Evidence Photo')
                        ->image()
                        ->disk('public')
                        ->directory('abnormalities')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_OPEN])),
                ]),

            Section::make('Assignment')
                ->columns(2)
                ->visible(fn ($record) => $record !== null)
                ->components([
                    Select::make('assigned_to')
                        ->label('Assigned To')
                        ->options(
                            User::whereIn('role', ['technician', 'senior_technician', 'supervisor'])
                                ->whereIn('department', ['utility', 'mechanic', 'electric'])
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($user) => [$user->gpid => "{$user->name} ({$user->department})"])
                        )
                        ->searchable()
                        ->preload()
                        ->visible($isManager),

                    Placeholder::make('assigned_to_display')
                        ->label('Assigned To')
                        ->content(fn ($record) => $record?->assignee?->name ?? '-')
                        ->visible(!$isManager),

                    Placeholder::make('assigned_at_display')
                        ->label('Assigned At')
                        ->content(fn ($record) => $record?->assigned_at?->format('d/m/Y H:i') ?? '-'),

                    Placeholder::make('status_display')
                        ->label('Current Status')
                        ->content(fn ($record) => Abnormality::getStatuses()[$record?->status] ?? '-'),
                ]),

            Section::make('Fix Details')
                ->columns(2)
                ->visible(fn ($record) => $record && in_array($record->status, [
                    Abnormality::STATUS_IN_PROGRESS, 
                    Abnormality::STATUS_FIXED, 
                    Abnormality::STATUS_VERIFIED, 
                    Abnormality::STATUS_CLOSED
                ]))
                ->components([
                    Textarea::make('fix_description')
                        ->label('Fix Description')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_IN_PROGRESS, Abnormality::STATUS_ASSIGNED])),

                    FileUpload::make('fix_photo')
                        ->label('Fix Evidence Photo')
                        ->image()
                        ->disk('public')
                        ->directory('abnormalities/fixes')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Abnormality::STATUS_IN_PROGRESS, Abnormality::STATUS_ASSIGNED])),

                    Placeholder::make('fixed_by_display')
                        ->label('Fixed By')
                        ->content(fn ($record) => $record?->fixer?->name ?? '-'),

                    Placeholder::make('fixed_at_display')
                        ->label('Fixed At')
                        ->content(fn ($record) => $record?->fixed_at?->format('d/m/Y H:i') ?? '-'),
                ]),

            Section::make('Verification')
                ->columns(2)
                ->visible(fn ($record) => $record && in_array($record->status, [
                    Abnormality::STATUS_VERIFIED, 
                    Abnormality::STATUS_CLOSED
                ]))
                ->components([
                    Placeholder::make('verified_by_display')
                        ->label('Verified By')
                        ->content(fn ($record) => $record?->verifier?->name ?? '-'),

                    Placeholder::make('verified_at_display')
                        ->label('Verified At')
                        ->content(fn ($record) => $record?->verified_at?->format('d/m/Y H:i') ?? '-'),

                    Placeholder::make('verification_notes_display')
                        ->label('Verification Notes')
                        ->content(fn ($record) => $record?->verification_notes ?? '-')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AbnormalitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbnormalities::route('/'),
            'create' => CreateAbnormality::route('/create'),
            'view' => ViewAbnormality::route('/{record}'),
            'edit' => EditAbnormality::route('/{record}/edit'),
        ];
    }

    /**
     * Check if user can verify abnormalities (managers only)
     */
    public static function userCanVerify(): bool
    {
        $user = Auth::user();
        return $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin', 'supervisor']);
    }

    /**
     * Get navigation badge (count of open abnormalities)
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Abnormality::whereIn('status', [
            Abnormality::STATUS_OPEN, 
            Abnormality::STATUS_ASSIGNED
        ])->count();
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

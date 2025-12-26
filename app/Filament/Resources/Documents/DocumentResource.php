<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Models\Area;
use App\Models\Document;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

class DocumentResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = Document::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'OPL/SOP Documents';

    protected static string | UnitEnum | null $navigationGroup = 'Performance KPIs';

    protected static ?int $navigationSort = 3;

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
            Section::make('Document Information')
                ->columns(2)
                ->components([
                    Placeholder::make('document_no_display')
                        ->label('Document No')
                        ->content(fn ($record) => $record?->document_no ?? 'Auto-generated')
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Document Type')
                        ->required()
                        ->options(Document::getTypes())
                        ->default(Document::TYPE_OPL)
                        ->disabled(fn ($record) => $record !== null),

                    Select::make('category')
                        ->label('Category')
                        ->required()
                        ->options(Document::getCategories())
                        ->default('general'),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Document title')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Document::STATUS_DRAFT])),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->placeholder('Brief description of the document')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Document::STATUS_DRAFT])),

                    Select::make('area_id')
                        ->label('Related Area')
                        ->options(Area::pluck('name', 'id'))
                        ->searchable()
                        ->preload(),

                    TagsInput::make('tags')
                        ->label('Tags')
                        ->placeholder('Add searchable tags')
                        ->separator(','),
                ]),

            Section::make('Document Content')
                ->components([
                    RichEditor::make('content')
                        ->label('Content')
                        ->required()
                        ->toolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'bold',
                            'bulletList',
                            'codeBlock',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'underline',
                            'undo',
                        ])
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('documents/content')
                        ->fileAttachmentsVisibility('public')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Document::STATUS_DRAFT])),

                    FileUpload::make('attachments')
                        ->label('Attachments')
                        ->multiple()
                        ->disk('public')
                        ->directory('documents/attachments')
                        ->visibility('public')
                        ->maxSize(10240)
                        ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->downloadable()
                        ->openable()
                        ->previewable()
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Document::STATUS_DRAFT])),
                ]),

            Section::make('Status & Approval')
                ->columns(2)
                ->visible(fn ($record) => $record !== null)
                ->components([
                    Placeholder::make('status_display')
                        ->label('Current Status')
                        ->content(fn ($record) => Document::getStatuses()[$record?->status] ?? '-'),

                    Placeholder::make('version_display')
                        ->label('Version')
                        ->content(fn ($record) => $record?->version ?? 1),

                    Placeholder::make('author_display')
                        ->label('Created By')
                        ->content(fn ($record) => $record?->author?->name ?? '-'),

                    Placeholder::make('created_at_display')
                        ->label('Created At')
                        ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                    Placeholder::make('reviewed_by_display')
                        ->label('Reviewed By')
                        ->content(fn ($record) => $record?->reviewer?->name ?? '-')
                        ->visible(fn ($record) => $record && $record->reviewed_by),

                    Placeholder::make('reviewed_at_display')
                        ->label('Reviewed At')
                        ->content(fn ($record) => $record?->reviewed_at?->format('d/m/Y H:i') ?? '-')
                        ->visible(fn ($record) => $record && $record->reviewed_at),

                    Placeholder::make('review_notes_display')
                        ->label('Review Notes')
                        ->content(fn ($record) => $record?->review_notes ?? '-')
                        ->visible(fn ($record) => $record && $record->review_notes)
                        ->columnSpanFull(),

                    Placeholder::make('published_at_display')
                        ->label('Published At')
                        ->content(fn ($record) => $record?->published_at?->format('d/m/Y H:i') ?? '-')
                        ->visible(fn ($record) => $record && $record->published_at),

                    Placeholder::make('acknowledgment_count')
                        ->label('Total Acknowledgments')
                        ->content(fn ($record) => $record?->getAcknowledgmentCount() ?? 0)
                        ->visible(fn ($record) => $record && $record->status === Document::STATUS_PUBLISHED),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    /**
     * Check if user can approve documents (managers only)
     */
    public static function userCanApprove(): bool
    {
        $user = Auth::user();
        return $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);
    }

    /**
     * Get navigation badge (count of pending review documents)
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Document::where('status', Document::STATUS_PENDING_REVIEW)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AiUsageService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class AiUsageMonitor extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCpuChip;
    protected static ?string $navigationLabel = 'AI Usage Monitor';
    protected static ?string $title = 'AI Usage Monitor';
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.ai-usage-monitor';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }

    public function getViewData(): array
    {
        $stats = $this->getOverallStats();
        $userStats = AiUsageService::getUserStats();

        return [
            'overallStats' => $stats,
            'currentUserStats' => $userStats,
        ];
    }

    protected function getOverallStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        // Today stats
        $todayStats = AiUsageLog::where('usage_date', $today)
            ->selectRaw('SUM(total_tokens) as tokens, SUM(estimated_cost) as cost, COUNT(*) as requests, COUNT(DISTINCT user_id) as users')
            ->first();

        // Month stats
        $monthStats = AiUsageLog::whereBetween('usage_date', [$monthStart, $monthEnd])
            ->selectRaw('SUM(total_tokens) as tokens, SUM(estimated_cost) as cost, COUNT(*) as requests, COUNT(DISTINCT user_id) as users')
            ->first();

        // Top users today
        $topUsers = AiUsageLog::where('usage_date', $today)
            ->selectRaw('user_id, SUM(total_tokens) as tokens')
            ->groupBy('user_id')
            ->orderByDesc('tokens')
            ->limit(5)
            ->with('user:id,name')
            ->get()
            ->map(fn($log) => [
                'name' => $log->user?->name ?? 'Unknown',
                'tokens' => number_format($log->tokens),
            ])
            ->toArray();

        return [
            'today' => [
                'tokens' => number_format($todayStats->tokens ?? 0),
                'cost' => '$' . number_format($todayStats->cost ?? 0, 4),
                'requests' => number_format($todayStats->requests ?? 0),
                'users' => $todayStats->users ?? 0,
            ],
            'month' => [
                'tokens' => number_format($monthStats->tokens ?? 0),
                'cost' => '$' . number_format($monthStats->cost ?? 0, 4),
                'requests' => number_format($monthStats->requests ?? 0),
                'users' => $monthStats->users ?? 0,
            ],
            'topUsers' => $topUsers,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query())
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('daily_ai_token_limit')
                    ->label('Daily Limit')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable(),

                TextColumn::make('today_tokens')
                    ->label('Used Today')
                    ->getStateUsing(function (User $record) {
                        return AiUsageLog::getTodayTokensForUser($record->id);
                    })
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->color(function ($state, User $record) {
                        $percentage = $record->daily_ai_token_limit > 0 
                            ? ($state / $record->daily_ai_token_limit) * 100 
                            : 0;
                        if ($percentage >= 90) return 'danger';
                        if ($percentage >= 70) return 'warning';
                        return 'success';
                    }),

                TextColumn::make('usage_percentage')
                    ->label('Usage %')
                    ->getStateUsing(function (User $record) {
                        $used = AiUsageLog::getTodayTokensForUser($record->id);
                        $limit = $record->daily_ai_token_limit;
                        return $limit > 0 ? round(($used / $limit) * 100, 1) : 0;
                    })
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 90) return 'danger';
                        if ($state >= 70) return 'warning';
                        return 'success';
                    }),

                IconColumn::make('ai_enabled')
                    ->label('AI Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('month_cost')
                    ->label('Month Cost')
                    ->getStateUsing(function (User $record) {
                        $monthStart = now()->startOfMonth()->toDateString();
                        $monthEnd = now()->endOfMonth()->toDateString();
                        return AiUsageLog::forUser($record->id)
                            ->whereBetween('usage_date', [$monthStart, $monthEnd])
                            ->sum('estimated_cost');
                    })
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 4))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('ai_enabled')
                    ->label('AI Status')
                    ->options([
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ]),
            ])
            ->actions([
                Action::make('setLimit')
                    ->label('Set Limit')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('limit')
                            ->label('Daily Token Limit')
                            ->numeric()
                            ->default(fn (User $record) => $record->daily_ai_token_limit)
                            ->required()
                            ->helperText('Recommended: 50,000 - 200,000 tokens per day'),
                    ])
                    ->action(function (User $record, array $data) {
                        AiUsageService::setUserLimit($record->id, (int) $data['limit']);
                        Notification::make()
                            ->title('Token limit updated')
                            ->success()
                            ->send();
                    }),

                Action::make('toggleAi')
                    ->label(fn (User $record) => $record->ai_enabled ? 'Disable AI' : 'Enable AI')
                    ->icon(fn (User $record) => $record->ai_enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->ai_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        AiUsageService::setUserAiEnabled($record->id, !$record->ai_enabled);
                        Notification::make()
                            ->title($record->ai_enabled ? 'AI disabled for user' : 'AI enabled for user')
                            ->success()
                            ->send();
                    }),

                Action::make('viewHistory')
                    ->label('View History')
                    ->icon('heroicon-o-clock')
                    ->modalHeading(fn (User $record) => "AI Usage History - {$record->name}")
                    ->modalContent(function (User $record) {
                        $logs = \App\Models\AiUsageLog::where('user_id', $record->id)
                            ->orderBy('created_at', 'desc')
                            ->limit(20)
                            ->get();
                        
                        return view('filament.pages.partials.ai-usage-history', [
                            'logs' => $logs,
                            'user' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([])
            ->defaultSort('name');
    }
}

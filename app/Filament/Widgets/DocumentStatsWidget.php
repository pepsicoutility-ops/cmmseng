<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentAcknowledgment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 12;

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Check user role
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin']);

        // Total published documents
        $publishedCount = Document::published()->count();

        // Pending review (for managers)
        $pendingReview = Document::where('status', Document::STATUS_PENDING_REVIEW)->count();

        // My acknowledgments this month
        $myAcksThisMonth = DocumentAcknowledgment::where('gpid', $user->gpid)
            ->whereYear('acknowledged_at', $currentYear)
            ->whereMonth('acknowledged_at', $currentMonth)
            ->count();

        // Monthly target: >= 2 documents read per month
        $monthlyTarget = 2;
        $ackProgress = $monthlyTarget > 0 ? round(($myAcksThisMonth / $monthlyTarget) * 100, 1) : 0;

        // Unread published documents
        $totalPublished = Document::published()->count();
        $myTotalAcks = DocumentAcknowledgment::where('gpid', $user->gpid)->count();
        $unreadCount = max(0, $totalPublished - $myTotalAcks);

        // Documents by type
        $typeBreakdown = Document::published()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        $oplCount = $typeBreakdown['opl'] ?? 0;
        $sopCount = $typeBreakdown['sop'] ?? 0;

        // Documents created this month (for authors)
        $createdThisMonth = Document::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->when(!$isManager, fn($q) => $q->where('created_by', $user->gpid))
            ->count();

        $stats = [
            Stat::make('My Reads This Month', $myAcksThisMonth . ' / ' . $monthlyTarget)
                ->description($ackProgress >= 100 ? '✅ Target Met' : '⚠️ Read More Documents')
                ->descriptionIcon($ackProgress >= 100 ? 'heroicon-o-check-circle' : 'heroicon-o-book-open')
                ->color($ackProgress >= 100 ? 'success' : 'warning'),

            Stat::make('Unread Documents', $unreadCount)
                ->description('Published documents not yet read')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($unreadCount > 5 ? 'warning' : 'info'),

            Stat::make('Document Library', "{$oplCount} OPL / {$sopCount} SOP")
                ->description("{$publishedCount} total published")
                ->descriptionIcon('heroicon-o-building-library')
                ->color('primary'),
        ];

        // Add pending review stat for managers
        if ($isManager) {
            $stats[] = Stat::make('Pending Review', $pendingReview)
                ->description('Documents awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingReview > 0 ? 'warning' : 'success');
        } else {
            $stats[] = Stat::make('My Documents', $createdThisMonth)
                ->description('Created this month')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color('info');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->role !== 'operator';
    }
}

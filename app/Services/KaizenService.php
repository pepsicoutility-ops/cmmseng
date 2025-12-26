<?php

namespace App\Services;

use App\Models\Kaizen;
use App\Models\User;
use App\Models\WoImprovement;
use App\Models\AreaOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KaizenService
{
    /**
     * Check Kaizen compliance for all users
     * Target: Minimum 4 Kaizens per person per year
     * 
     * @param int|null $year
     * @return array
     */
    public function checkKaizenCompliance(?int $year = null): array
    {
        $year = $year ?? now()->year;
        $target = 4; // Minimum 4 Kaizens per year

        // Get all users who should submit Kaizens
        $users = User::whereIn('role', ['technician', 'engineer', 'supervisor', 'asisten_manager'])
            ->get();

        $results = [];
        
        foreach ($users as $user) {
            $kaizenCount = Kaizen::where('submitted_by_gpid', $user->gpid)
                ->whereYear('created_at', $year)
                ->count();

            $totalScore = Kaizen::where('submitted_by_gpid', $user->gpid)
                ->whereYear('created_at', $year)
                ->whereIn('status', ['approved', 'implemented'])
                ->sum('score');

            $compliance = ($kaizenCount / $target) * 100;
            $status = $compliance >= 100 ? 'compliant' : 'below_target';

            $results[] = [
                'gpid' => $user->gpid,
                'name' => $user->name,
                'department' => $user->department,
                'kaizen_count' => $kaizenCount,
                'total_score' => $totalScore,
                'target' => $target,
                'compliance_percentage' => round($compliance, 2),
                'status' => $status,
            ];
        }

        // Sort by compliance percentage (ascending) to see who needs attention
        usort($results, fn($a, $b) => $a['compliance_percentage'] <=> $b['compliance_percentage']);

        return $results;
    }

    /**
     * Check WO Improvement compliance for all area owners
     * Target: Minimum 5 improvements per area per month
     * 
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function checkWoImprovementCompliance(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $target = 5; // Minimum 5 improvements per area per month

        // Get all active area owners
        $areaOwners = AreaOwner::where('is_active', true)
            ->with(['area', 'owner'])
            ->get();

        $results = [];

        foreach ($areaOwners as $areaOwner) {
            $improvementCount = WoImprovement::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereHas('workOrder', function($q) use ($areaOwner) {
                    $q->where('area_id', $areaOwner->area_id);
                })
                ->count();

            $timeSaved = WoImprovement::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereHas('workOrder', function($q) use ($areaOwner) {
                    $q->where('area_id', $areaOwner->area_id);
                })
                ->sum('time_saved_minutes') ?? 0;

            $costSaved = WoImprovement::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereHas('workOrder', function($q) use ($areaOwner) {
                    $q->where('area_id', $areaOwner->area_id);
                })
                ->sum('cost_saved') ?? 0;

            $compliance = ($improvementCount / $target) * 100;
            $status = $compliance >= 100 ? 'compliant' : 'below_target';

            $results[] = [
                'area_id' => $areaOwner->area_id,
                'area_name' => $areaOwner->area->name,
                'owner_gpid' => $areaOwner->owner_gpid,
                'owner_name' => $areaOwner->owner->name,
                'improvement_count' => $improvementCount,
                'time_saved_minutes' => $timeSaved,
                'cost_saved' => $costSaved,
                'target' => $target,
                'compliance_percentage' => round($compliance, 2),
                'status' => $status,
            ];
        }

        // Sort by compliance percentage (ascending)
        usort($results, fn($a, $b) => $a['compliance_percentage'] <=> $b['compliance_percentage']);

        return $results;
    }

    /**
     * Get users who are below Kaizen target
     * 
     * @param int|null $year
     * @return array
     */
    public function getUsersBelowKaizenTarget(?int $year = null): array
    {
        $compliance = $this->checkKaizenCompliance($year);
        
        return array_filter($compliance, function($item) {
            return $item['status'] === 'below_target';
        });
    }

    /**
     * Get area owners who are below WO improvement target
     * 
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function getAreaOwnersBelowImprovementTarget(?int $year = null, ?int $month = null): array
    {
        $compliance = $this->checkWoImprovementCompliance($year, $month);
        
        return array_filter($compliance, function($item) {
            return $item['status'] === 'below_target';
        });
    }

    /**
     * Get Kaizen statistics for dashboard
     * 
     * @param int|null $year
     * @return array
     */
    public function getKaizenStatistics(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $totalKaizens = Kaizen::whereYear('created_at', $year)->count();
        $totalScore = Kaizen::whereYear('created_at', $year)
            ->whereIn('status', ['approved', 'implemented'])
            ->sum('score');

        // Category breakdown
        $categoryBreakdown = Kaizen::whereYear('created_at', $year)
            ->select('category', DB::raw('count(*) as count'), DB::raw('sum(score) as total_score'))
            ->groupBy('category')
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                    'total_score' => $item->total_score,
                    'avg_score' => round($item->total_score / $item->count, 2),
                ];
            });

        // Status breakdown
        $statusBreakdown = Kaizen::whereYear('created_at', $year)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top contributors
        $topContributors = Kaizen::whereYear('created_at', $year)
            ->select('submitted_by_gpid', DB::raw('count(*) as kaizen_count'), DB::raw('sum(score) as total_score'))
            ->groupBy('submitted_by_gpid')
            ->orderByDesc('total_score')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $user = User::where('gpid', $item->submitted_by_gpid)->first();
                return [
                    'gpid' => $item->submitted_by_gpid,
                    'name' => $user ? $user->name : 'Unknown',
                    'kaizen_count' => $item->kaizen_count,
                    'total_score' => $item->total_score,
                ];
            });

        return [
            'total_kaizens' => $totalKaizens,
            'total_score' => $totalScore,
            'avg_score_per_kaizen' => $totalKaizens > 0 ? round($totalScore / $totalKaizens, 2) : 0,
            'category_breakdown' => $categoryBreakdown,
            'status_breakdown' => $statusBreakdown,
            'top_contributors' => $topContributors,
        ];
    }

    /**
     * Get WO Improvement statistics for dashboard
     * 
     * @param int|null $year
     * @return array
     */
    public function getWoImprovementStatistics(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $totalImprovements = WoImprovement::whereYear('created_at', $year)->count();
        $totalTimeSaved = WoImprovement::whereYear('created_at', $year)->sum('time_saved_minutes') ?? 0;
        $totalCostSaved = WoImprovement::whereYear('created_at', $year)->sum('cost_saved') ?? 0;
        $recurrencePrevented = WoImprovement::whereYear('created_at', $year)
            ->where('recurrence_prevented', true)
            ->count();

        // Type breakdown
        $typeBreakdown = WoImprovement::whereYear('created_at', $year)
            ->select('improvement_type', DB::raw('count(*) as count'))
            ->groupBy('improvement_type')
            ->pluck('count', 'improvement_type')
            ->toArray();

        // Top improvers
        $topImprovers = WoImprovement::whereYear('created_at', $year)
            ->select('improved_by_gpid', DB::raw('count(*) as improvement_count'), 
                    DB::raw('sum(time_saved_minutes) as total_time_saved'))
            ->groupBy('improved_by_gpid')
            ->orderByDesc('improvement_count')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $user = User::where('gpid', $item->improved_by_gpid)->first();
                return [
                    'gpid' => $item->improved_by_gpid,
                    'name' => $user ? $user->name : 'Unknown',
                    'improvement_count' => $item->improvement_count,
                    'total_time_saved' => $item->total_time_saved,
                ];
            });

        return [
            'total_improvements' => $totalImprovements,
            'total_time_saved_minutes' => $totalTimeSaved,
            'total_time_saved_hours' => round($totalTimeSaved / 60, 2),
            'total_cost_saved' => $totalCostSaved,
            'recurrence_prevented' => $recurrencePrevented,
            'type_breakdown' => $typeBreakdown,
            'top_improvers' => $topImprovers,
        ];
    }

    /**
     * Log alert for users below target
     * This can be called by scheduled command for automated notifications
     * 
     * @return void
     */
    public function sendComplianceAlerts(): void
    {
        // Kaizen compliance alerts
        $belowTargetUsers = $this->getUsersBelowKaizenTarget();
        foreach ($belowTargetUsers as $user) {
            Log::info('Kaizen Alert: User below target', [
                'gpid' => $user['gpid'],
                'name' => $user['name'],
                'kaizen_count' => $user['kaizen_count'],
                'target' => $user['target'],
                'compliance' => $user['compliance_percentage'] . '%',
            ]);
            
            // Here you can integrate with TelegramService or WhatsAppService
            // Example: $this->telegramService->sendMessage($user['gpid'], "...");
        }

        // WO Improvement compliance alerts
        $belowTargetAreas = $this->getAreaOwnersBelowImprovementTarget();
        foreach ($belowTargetAreas as $area) {
            Log::info('WO Improvement Alert: Area owner below target', [
                'area' => $area['area_name'],
                'owner' => $area['owner_name'],
                'improvement_count' => $area['improvement_count'],
                'target' => $area['target'],
                'compliance' => $area['compliance_percentage'] . '%',
            ]);
            
            // Here you can integrate with TelegramService or WhatsAppService
        }
    }
}

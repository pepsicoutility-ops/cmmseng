<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\SubAsset;
use App\Models\EquipmentTrouble;
use App\Models\PmExecution;
use App\Models\PmSchedule;
use App\Models\WorkOrder;
use App\Models\WoProcesse;
use App\Models\WoCost;
use App\Models\PmCost;
use App\Models\Part;
use App\Models\InventoryMovement;
use App\Models\RunningHour;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\AhuChecklist;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AIAnalyticsService
{
    /**
     * Analyze root cause of equipment troubles
     * 
     * @param array $params
     * @return array
     */
    public function analyzeRootCause(array $params): array
    {
        $equipmentId = $params['equipment_id'] ?? null;
        $analysisPeriod = $params['analysis_period'] ?? 90;
        $troubleThreshold = $params['trouble_threshold'] ?? 3;

        if (!$equipmentId) {
            return [
                'success' => false,
                'message' => 'Equipment ID is required for root cause analysis',
            ];
        }

        // Get equipment info (from SubAsset, not Asset)
        $equipment = SubAsset::find($equipmentId);
        if (!$equipment) {
            return [
                'success' => false,
                'message' => 'Equipment not found',
            ];
        }

        $startDate = Carbon::now()->subDays($analysisPeriod);
        $endDate = Carbon::now();
        
        // Get troubles in analysis period
        $troubles = EquipmentTrouble::where('equipment_id', $equipmentId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $troubleCount = $troubles->count();

        // Check if sufficient data
        if ($troubleCount < $troubleThreshold) {
            return [
                'success' => true,
                'insufficient_data' => true,
                'equipment_name' => $equipment->name,
                'trouble_count' => $troubleCount,
                'threshold' => $troubleThreshold,
                'message' => "Insufficient data for analysis. Only {$troubleCount} troubles found (minimum: {$troubleThreshold})",
            ];
        }

        // 1. TROUBLE FREQUENCY ANALYSIS
        $frequencyAnalysis = $this->analyzeTroubleFrequency($troubles, $analysisPeriod);

        // 2. TIMING PATTERN ANALYSIS
        $timingPatterns = $this->analyzeTimingPatterns($troubles);

        // 3. ISSUE TYPE PATTERN ANALYSIS
        $issuePatterns = $this->analyzeIssueTypes($troubles);

        // 4. CORRELATION ANALYSIS
        $correlationAnalysis = $this->analyzeCorrelations($equipmentId, $startDate, $endDate);

        // 5. ROOT CAUSE IDENTIFICATION
        $rootCauses = $this->identifyRootCauses($troubles, $correlationAnalysis, $timingPatterns, $issuePatterns);

        // 6. RECOMMENDATIONS
        $recommendations = $this->generateRecommendations($rootCauses, $correlationAnalysis, $equipment);

        // 7. IMPACT ESTIMATION
        $impactEstimate = $this->estimateImpact($troubleCount, $troubles, $correlationAnalysis);

        return [
            'success' => true,
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipment->name,
            'analysis_period' => $analysisPeriod,
            'analysis_period_text' => "{$analysisPeriod} days",
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'trouble_summary' => [
                'total_troubles' => $troubleCount,
                'average_per_week' => round($troubleCount / ($analysisPeriod / 7), 1),
                'average_per_month' => round($troubleCount / ($analysisPeriod / 30), 1),
                'trend' => $frequencyAnalysis['trend'],
                'trend_percentage' => $frequencyAnalysis['trend_percentage'],
            ],
            'timing_patterns' => $timingPatterns,
            'issue_type_patterns' => $issuePatterns,
            'correlation_analysis' => $correlationAnalysis,
            'root_causes' => $rootCauses,
            'recommendations' => $recommendations,
            'impact_estimate' => $impactEstimate,
        ];
    }

    /**
     * Analyze trouble frequency and trend
     */
    protected function analyzeTroubleFrequency($troubles, $analysisPeriod): array
    {
        $totalTroubles = $troubles->count();
        
        // Split into two halves to detect trend
        $midPoint = Carbon::now()->subDays($analysisPeriod / 2);
        $firstHalf = $troubles->where('created_at', '<', $midPoint)->count();
        $secondHalf = $troubles->where('created_at', '>=', $midPoint)->count();

        $trend = 'stable';
        $trendPercentage = 0;

        if ($firstHalf > 0) {
            $change = (($secondHalf - $firstHalf) / $firstHalf) * 100;
            $trendPercentage = round($change, 1);
            
            if ($change > 20) {
                $trend = 'increasing';
            } elseif ($change < -20) {
                $trend = 'decreasing';
            }
        } elseif ($secondHalf > 0) {
            $trend = 'increasing';
            $trendPercentage = 100;
        }

        return [
            'trend' => $trend,
            'trend_percentage' => $trendPercentage,
            'first_half_count' => $firstHalf,
            'second_half_count' => $secondHalf,
        ];
    }

    /**
     * Analyze timing patterns (shift, day, hour)
     */
    protected function analyzeTimingPatterns($troubles): array
    {
        $patterns = [
            'by_shift' => [],
            'by_day_of_week' => [],
            'by_hour' => [],
            'peak_time' => null,
        ];

        foreach ($troubles as $trouble) {
            $hour = (int) $trouble->created_at->format('H');
            $dayOfWeek = $trouble->created_at->format('l');
            
            // Determine shift based on hour
            if ($hour >= 6 && $hour < 14) {
                $shift = 'Shift 1 (06:00-14:00)';
            } elseif ($hour >= 14 && $hour < 22) {
                $shift = 'Shift 2 (14:00-22:00)';
            } else {
                $shift = 'Shift 3 (22:00-06:00)';
            }

            // Count by shift
            if (!isset($patterns['by_shift'][$shift])) {
                $patterns['by_shift'][$shift] = 0;
            }
            $patterns['by_shift'][$shift]++;

            // Count by day
            if (!isset($patterns['by_day_of_week'][$dayOfWeek])) {
                $patterns['by_day_of_week'][$dayOfWeek] = 0;
            }
            $patterns['by_day_of_week'][$dayOfWeek]++;

            // Count by hour
            if (!isset($patterns['by_hour'][$hour])) {
                $patterns['by_hour'][$hour] = 0;
            }
            $patterns['by_hour'][$hour]++;
        }

        // Calculate percentages
        $total = $troubles->count();
        foreach ($patterns['by_shift'] as $shift => $count) {
            $patterns['by_shift'][$shift] = [
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
            ];
        }

        foreach ($patterns['by_day_of_week'] as $day => $count) {
            $patterns['by_day_of_week'][$day] = [
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
            ];
        }

        // Find peak hour
        if (!empty($patterns['by_hour'])) {
            $maxHour = array_keys($patterns['by_hour'], max($patterns['by_hour']))[0];
            $patterns['peak_time'] = sprintf('%02d:00-%02d:00', $maxHour, $maxHour + 1);
        }

        return $patterns;
    }

    /**
     * Analyze issue types and categorize
     */
    protected function analyzeIssueTypes($troubles): array
    {
        $issueCategories = [];
        
        // Keywords for categorization
        $keywords = [
            'Refrigerant/Cooling' => ['refrigerant', 'cooling', 'freon', 'pressure', 'discharge', 'suction', 'evaporator', 'condenser'],
            'Motor/Electrical' => ['motor', 'amp', 'volt', 'electrical', 'power', 'current', 'winding'],
            'Bearing/Mechanical' => ['bearing', 'vibration', 'noise', 'mechanical', 'shaft', 'coupling'],
            'Control/Sensor' => ['control', 'sensor', 'plc', 'panel', 'switch', 'relay'],
            'Leak' => ['leak', 'bocor', 'leaking'],
            'Temperature' => ['temperature', 'temp', 'overheat', 'hot', 'cold'],
            'Other' => [],
        ];

        foreach ($troubles as $trouble) {
            $description = strtolower($trouble->issue_description ?? '');
            $actionTaken = strtolower($trouble->action_taken ?? '');
            $text = $description . ' ' . $actionTaken;
            
            $categorized = false;
            
            foreach ($keywords as $category => $words) {
                if ($category === 'Other') continue;
                
                foreach ($words as $keyword) {
                    if (strpos($text, $keyword) !== false) {
                        if (!isset($issueCategories[$category])) {
                            $issueCategories[$category] = [
                                'count' => 0,
                                'examples' => [],
                            ];
                        }
                        $issueCategories[$category]['count']++;
                        
                        if (count($issueCategories[$category]['examples']) < 3) {
                            $issueCategories[$category]['examples'][] = substr($trouble->issue_description, 0, 100);
                        }
                        
                        $categorized = true;
                        break 2;
                    }
                }
            }
            
            if (!$categorized) {
                if (!isset($issueCategories['Other'])) {
                    $issueCategories['Other'] = [
                        'count' => 0,
                        'examples' => [],
                    ];
                }
                $issueCategories['Other']['count']++;
            }
        }

        // Calculate percentages and sort by count
        $total = $troubles->count();
        foreach ($issueCategories as $category => &$data) {
            $data['percentage'] = round(($data['count'] / $total) * 100, 1);
        }

        // Sort by count descending
        uasort($issueCategories, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $issueCategories;
    }

    /**
     * Analyze correlations with PM, running hours, costs
     * Simplified version - focuses on trouble patterns
     */
    protected function analyzeCorrelations($equipmentId, $startDate, $endDate): array
    {
        $correlations = [];

        // 1. Work Orders correlation
        $relatedWo = WorkOrder::where(function($query) use ($equipmentId) {
                $query->where('asset_id', $equipmentId)
                      ->orWhere('sub_asset_id', $equipmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        $emergencyWo = WorkOrder::where(function($query) use ($equipmentId) {
                $query->where('asset_id', $equipmentId)
                      ->orWhere('sub_asset_id', $equipmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->where('priority', 'Emergency')
            ->count();

        $correlations['work_orders'] = [
            'total' => $relatedWo,
            'emergency' => $emergencyWo,
            'emergency_rate' => $relatedWo > 0 ? round(($emergencyWo / $relatedWo) * 100, 1) : 0,
        ];

        // 2. PM Schedule status (simplified)
        $activePmSchedules = PmSchedule::where(function($query) use ($equipmentId) {
                $query->where('asset_id', $equipmentId)
                      ->orWhere('sub_asset_id', $equipmentId);
            })
            ->where('is_active', true)
            ->count();

        $correlations['pm_schedules'] = [
            'active_schedules' => $activePmSchedules,
            'has_pm_program' => $activePmSchedules > 0,
        ];

        return $correlations;
    }

    /**
     * Identify root causes based on patterns and correlations
     */
    protected function identifyRootCauses($troubles, $correlations, $timingPatterns, $issuePatterns): array
    {
        $rootCauses = [];

        // Check PM program status
        if (isset($correlations['pm_schedules']) && !$correlations['pm_schedules']['has_pm_program']) {
            $rootCauses[] = [
                'severity' => 'primary',
                'cause' => 'No Active PM Program',
                'evidence' => [
                    "No active PM schedule found for this equipment",
                    "Equipment maintained only reactively (after failure)",
                ],
                'confidence' => 'high',
            ];
        }

        // Check recurring issue types
        if (!empty($issuePatterns)) {
            $topIssue = array_key_first($issuePatterns);
            $topIssueData = $issuePatterns[$topIssue];
            
            if ($topIssueData['percentage'] >= 30) {
                $rootCauses[] = [
                    'severity' => 'primary',
                    'cause' => "Recurring {$topIssue} Issues",
                    'evidence' => [
                        "{$topIssueData['count']} incidents ({$topIssueData['percentage']}% of all troubles)",
                        "Pattern indicates systematic problem, not random failures",
                    ],
                    'confidence' => 'high',
                ];
            }
        }

        // Check shift-specific issues
        if (!empty($timingPatterns['by_shift'])) {
            foreach ($timingPatterns['by_shift'] as $shift => $data) {
                if ($data['percentage'] >= 50) {
                    $rootCauses[] = [
                        'severity' => 'tertiary',
                        'cause' => "Shift-Specific Issues ({$shift})",
                        'evidence' => [
                            "{$data['count']} incidents ({$data['percentage']}%) occur during {$shift}",
                            "Possible operator error or shift-specific conditions",
                        ],
                        'confidence' => 'medium',
                    ];
                }
            }
        }

        // Check high emergency WO rate
        if (isset($correlations['work_orders']) && $correlations['work_orders']['emergency_rate'] > 30) {
            $rootCauses[] = [
                'severity' => 'secondary',
                'cause' => 'High Emergency Work Order Rate',
                'evidence' => [
                    "{$correlations['work_orders']['emergency_rate']}% of work orders are emergency",
                    "Indicates reactive maintenance culture",
                ],
                'confidence' => 'medium',
            ];
        }

        // If no specific root cause found
        if (empty($rootCauses)) {
            $rootCauses[] = [
                'severity' => 'unknown',
                'cause' => 'Multiple factors or insufficient data',
                'evidence' => [
                    'No clear pattern detected from current data',
                    'May require deeper technical investigation',
                ],
                'confidence' => 'low',
            ];
        }

        return $rootCauses;
    }

    /**
     * Generate actionable recommendations
     */
    protected function generateRecommendations($rootCauses, $correlations, $equipment): array
    {
        $recommendations = [];

        foreach ($rootCauses as $index => $cause) {
            $priority = $index + 1;
            
            if (strpos($cause['cause'], 'No Active PM') !== false) {
                $recommendations[] = [
                    'priority' => $priority,
                    'urgency' => 'urgent',
                    'action' => "Create PM Schedule for {$equipment->name}",
                    'details' => [
                        'Establish preventive maintenance program',
                        'Set regular inspection intervals',
                        'Define maintenance tasks checklist',
                    ],
                    'timeline' => 'This week',
                ];
            }
            
            if (strpos($cause['cause'], 'Recurring') !== false) {
                $issueType = str_replace(['Recurring ', ' Issues'], '', $cause['cause']);
                $recommendations[] = [
                    'priority' => $priority,
                    'urgency' => 'high',
                    'action' => "Address Systematic {$issueType} Problem",
                    'details' => [
                        'Conduct thorough inspection of affected components',
                        'Replace or repair root cause, not just symptoms',
                        'Update maintenance procedures to prevent recurrence',
                    ],
                    'timeline' => 'Within 3-5 days',
                ];
            }
            
            if (strpos($cause['cause'], 'Shift-Specific') !== false) {
                $recommendations[] = [
                    'priority' => $priority,
                    'urgency' => 'medium',
                    'action' => 'Shift Training & SOP Review',
                    'details' => [
                        'Conduct refresher training for affected shift',
                        'Review and update SOPs',
                        'Implement shift handover checklist',
                    ],
                    'timeline' => 'Within 2 weeks',
                ];
            }

            if (strpos($cause['cause'], 'Emergency Work Order') !== false) {
                $recommendations[] = [
                    'priority' => $priority,
                    'urgency' => 'medium',
                    'action' => 'Shift from Reactive to Preventive Maintenance',
                    'details' => [
                        'Increase PM frequency',
                        'Implement condition monitoring',
                        'Early detection of potential failures',
                    ],
                    'timeline' => 'Within 1 month',
                ];
            }
        }

        // Add general recommendation if specific ones are low
        if (count($recommendations) < 2) {
            $recommendations[] = [
                'priority' => count($recommendations) + 1,
                'urgency' => 'medium',
                'action' => 'Increase Monitoring & Documentation',
                'details' => [
                    'Track equipment parameters more frequently',
                    'Document all abnormal observations',
                    'Review trouble logs weekly for patterns',
                ],
                'timeline' => 'Ongoing',
            ];
        }

        return $recommendations;
    }

    /**
     * Estimate impact of addressing root causes
     */
    protected function estimateImpact($troubleCount, $troubles, $correlations): array
    {
        // Calculate average downtime
        $totalDowntime = $troubles->sum('downtime_minutes') ?? 0;
        $avgDowntime = $troubleCount > 0 ? round($totalDowntime / $troubleCount, 1) : 0;

        // Estimate reduction if root causes are addressed
        $estimatedReduction = 60; // Default 60% reduction
        
        if (isset($correlations['pm_compliance']) && $correlations['pm_compliance']['rate'] < 75) {
            $estimatedReduction = 70; // Higher reduction if PM compliance is poor
        }

        $estimatedTroubles = round($troubleCount * (1 - $estimatedReduction / 100));
        $troublesAvoided = $troubleCount - $estimatedTroubles;

        // Estimate cost savings (assume Rp 1M per major trouble)
        $avgCostPerTrouble = 1000000;
        $estimatedSavings = $troublesAvoided * $avgCostPerTrouble;

        return [
            'current_troubles_per_quarter' => $troubleCount,
            'estimated_troubles_after_fix' => $estimatedTroubles,
            'reduction_percentage' => $estimatedReduction,
            'troubles_avoided' => $troublesAvoided,
            'estimated_cost_savings' => $estimatedSavings,
            'estimated_cost_savings_formatted' => 'Rp ' . number_format($estimatedSavings, 0, ',', '.'),
            'average_downtime_minutes' => $avgDowntime,
            'total_downtime_reduction_hours' => round(($troublesAvoided * $avgDowntime) / 60, 1),
        ];
    }

    /**
     * Analyze cost optimization opportunities
     * 
     * @param array $params
     * @return array
     */
    public function analyzeCostOptimization(array $params): array
    {
        $period = $params['period'] ?? 90; // days
        $costThreshold = $params['cost_threshold'] ?? 100000; // Rp 100K
        $includeOpportunities = $params['include_opportunities'] ?? true;

        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // 1. SPENDING SUMMARY
        $spendingSummary = $this->getSpendingSummary($startDate, $endDate);

        // 2. COST DRIVERS (top contributors)
        $costDrivers = $this->getCostDrivers($startDate, $endDate, $costThreshold);

        // 3. OPTIMIZATION OPPORTUNITIES
        $opportunities = [];
        if ($includeOpportunities) {
            $opportunities = $this->identifyCostOpportunities($startDate, $endDate, $spendingSummary, $costDrivers);
        }

        // 4. PRIORITY RANKING
        $priorityRanking = $this->rankOpportunities($opportunities);

        // 5. IMPLEMENTATION PLAN
        $implementationPlan = $this->generateImplementationPlan($priorityRanking);

        return [
            'success' => true,
            'analysis_period' => $period,
            'analysis_period_text' => "{$period} days",
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'spending_summary' => $spendingSummary,
            'cost_drivers' => $costDrivers,
            'opportunities' => $opportunities,
            'priority_ranking' => $priorityRanking,
            'implementation_plan' => $implementationPlan,
            'total_potential_savings' => array_sum(array_column($opportunities, 'potential_savings')),
            'total_potential_savings_formatted' => 'Rp ' . number_format(array_sum(array_column($opportunities, 'potential_savings')), 0, ',', '.'),
        ];
    }

    /**
     * Get spending summary by category
     */
    protected function getSpendingSummary($startDate, $endDate): array
    {
        // WO Costs
        $woCosts = WoCost::whereBetween('created_at', [$startDate, $endDate])->get();
        $totalWoCost = $woCosts->sum(function($cost) {
            return ($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0);
        });
        $woLabourCost = $woCosts->sum('labour_cost') ?? 0;
        $woPartsCost = $woCosts->sum('parts_cost') ?? 0;

        // PM Costs
        $pmCosts = PmCost::whereBetween('created_at', [$startDate, $endDate])->get();
        $totalPmCost = $pmCosts->sum(function($cost) {
            return ($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0);
        });
        $pmLabourCost = $pmCosts->sum('labour_cost') ?? 0;
        $pmPartsCost = $pmCosts->sum('parts_cost') ?? 0;

        // Total maintenance cost (WO + PM only, skip inventory for now)
        $totalCost = $totalWoCost + $totalPmCost;
        $totalLabour = $woLabourCost + $pmLabourCost;
        $totalParts = $woPartsCost + $pmPartsCost;

        return [
            'total_cost' => $totalCost,
            'total_cost_formatted' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'breakdown' => [
                'wo_costs' => [
                    'total' => $totalWoCost,
                    'total_formatted' => 'Rp ' . number_format($totalWoCost, 0, ',', '.'),
                    'percentage' => $totalCost > 0 ? round(($totalWoCost / $totalCost) * 100, 1) : 0,
                    'labour' => $woLabourCost,
                    'parts' => $woPartsCost,
                ],
                'pm_costs' => [
                    'total' => $totalPmCost,
                    'total_formatted' => 'Rp ' . number_format($totalPmCost, 0, ',', '.'),
                    'percentage' => $totalCost > 0 ? round(($totalPmCost / $totalCost) * 100, 1) : 0,
                    'labour' => $pmLabourCost,
                    'parts' => $pmPartsCost,
                ],
            ],
            'labour_vs_parts' => [
                'total_labour' => $totalLabour,
                'total_parts' => $totalParts,
                'labour_percentage' => ($totalLabour + $totalParts) > 0 ? round(($totalLabour / ($totalLabour + $totalParts)) * 100, 1) : 0,
                'parts_percentage' => ($totalLabour + $totalParts) > 0 ? round(($totalParts / ($totalLabour + $totalParts)) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Identify top cost drivers
     */
    protected function getCostDrivers($startDate, $endDate, $costThreshold): array
    {
        $drivers = [];

        // 1. Emergency WO costs
        $emergencyWoCosts = WorkOrder::where('priority', 'Emergency')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('cost')
            ->get();

        $emergencyTotal = $emergencyWoCosts->sum(function($wo) {
            $cost = $wo->cost;
            return $cost ? (($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0)) : 0;
        });

        if ($emergencyTotal > $costThreshold) {
            $drivers['emergency_wo'] = [
                'category' => 'Emergency Work Orders',
                'total_cost' => $emergencyTotal,
                'total_cost_formatted' => 'Rp ' . number_format($emergencyTotal, 0, ',', '.'),
                'count' => $emergencyWoCosts->count(),
                'average_cost' => $emergencyWoCosts->count() > 0 ? round($emergencyTotal / $emergencyWoCosts->count(), 0) : 0,
                'severity' => 'high',
            ];
        }

        // 2. Overtime labor
        $totalLabour = WoCost::whereBetween('created_at', [$startDate, $endDate])
            ->sum('labour_cost') ?? 0;
        $totalLabour += PmCost::whereBetween('created_at', [$startDate, $endDate])
            ->sum('labour_cost') ?? 0;

        // Assume 20% is overtime (rough estimate)
        $estimatedOvertime = $totalLabour * 0.20;

        if ($estimatedOvertime > $costThreshold) {
            $drivers['overtime'] = [
                'category' => 'Overtime Labor',
                'total_cost' => $estimatedOvertime,
                'total_cost_formatted' => 'Rp ' . number_format($estimatedOvertime, 0, ',', '.'),
                'percentage_of_labour' => 20,
                'severity' => 'medium',
            ];
        }

        // 3. Frequent small parts orders (expedited shipping)
        $smallOrders = InventoryMovement::whereBetween('created_at', [$startDate, $endDate])
            ->where('movement_type', 'in')
            ->where('quantity', '<', 5)
            ->count();

        if ($smallOrders > 10) {
            $estimatedExpeditedCost = $smallOrders * 50000; // Assume Rp 50K per expedited order
            $drivers['expedited_parts'] = [
                'category' => 'Expedited Parts Orders',
                'total_cost' => $estimatedExpeditedCost,
                'total_cost_formatted' => 'Rp ' . number_format($estimatedExpeditedCost, 0, ',', '.'),
                'count' => $smallOrders,
                'severity' => 'medium',
            ];
        }

        // 4. Repeated repairs on same equipment
        $repeatedRepairs = WorkOrder::whereBetween('created_at', [$startDate, $endDate])
            ->select('sub_asset_id', DB::raw('count(*) as repair_count'))
            ->whereNotNull('sub_asset_id')
            ->groupBy('sub_asset_id')
            ->having('repair_count', '>', 3)
            ->with('subAsset')
            ->get();

        if ($repeatedRepairs->count() > 0) {
            $repeatedCost = 0;
            foreach ($repeatedRepairs as $repair) {
                $equipment = WorkOrder::where('sub_asset_id', $repair->sub_asset_id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->with('cost')
                    ->get();
                
                $repeatedCost += $equipment->sum(function($wo) {
                    $cost = $wo->cost;
                    return $cost ? (($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0)) : 0;
                });
            }

            $drivers['repeated_repairs'] = [
                'category' => 'Repeated Repairs (Same Equipment)',
                'total_cost' => $repeatedCost,
                'total_cost_formatted' => 'Rp ' . number_format($repeatedCost, 0, ',', '.'),
                'equipment_count' => $repeatedRepairs->count(),
                'severity' => 'high',
            ];
        }

        return $drivers;
    }

    /**
     * Identify cost optimization opportunities
     */
    protected function identifyCostOpportunities($startDate, $endDate, $spendingSummary, $costDrivers): array
    {
        $opportunities = [];

        // Opportunity 1: Reduce Emergency WO through better PM
        if (isset($costDrivers['emergency_wo'])) {
            $currentEmergencyCost = $costDrivers['emergency_wo']['total_cost'];
            $potentialReduction = $currentEmergencyCost * 0.50; // 50% reduction potential

            $opportunities[] = [
                'id' => 'reduce_emergency_wo',
                'title' => 'Reduce Emergency Work Orders',
                'description' => 'Improve PM compliance and predictive maintenance to reduce emergency breakdowns',
                'current_cost' => $currentEmergencyCost,
                'potential_savings' => $potentialReduction,
                'potential_savings_formatted' => 'Rp ' . number_format($potentialReduction, 0, ',', '.'),
                'actions' => [
                    'Increase PM compliance from current to 95%+',
                    'Implement condition monitoring for critical equipment',
                    'Schedule PM based on running hours, not just time',
                ],
                'timeline' => '2-3 months',
                'confidence' => 'high',
                'difficulty' => 'medium',
            ];
        }

        // Opportunity 2: Optimize Inventory Management
        $lowStockParts = Part::where('current_stock', '<=', DB::raw('min_stock'))
            ->count();

        if ($lowStockParts > 10) {
            $potentialSavings = $lowStockParts * 100000; // Assume Rp 100K savings per part

            $opportunities[] = [
                'id' => 'optimize_inventory',
                'title' => 'Optimize Spare Parts Inventory',
                'description' => 'Implement reorder points and reduce emergency expedited orders',
                'current_cost' => isset($costDrivers['expedited_parts']) ? $costDrivers['expedited_parts']['total_cost'] : 0,
                'potential_savings' => $potentialSavings * 0.30,
                'potential_savings_formatted' => 'Rp ' . number_format($potentialSavings * 0.30, 0, ',', '.'),
                'actions' => [
                    "Set reorder points for {$lowStockParts} critical parts",
                    'Negotiate supplier contracts for better pricing',
                    'Implement automated low-stock alerts',
                ],
                'timeline' => '1 month',
                'confidence' => 'high',
                'difficulty' => 'low',
            ];
        }

        // Opportunity 3: Reduce Overtime through Better Planning
        if (isset($costDrivers['overtime'])) {
            $potentialSavings = $costDrivers['overtime']['total_cost'] * 0.40; // 40% reduction

            $opportunities[] = [
                'id' => 'reduce_overtime',
                'title' => 'Optimize Labor Planning',
                'description' => 'Better scheduling and workload distribution to reduce overtime',
                'current_cost' => $costDrivers['overtime']['total_cost'],
                'potential_savings' => $potentialSavings,
                'potential_savings_formatted' => 'Rp ' . number_format($potentialSavings, 0, ',', '.'),
                'actions' => [
                    'Shift PM activities to regular hours',
                    'Balance workload across shifts',
                    'Cross-train technicians for flexibility',
                ],
                'timeline' => '1-2 months',
                'confidence' => 'medium',
                'difficulty' => 'medium',
            ];
        }

        // Opportunity 4: Fix Repeated Repairs
        if (isset($costDrivers['repeated_repairs'])) {
            $potentialSavings = $costDrivers['repeated_repairs']['total_cost'] * 0.60; // 60% reduction

            $opportunities[] = [
                'id' => 'fix_repeated_repairs',
                'title' => 'Address Root Cause of Repeated Repairs',
                'description' => 'Fix underlying problems instead of temporary patches',
                'current_cost' => $costDrivers['repeated_repairs']['total_cost'],
                'potential_savings' => $potentialSavings,
                'potential_savings_formatted' => 'Rp ' . number_format($potentialSavings, 0, ',', '.'),
                'actions' => [
                    'Conduct root cause analysis on frequent failures',
                    'Invest in quality repairs vs quick fixes',
                    'Update equipment specifications if needed',
                ],
                'timeline' => '2-4 months',
                'confidence' => 'high',
                'difficulty' => 'high',
            ];
        }

        return $opportunities;
    }

    /**
     * Rank opportunities by ROI
     */
    protected function rankOpportunities($opportunities): array
    {
        if (empty($opportunities)) {
            return [];
        }

        // Calculate ROI score for each opportunity
        $ranked = array_map(function($opp) {
            $confidenceScore = match($opp['confidence']) {
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 1,
            };

            $difficultyScore = match($opp['difficulty']) {
                'low' => 3,
                'medium' => 2,
                'high' => 1,
                default => 1,
            };

            // ROI Score = Savings * Confidence / Difficulty
            $roi_score = ($opp['potential_savings'] * $confidenceScore) / max($difficultyScore, 1);

            $opp['roi_score'] = $roi_score;
            $opp['priority_stars'] = $confidenceScore + $difficultyScore; // 2-6 stars

            return $opp;
        }, $opportunities);

        // Sort by ROI score descending
        usort($ranked, function($a, $b) {
            return $b['roi_score'] <=> $a['roi_score'];
        });

        return $ranked;
    }

    /**
     * Generate phased implementation plan
     */
    protected function generateImplementationPlan($rankedOpportunities): array
    {
        if (empty($rankedOpportunities)) {
            return [
                'phases' => [],
                'total_duration' => '0 months',
            ];
        }

        $phases = [];
        $currentPhase = 1;

        foreach ($rankedOpportunities as $index => $opp) {
            $phase = match($opp['timeline']) {
                '1 month', '1-2 months' => 'Phase 1 (Month 1-2)',
                '2-3 months', '2-4 months' => 'Phase 2 (Month 2-4)',
                default => 'Phase 3 (Month 4+)',
            };

            if (!isset($phases[$phase])) {
                $phases[$phase] = [
                    'phase_name' => $phase,
                    'opportunities' => [],
                    'total_savings' => 0,
                ];
            }

            $phases[$phase]['opportunities'][] = [
                'title' => $opp['title'],
                'savings' => $opp['potential_savings_formatted'],
                'confidence' => $opp['confidence'],
            ];

            $phases[$phase]['total_savings'] += $opp['potential_savings'];
        }

        // Format total savings for each phase
        foreach ($phases as &$phase) {
            $phase['total_savings_formatted'] = 'Rp ' . number_format($phase['total_savings'], 0, ',', '.');
        }

        return [
            'phases' => array_values($phases),
            'total_duration' => count($phases) > 0 ? (count($phases) * 2) . ' months' : '0 months',
        ];
    }

    /**
     * Detect anomalies in checklist data
     * 
     * @param array $params
     * @return array
     */
    public function detectAnomalies(array $params): array
    {
        $equipmentType = $params['equipment_type'] ?? null; // compressor1, compressor2, chiller1, chiller2, ahu
        $sensitivity = $params['sensitivity'] ?? 'medium'; // low, medium, high
        $lookbackDays = $params['lookback_days'] ?? 90;
        $recentDays = $params['recent_days'] ?? 7;

        // Sensitivity thresholds (z-score)
        $thresholds = [
            'low' => 3.0,      // Only extreme outliers
            'medium' => 2.0,   // Moderate outliers
            'high' => 1.5,     // Sensitive detection
        ];
        $zThreshold = $thresholds[$sensitivity] ?? 2.0;

        $baselineStart = Carbon::now()->subDays($lookbackDays);
        $recentStart = Carbon::now()->subDays($recentDays);
        $now = Carbon::now();

        $anomalies = [];

        // Analyze each equipment type
        $equipmentTypes = $equipmentType ? [$equipmentType] : ['compressor1', 'compressor2', 'chiller1', 'chiller2', 'ahu'];

        foreach ($equipmentTypes as $type) {
            $typeAnomalies = $this->analyzeEquipmentType($type, $baselineStart, $recentStart, $now, $zThreshold);
            $anomalies = array_merge($anomalies, $typeAnomalies);
        }

        // Sort by severity (critical > warning > info)
        usort($anomalies, function($a, $b) {
            $severityOrder = ['critical' => 1, 'warning' => 2, 'info' => 3];
            return ($severityOrder[$a['severity']] ?? 4) <=> ($severityOrder[$b['severity']] ?? 4);
        });

        // Summary statistics
        $summary = [
            'critical' => count(array_filter($anomalies, fn($a) => $a['severity'] === 'critical')),
            'warning' => count(array_filter($anomalies, fn($a) => $a['severity'] === 'warning')),
            'info' => count(array_filter($anomalies, fn($a) => $a['severity'] === 'info')),
            'total' => count($anomalies),
        ];

        return [
            'success' => true,
            'analysis_period' => [
                'baseline_days' => $lookbackDays,
                'recent_days' => $recentDays,
                'baseline_start' => $baselineStart->format('Y-m-d'),
                'recent_start' => $recentStart->format('Y-m-d'),
                'current_date' => $now->format('Y-m-d'),
            ],
            'sensitivity' => $sensitivity,
            'threshold_zscore' => $zThreshold,
            'anomalies' => $anomalies,
            'summary' => $summary,
        ];
    }

    /**
     * Analyze specific equipment type for anomalies
     */
    protected function analyzeEquipmentType($type, $baselineStart, $recentStart, $now, $zThreshold): array
    {
        $model = $this->getChecklistModel($type);
        if (!$model) {
            return [];
        }

        // Get parameter definitions for this equipment type
        $parameters = $this->getParameterDefinitions($type);
        
        $anomalies = [];

        foreach ($parameters as $param => $config) {
            // Get baseline data (90 days)
            $baselineData = $model::whereBetween('created_at', [$baselineStart, $recentStart])
                ->whereNotNull($param)
                ->pluck($param)
                ->filter(fn($val) => is_numeric($val) && $val > 0)
                ->toArray();

            // Get recent data (7 days)
            $recentData = $model::whereBetween('created_at', [$recentStart, $now])
                ->whereNotNull($param)
                ->orderBy('created_at', 'desc')
                ->get(['created_at', $param])
                ->filter(fn($row) => is_numeric($row->$param) && $row->$param > 0);

            if (count($baselineData) < 10 || $recentData->count() === 0) {
                continue; // Insufficient data
            }

            // Calculate baseline statistics
            $mean = array_sum($baselineData) / count($baselineData);
            $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $baselineData)) / count($baselineData);
            $stdDev = sqrt($variance);

            // Analyze recent readings
            $latestValue = $recentData->first()->$param;
            $zScore = $stdDev > 0 ? abs(($latestValue - $mean) / $stdDev) : 0;
            
            // Trend analysis (last 7 readings)
            $trendData = $recentData->take(7)->pluck($param)->toArray();
            $trend = $this->calculateTrend($trendData);

            // Detect anomaly types
            $anomaly = $this->detectAnomalyType(
                $type,
                $param,
                $config,
                $latestValue,
                $mean,
                $stdDev,
                $zScore,
                $zThreshold,
                $trend,
                $recentData
            );

            if ($anomaly) {
                $anomalies[] = $anomaly;
            }
        }

        return $anomalies;
    }

    /**
     * Detect specific anomaly type
     */
    protected function detectAnomalyType($equipmentType, $param, $config, $latestValue, $mean, $stdDev, $zScore, $zThreshold, $trend, $recentData): ?array
    {
        $deviation = $mean > 0 ? (($latestValue - $mean) / $mean) * 100 : 0;
        $normalRange = [
            'min' => round($mean - 2 * $stdDev, 2),
            'max' => round($mean + 2 * $stdDev, 2),
        ];

        // Check if value is outside acceptable range
        if ($zScore < $zThreshold) {
            return null; // No anomaly
        }

        // Determine severity
        $severity = 'info';
        $status = 'Slight deviation';
        
        if ($zScore >= 3.0) {
            $severity = 'critical';
            $status = 'CRITICAL DEVIATION';
        } elseif ($zScore >= 2.5) {
            $severity = 'warning';
            $status = 'Significant deviation';
        } elseif ($zScore >= 2.0) {
            $severity = 'warning';
            $status = 'Moderate deviation';
        }

        // Enhanced severity based on trend
        if ($trend['direction'] !== 'stable' && abs($trend['percentage']) > 15) {
            $status = 'TRENDING ABNORMAL';
            if ($severity === 'info') {
                $severity = 'warning';
            }
        }

        // Calculate confidence (based on data quality)
        $dataPoints = $recentData->count();
        $confidence = min(95, 60 + ($dataPoints * 2)); // More data = higher confidence

        // Risk assessment
        $risk = $this->assessRisk($severity, $trend, $deviation, $config);

        // Recommendations
        $recommendations = $this->generateAnomalyRecommendations($severity, $param, $trend, $config);

        return [
            'equipment_type' => ucfirst($equipmentType),
            'parameter' => $config['label'] ?? $param,
            'parameter_key' => $param,
            'status' => $status,
            'severity' => $severity,
            'current_value' => round($latestValue, 2),
            'normal_range' => $normalRange,
            'mean' => round($mean, 2),
            'std_dev' => round($stdDev, 2),
            'z_score' => round($zScore, 2),
            'deviation_percent' => round($deviation, 1),
            'trend' => $trend,
            'unit' => $config['unit'] ?? '',
            'confidence' => $confidence,
            'risk_assessment' => $risk,
            'recommendations' => $recommendations,
            'recent_readings' => $recentData->take(7)->map(function($row) use ($param) {
                return [
                    'date' => Carbon::parse($row->created_at)->format('M d'),
                    'value' => round($row->$param, 2),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Calculate trend from array of values
     */
    protected function calculateTrend(array $values): array
    {
        if (count($values) < 2) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $first = $values[count($values) - 1]; // Oldest
        $last = $values[0]; // Newest
        
        $change = $last - $first;
        $percentage = $first > 0 ? ($change / $first) * 100 : 0;

        $direction = 'stable';
        if (abs($percentage) > 5) {
            $direction = $percentage > 0 ? 'increasing' : 'decreasing';
        }

        return [
            'direction' => $direction,
            'change' => round($change, 2),
            'percentage' => round($percentage, 1),
            'first_value' => round($first, 2),
            'last_value' => round($last, 2),
        ];
    }

    /**
     * Assess risk level
     */
    protected function assessRisk($severity, $trend, $deviation, $config): array
    {
        $breakdownProb = 0;
        $riskLevel = 'low';

        if ($severity === 'critical') {
            $breakdownProb = 75;
            $riskLevel = 'high';
        } elseif ($severity === 'warning') {
            $breakdownProb = 40;
            $riskLevel = 'medium';
        } else {
            $breakdownProb = 15;
            $riskLevel = 'low';
        }

        // Increase risk if trending worse
        if ($trend['direction'] === 'increasing' && abs($trend['percentage']) > 20) {
            $breakdownProb = min(95, $breakdownProb + 20);
        }

        return [
            'level' => $riskLevel,
            'breakdown_probability' => $breakdownProb,
            'timeframe' => $severity === 'critical' ? '2-3 days' : ($severity === 'warning' ? '5-7 days' : '2-3 weeks'),
            'estimated_downtime' => $severity === 'critical' ? '8-12 hours' : '4-6 hours',
            'estimated_cost' => $severity === 'critical' ? 'Rp 15-25M' : 'Rp 5-10M',
        ];
    }

    /**
     * Generate recommendations based on anomaly
     */
    protected function generateAnomalyRecommendations($severity, $param, $trend, $config): array
    {
        $recommendations = [];

        if ($severity === 'critical') {
            $recommendations[] = '🚨 URGENT - Immediate inspection required';
            $recommendations[] = 'Stop equipment if safe to do so';
            $recommendations[] = 'Call maintenance team immediately';
        }

        // Parameter-specific recommendations
        if (str_contains($param, 'temperature')) {
            $recommendations[] = 'Check cooling system and fans';
            $recommendations[] = 'Verify temperature sensor calibration';
            if ($trend['direction'] === 'increasing') {
                $recommendations[] = 'Inspect for blockages or reduced airflow';
            }
        } elseif (str_contains($param, 'pressure')) {
            $recommendations[] = 'Check for leaks in the system';
            $recommendations[] = 'Verify pressure gauge accuracy';
            $recommendations[] = 'Inspect valves and connections';
        } elseif (str_contains($param, 'oil')) {
            $recommendations[] = 'Check oil level and top up if needed';
            $recommendations[] = 'Sample oil for contamination analysis';
            $recommendations[] = 'Inspect bearing condition';
        } elseif (str_contains($param, 'vibration') || str_contains($param, 'bearing')) {
            $recommendations[] = 'Conduct vibration analysis';
            $recommendations[] = 'Check bearing condition';
            $recommendations[] = 'Inspect for misalignment';
        }

        if ($severity === 'warning') {
            $recommendations[] = 'Schedule inspection within 24 hours';
            $recommendations[] = 'Increase monitoring frequency';
        } else {
            $recommendations[] = 'Continue monitoring';
            $recommendations[] = 'Schedule preventive check soon';
        }

        return $recommendations;
    }

    /**
     * Get checklist model for equipment type
     */
    protected function getChecklistModel($type)
    {
        return match($type) {
            'compressor1' => new Compressor1Checklist(),
            'compressor2' => new Compressor2Checklist(),
            'chiller1' => new Chiller1Checklist(),
            'chiller2' => new Chiller2Checklist(),
            'ahu' => new AhuChecklist(),
            default => null,
        };
    }

    /**
     * Get parameter definitions for equipment type
     */
    protected function getParameterDefinitions($type): array
    {
        $compressorParams = [
            'bearing_oil_temperature' => ['label' => 'Bearing Oil Temperature', 'unit' => '°C', 'critical' => true],
            'bearing_oil_pressure' => ['label' => 'Bearing Oil Pressure', 'unit' => 'bar', 'critical' => true],
            'discharge_pressure' => ['label' => 'Discharge Pressure', 'unit' => 'bar', 'critical' => true],
            'discharge_temperature' => ['label' => 'Discharge Temperature', 'unit' => '°C', 'critical' => true],
            'cws_temperature' => ['label' => 'CWS Temperature', 'unit' => '°C', 'critical' => false],
            'cwr_temperature' => ['label' => 'CWR Temperature', 'unit' => '°C', 'critical' => false],
            'cws_pressure' => ['label' => 'CWS Pressure', 'unit' => 'bar', 'critical' => false],
            'cwr_pressure' => ['label' => 'CWR Pressure', 'unit' => 'bar', 'critical' => false],
            'refrigerant_pressure' => ['label' => 'Refrigerant Pressure', 'unit' => 'bar', 'critical' => true],
            'dew_point' => ['label' => 'Dew Point', 'unit' => '°C', 'critical' => false],
        ];

        $chillerParams = [
            'sat_evap_t' => ['label' => 'Sat Evap Temperature', 'unit' => '°C', 'critical' => true],
            'sat_dis_t' => ['label' => 'Sat Discharge Temperature', 'unit' => '°C', 'critical' => true],
            'dis_superheat' => ['label' => 'Discharge Superheat', 'unit' => '°C', 'critical' => true],
            'motor_amps' => ['label' => 'Motor Amps', 'unit' => 'A', 'critical' => true],
            'motor_volts' => ['label' => 'Motor Volts', 'unit' => 'V', 'critical' => false],
            'motor_t' => ['label' => 'Motor Temperature', 'unit' => '°C', 'critical' => true],
            'heatsink_t' => ['label' => 'Heatsink Temperature', 'unit' => '°C', 'critical' => true],
            'evap_p' => ['label' => 'Evaporator Pressure', 'unit' => 'bar', 'critical' => true],
            'conds_p' => ['label' => 'Condenser Pressure', 'unit' => 'bar', 'critical' => true],
            'oil_p' => ['label' => 'Oil Pressure', 'unit' => 'bar', 'critical' => true],
            'evap_t_diff' => ['label' => 'Evap Temperature Diff', 'unit' => '°C', 'critical' => false],
            'conds_t_diff' => ['label' => 'Condenser Temperature Diff', 'unit' => '°C', 'critical' => false],
            'run_hours' => ['label' => 'Running Hours', 'unit' => 'hrs', 'critical' => false],
        ];

        return match($type) {
            'compressor1', 'compressor2' => $compressorParams,
            'chiller1', 'chiller2' => $chillerParams,
            'ahu' => [],  // AHU has string fields, skip
            default => [],
        };
    }

    // ========================================================================
    // PHASE 2: PREDICTIVE MAINTENANCE & PERFORMANCE MONITORING
    // ========================================================================

    /**
     * Predict maintenance needs based on historical patterns
     * 
     * @param array $params
     * @return array
     */
    public function predictMaintenanceNeeds(array $params): array
    {
        $equipmentId = $params['equipment_id'] ?? null;
        $predictionDays = $params['prediction_days'] ?? 30;
        $includeAllEquipment = $params['include_all_equipment'] ?? false;

        $startDate = Carbon::now()->subDays(180); // 6 months history
        $endDate = Carbon::now();

        $predictions = [];
        $summary = [
            'high_risk' => 0,
            'medium_risk' => 0,
            'low_risk' => 0,
            'total_equipment' => 0,
        ];

        // Get equipment list
        if ($equipmentId) {
            $equipment = SubAsset::find($equipmentId);
            if (!$equipment) {
                return ['success' => false, 'message' => 'Equipment not found'];
            }
            $equipmentList = collect([$equipment]);
        } else {
            $equipmentList = SubAsset::where('is_active', 1)->get();
        }

        foreach ($equipmentList as $equipment) {
            $prediction = $this->generateEquipmentPrediction($equipment, $startDate, $endDate, $predictionDays);
            
            if ($prediction['risk_level'] === 'high') {
                $summary['high_risk']++;
            } elseif ($prediction['risk_level'] === 'medium') {
                $summary['medium_risk']++;
            } else {
                $summary['low_risk']++;
            }
            $summary['total_equipment']++;

            $predictions[] = $prediction;
        }

        // Sort by risk score descending
        usort($predictions, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        // Group actions by urgency
        $urgentActions = array_filter($predictions, fn($p) => $p['risk_level'] === 'high');
        $plannedActions = array_filter($predictions, fn($p) => $p['risk_level'] === 'medium');
        $monitorOnly = array_filter($predictions, fn($p) => $p['risk_level'] === 'low');

        return [
            'success' => true,
            'prediction_period' => "{$predictionDays} days",
            'analysis_based_on' => '180 days history',
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'summary' => $summary,
            'urgent_actions' => array_values($urgentActions),
            'planned_maintenance' => array_values($plannedActions),
            'monitor_only' => array_values($monitorOnly),
            'predictions' => $predictions,
            'recommendations' => $this->generatePredictiveRecommendations($predictions),
        ];
    }

    /**
     * Generate prediction for single equipment
     */
    protected function generateEquipmentPrediction($equipment, $startDate, $endDate, $predictionDays): array
    {
        $equipmentId = $equipment->id;
        
        // Get historical troubles
        $troubles = EquipmentTrouble::where('equipment_id', $equipmentId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get PM history through PmSchedule (sub_asset_id)
        $pmExecutions = PmExecution::whereHas('pmSchedule', function($query) use ($equipmentId) {
            $query->where('sub_asset_id', $equipmentId);
        })->where('created_at', '>=', $startDate)->get();

        // Get running hours data (use asset_id from SubAsset parent)
        $assetId = $equipment->asset_id ?? null;
        $runningHours = $assetId ? RunningHour::where('asset_id', $assetId)
            ->orderBy('recorded_at', 'desc')
            ->first() : null;

        // Calculate MTBF (Mean Time Between Failures)
        $mtbf = $this->calculateMTBF($troubles);

        // Calculate failure probability
        $failureProbability = $this->calculateFailureProbability($troubles, $mtbf, $predictionDays);

        // Calculate PM compliance impact
        $pmCompliance = $this->calculateEquipmentPmCompliance($equipmentId, $startDate, $endDate);

        // Get last trouble date
        $lastTrouble = $troubles->first();
        $daysSinceLastTrouble = $lastTrouble ? Carbon::parse($lastTrouble->created_at)->diffInDays(Carbon::now()) : null;

        // Get last PM date
        $lastPm = $pmExecutions->sortByDesc('created_at')->first();
        $daysSinceLastPm = $lastPm ? Carbon::parse($lastPm->created_at)->diffInDays(Carbon::now()) : null;

        // Check PM schedule (sub_asset_id)
        $nextPmSchedule = PmSchedule::where('sub_asset_id', $equipmentId)
            ->where('is_active', 1)
            ->first();

        $pmOverdue = false;
        $daysUntilNextPm = null;
        if ($nextPmSchedule && $lastPm) {
            $nextPmDate = Carbon::parse($lastPm->created_at)->addDays($nextPmSchedule->frequency ?? 30);
            $daysUntilNextPm = Carbon::now()->diffInDays($nextPmDate, false);
            $pmOverdue = $daysUntilNextPm < 0;
        }

        // Calculate risk score (0-100)
        $riskScore = $this->calculateRiskScore([
            'failure_probability' => $failureProbability,
            'pm_compliance' => $pmCompliance,
            'pm_overdue' => $pmOverdue,
            'days_since_last_pm' => $daysSinceLastPm,
            'trouble_trend' => $this->getTroubleTrend($troubles),
            'mtbf' => $mtbf,
        ]);

        // Determine risk level
        $riskLevel = $riskScore >= 70 ? 'high' : ($riskScore >= 40 ? 'medium' : 'low');

        // Predict next failure timeframe
        $predictedFailure = $this->predictNextFailure($mtbf, $daysSinceLastTrouble, $riskScore);

        // Generate recommended actions
        $recommendedActions = $this->generateEquipmentActions($riskLevel, $pmOverdue, $pmCompliance, $predictedFailure);

        return [
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipment->name,
            'equipment_code' => $equipment->code ?? null,
            'asset_name' => $equipment->asset->name ?? null,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'failure_probability' => round($failureProbability, 1),
            'failure_probability_text' => $failureProbability . '% chance of failure in next ' . $predictionDays . ' days',
            'metrics' => [
                'mtbf_days' => $mtbf,
                'mtbf_text' => $mtbf ? "{$mtbf} days between failures" : 'Insufficient data',
                'total_troubles_6m' => $troubles->count(),
                'pm_compliance' => $pmCompliance . '%',
                'days_since_last_trouble' => $daysSinceLastTrouble,
                'days_since_last_pm' => $daysSinceLastPm,
                'running_hours' => $runningHours->hours ?? null,
            ],
            'pm_status' => [
                'last_pm_date' => $lastPm ? Carbon::parse($lastPm->created_at)->format('Y-m-d') : null,
                'pm_overdue' => $pmOverdue,
                'days_until_next_pm' => $daysUntilNextPm,
                'schedule_frequency' => $nextPmSchedule->frequency ?? null,
            ],
            'predicted_failure' => $predictedFailure,
            'recommended_actions' => $recommendedActions,
        ];
    }

    /**
     * Calculate Mean Time Between Failures
     */
    protected function calculateMTBF($troubles): ?int
    {
        if ($troubles->count() < 2) {
            return null;
        }

        $sortedTroubles = $troubles->sortBy('created_at')->values();
        $totalDays = 0;
        $intervals = 0;

        for ($i = 1; $i < $sortedTroubles->count(); $i++) {
            $daysBetween = Carbon::parse($sortedTroubles[$i - 1]->created_at)
                ->diffInDays(Carbon::parse($sortedTroubles[$i]->created_at));
            
            if ($daysBetween > 0) {
                $totalDays += $daysBetween;
                $intervals++;
            }
        }

        return $intervals > 0 ? round($totalDays / $intervals) : null;
    }

    /**
     * Calculate failure probability
     */
    protected function calculateFailureProbability($troubles, $mtbf, $predictionDays): float
    {
        if (!$mtbf || $mtbf <= 0) {
            // If no MTBF data, use trouble count based probability
            $troubleCount = $troubles->count();
            if ($troubleCount >= 5) return 60;
            if ($troubleCount >= 3) return 40;
            if ($troubleCount >= 1) return 20;
            return 5;
        }

        // Calculate probability based on MTBF and prediction period
        // Using exponential distribution: P = 1 - e^(-t/MTBF)
        $lastTrouble = $troubles->first();
        $daysSinceLastFailure = $lastTrouble 
            ? Carbon::parse($lastTrouble->created_at)->diffInDays(Carbon::now())
            : 0;

        // Days until prediction period end
        $t = $daysSinceLastFailure + $predictionDays;
        
        // Probability calculation
        $probability = (1 - exp(-$t / $mtbf)) * 100;
        
        return min(95, max(5, $probability));
    }

    /**
     * Calculate equipment-specific PM compliance
     */
    protected function calculateEquipmentPmCompliance($equipmentId, $startDate, $endDate): float
    {
        // Query through PmSchedule relationship (sub_asset_id)
        $totalPm = PmExecution::whereHas('pmSchedule', function($query) use ($equipmentId) {
            $query->where('sub_asset_id', $equipmentId);
        })->whereBetween('created_at', [$startDate, $endDate])->count();

        $onTimePm = PmExecution::whereHas('pmSchedule', function($query) use ($equipmentId) {
            $query->where('sub_asset_id', $equipmentId);
        })->whereBetween('created_at', [$startDate, $endDate])
            ->where('is_on_time', true)
            ->count();

        return $totalPm > 0 ? round(($onTimePm / $totalPm) * 100, 1) : 0;
    }

    /**
     * Get trouble trend
     */
    protected function getTroubleTrend($troubles): string
    {
        if ($troubles->count() < 2) return 'stable';

        $midpoint = Carbon::now()->subDays(90);
        $first = $troubles->where('created_at', '<', $midpoint)->count();
        $second = $troubles->where('created_at', '>=', $midpoint)->count();

        if ($first == 0) return $second > 0 ? 'increasing' : 'stable';
        
        $change = (($second - $first) / $first) * 100;
        
        if ($change > 30) return 'increasing';
        if ($change < -30) return 'decreasing';
        return 'stable';
    }

    /**
     * Calculate overall risk score
     */
    protected function calculateRiskScore(array $factors): int
    {
        $score = 0;
        
        // Failure probability weight: 35%
        $score += ($factors['failure_probability'] ?? 0) * 0.35;
        
        // PM compliance weight: 25% (inverse - low compliance = high risk)
        $pmComplianceRisk = 100 - ($factors['pm_compliance'] ?? 100);
        $score += $pmComplianceRisk * 0.25;
        
        // PM overdue weight: 15%
        if ($factors['pm_overdue'] ?? false) {
            $score += 15;
        }
        
        // Trouble trend weight: 15%
        $trendScore = match($factors['trouble_trend'] ?? 'stable') {
            'increasing' => 15,
            'stable' => 5,
            'decreasing' => 0,
            default => 5,
        };
        $score += $trendScore;
        
        // Days since last PM weight: 10%
        $daysSincePm = $factors['days_since_last_pm'] ?? 0;
        if ($daysSincePm > 60) $score += 10;
        elseif ($daysSincePm > 30) $score += 5;
        
        return min(100, max(0, round($score)));
    }

    /**
     * Predict next failure timeframe
     */
    protected function predictNextFailure($mtbf, $daysSinceLastTrouble, $riskScore): array
    {
        if (!$mtbf) {
            return [
                'prediction' => 'Insufficient data for prediction',
                'confidence' => 'low',
                'timeframe' => null,
            ];
        }

        $daysRemaining = max(0, $mtbf - ($daysSinceLastTrouble ?? 0));
        
        // Adjust based on risk score
        if ($riskScore >= 70) {
            $daysRemaining = max(1, $daysRemaining * 0.5);
            $confidence = 'high';
        } elseif ($riskScore >= 40) {
            $daysRemaining = $daysRemaining * 0.75;
            $confidence = 'medium';
        } else {
            $confidence = 'low';
        }

        $predictedDate = Carbon::now()->addDays((int)$daysRemaining);

        return [
            'predicted_date' => $predictedDate->format('Y-m-d'),
            'days_remaining' => (int)$daysRemaining,
            'timeframe' => $this->getTimeframeText((int)$daysRemaining),
            'confidence' => $confidence,
            'prediction' => "Potential failure in approximately {$daysRemaining} days",
        ];
    }

    /**
     * Get timeframe text
     */
    protected function getTimeframeText($days): string
    {
        if ($days <= 7) return 'This week';
        if ($days <= 14) return 'Next 2 weeks';
        if ($days <= 30) return 'This month';
        if ($days <= 60) return 'Next 2 months';
        return 'Beyond 2 months';
    }

    /**
     * Generate equipment-specific actions
     */
    protected function generateEquipmentActions($riskLevel, $pmOverdue, $pmCompliance, $predictedFailure): array
    {
        $actions = [];

        if ($riskLevel === 'high') {
            $actions[] = [
                'priority' => 'urgent',
                'action' => 'Schedule immediate inspection',
                'timeline' => 'Within 24 hours',
            ];
            
            if ($pmOverdue) {
                $actions[] = [
                    'priority' => 'urgent',
                    'action' => 'Execute overdue PM immediately',
                    'timeline' => 'This week',
                ];
            }
        }

        if ($riskLevel === 'medium' || $pmOverdue) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Schedule preventive maintenance',
                'timeline' => 'Within 1 week',
            ];
        }

        if ($pmCompliance < 80) {
            $actions[] = [
                'priority' => 'medium',
                'action' => 'Improve PM compliance - create schedule reminders',
                'timeline' => 'This month',
            ];
        }

        if ($riskLevel === 'low') {
            $actions[] = [
                'priority' => 'low',
                'action' => 'Continue regular monitoring',
                'timeline' => 'Ongoing',
            ];
        }

        return $actions;
    }

    /**
     * Generate predictive recommendations
     */
    protected function generatePredictiveRecommendations($predictions): array
    {
        $recommendations = [];
        $highRiskCount = count(array_filter($predictions, fn($p) => $p['risk_level'] === 'high'));
        $overdueCount = count(array_filter($predictions, fn($p) => $p['pm_status']['pm_overdue'] ?? false));

        if ($highRiskCount > 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => "{$highRiskCount} equipment at high risk - immediate attention required",
                'action' => 'Schedule urgent inspections for high-risk equipment',
            ];
        }

        if ($overdueCount > 0) {
            $recommendations[] = [
                'type' => 'high',
                'message' => "{$overdueCount} equipment have overdue PM schedules",
                'action' => 'Catch up on overdue preventive maintenance',
            ];
        }

        $lowComplianceEquipment = array_filter($predictions, fn($p) => floatval(str_replace('%', '', $p['metrics']['pm_compliance'])) < 70);
        if (count($lowComplianceEquipment) > 0) {
            $recommendations[] = [
                'type' => 'improvement',
                'message' => count($lowComplianceEquipment) . ' equipment have PM compliance below 70%',
                'action' => 'Review PM scheduling and resource allocation',
            ];
        }

        return $recommendations;
    }

    /**
     * Benchmark equipment performance
     * 
     * @param array $params
     * @return array
     */
    public function benchmarkPerformance(array $params): array
    {
        $equipmentId = $params['equipment_id'] ?? null;
        $period = $params['period'] ?? 90;
        $compareWith = $params['compare_with'] ?? 'peers'; // peers, historical, target

        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // Previous period for comparison
        $prevStartDate = Carbon::now()->subDays($period * 2);
        $prevEndDate = $startDate;

        $benchmarks = [];

        // Get equipment list
        if ($equipmentId) {
            $equipment = SubAsset::find($equipmentId);
            if (!$equipment) {
                return ['success' => false, 'message' => 'Equipment not found'];
            }
            $equipmentList = collect([$equipment]);
        } else {
            $equipmentList = SubAsset::where('is_active', 1)->get();
        }

        foreach ($equipmentList as $equipment) {
            $benchmark = $this->calculateEquipmentBenchmark($equipment, $startDate, $endDate, $prevStartDate, $prevEndDate);
            $benchmarks[] = $benchmark;
        }

        // Calculate averages for comparison
        $avgMetrics = $this->calculateAverageMetrics($benchmarks);

        // Add comparison data to each equipment
        foreach ($benchmarks as &$benchmark) {
            $benchmark['comparison'] = $this->compareToAverage($benchmark, $avgMetrics);
        }

        // Sort by performance score
        usort($benchmarks, fn($a, $b) => $b['performance_score'] <=> $a['performance_score']);

        // Identify top and bottom performers
        $topPerformers = array_slice($benchmarks, 0, 5);
        $bottomPerformers = array_slice(array_reverse($benchmarks), 0, 5);

        return [
            'success' => true,
            'period' => "{$period} days",
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'summary' => [
                'total_equipment' => count($benchmarks),
                'average_uptime' => $avgMetrics['avg_uptime'] . '%',
                'average_mtbf' => $avgMetrics['avg_mtbf'] . ' days',
                'average_mttr' => $avgMetrics['avg_mttr'] . ' hours',
                'average_pm_compliance' => $avgMetrics['avg_pm_compliance'] . '%',
                'average_performance_score' => $avgMetrics['avg_performance_score'],
            ],
            'top_performers' => array_map(fn($b) => [
                'equipment_name' => $b['equipment_name'],
                'performance_score' => $b['performance_score'],
                'uptime' => $b['metrics']['uptime'] . '%',
            ], $topPerformers),
            'needs_improvement' => array_map(fn($b) => [
                'equipment_name' => $b['equipment_name'],
                'performance_score' => $b['performance_score'],
                'main_issue' => $b['main_issue'] ?? 'General performance',
            ], $bottomPerformers),
            'benchmarks' => $benchmarks,
            'improvement_opportunities' => $this->identifyImprovementOpportunities($benchmarks, $avgMetrics),
        ];
    }

    /**
     * Calculate benchmark metrics for single equipment
     */
    protected function calculateEquipmentBenchmark($equipment, $startDate, $endDate, $prevStartDate, $prevEndDate): array
    {
        $equipmentId = $equipment->id;
        $periodDays = $startDate->diffInDays($endDate);

        // Get troubles in current period
        $troubles = EquipmentTrouble::where('equipment_id', $equipmentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Get troubles in previous period
        $prevTroubles = EquipmentTrouble::where('equipment_id', $equipmentId)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->get();

        // Calculate MTBF
        $mtbf = $this->calculateMTBF($troubles);

        // Calculate MTTR (Mean Time To Repair)
        $mttr = $this->calculateMTTR($troubles);

        // Calculate downtime
        $totalDowntimeHours = $troubles->sum('downtime_minutes') / 60;

        // Calculate uptime percentage
        $totalHours = $periodDays * 24;
        $uptime = $totalHours > 0 ? round((($totalHours - $totalDowntimeHours) / $totalHours) * 100, 2) : 100;

        // Get PM compliance
        $pmCompliance = $this->calculateEquipmentPmCompliance($equipmentId, $startDate, $endDate);

        // Get maintenance costs
        $maintenanceCost = $this->getEquipmentMaintenanceCost($equipmentId, $startDate, $endDate);

        // Get running hours (use asset_id from SubAsset parent)
        $assetId = $equipment->asset_id ?? null;
        $runningHours = $assetId ? RunningHour::where('asset_id', $assetId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->sum('hours') : 0;

        // Calculate cost per operating hour
        $costPerHour = $runningHours > 0 ? round($maintenanceCost / $runningHours, 2) : 0;

        // Calculate performance score (0-100)
        $performanceScore = $this->calculatePerformanceScore([
            'uptime' => $uptime,
            'mtbf' => $mtbf,
            'mttr' => $mttr,
            'pm_compliance' => $pmCompliance,
            'cost_efficiency' => $costPerHour,
            'trouble_trend' => $this->compareTroubleCount($troubles->count(), $prevTroubles->count()),
        ]);

        // Determine main issue if performance is low
        $mainIssue = $this->identifyMainIssue($uptime, $mtbf, $mttr, $pmCompliance);

        return [
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipment->name,
            'equipment_code' => $equipment->code ?? null,
            'asset_name' => $equipment->asset->name ?? null,
            'performance_score' => $performanceScore,
            'performance_grade' => $this->getPerformanceGrade($performanceScore),
            'metrics' => [
                'uptime' => $uptime,
                'mtbf' => $mtbf ?? 0,
                'mttr' => $mttr ?? 0,
                'pm_compliance' => $pmCompliance,
                'trouble_count' => $troubles->count(),
                'prev_trouble_count' => $prevTroubles->count(),
                'downtime_hours' => round($totalDowntimeHours, 1),
                'running_hours' => $runningHours,
                'maintenance_cost' => $maintenanceCost,
                'cost_per_hour' => $costPerHour,
            ],
            'trend' => [
                'trouble_trend' => $this->compareTroubleCount($troubles->count(), $prevTroubles->count()),
                'vs_previous_period' => $this->calculateTrendPercentage($prevTroubles->count(), $troubles->count()),
            ],
            'main_issue' => $mainIssue,
        ];
    }

    /**
     * Calculate Mean Time To Repair
     */
    protected function calculateMTTR($troubles): ?float
    {
        $troublesWithDowntime = $troubles->filter(fn($t) => ($t->downtime_minutes ?? 0) > 0);
        
        if ($troublesWithDowntime->count() === 0) return null;

        $totalMinutes = $troublesWithDowntime->sum('downtime_minutes');
        return round($totalMinutes / $troublesWithDowntime->count() / 60, 1); // Return in hours
    }

    /**
     * Get equipment maintenance cost
     */
    protected function getEquipmentMaintenanceCost($equipmentId, $startDate, $endDate): float
    {
        // WO costs for this equipment (sub_asset_id)
        $woCost = WorkOrder::where('sub_asset_id', $equipmentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('cost')
            ->get()
            ->sum(function($wo) {
                $cost = $wo->cost;
                return $cost ? (($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0)) : 0;
            });

        // PM costs for this equipment (through PmSchedule relationship)
        $pmCost = PmExecution::whereHas('pmSchedule', function($query) use ($equipmentId) {
            $query->where('sub_asset_id', $equipmentId);
        })->whereBetween('created_at', [$startDate, $endDate])
            ->with('cost')
            ->get()
            ->sum(function($pm) {
                $cost = $pm->cost;
                return $cost ? (($cost->labour_cost ?? 0) + ($cost->parts_cost ?? 0)) : 0;
            });

        return $woCost + $pmCost;
    }

    /**
     * Calculate performance score
     */
    protected function calculatePerformanceScore(array $metrics): int
    {
        $score = 0;

        // Uptime weight: 30%
        $score += ($metrics['uptime'] ?? 0) * 0.30;

        // MTBF weight: 20% (higher is better, normalize to 100 based on 90 days)
        $mtbfScore = min(100, (($metrics['mtbf'] ?? 0) / 90) * 100);
        $score += $mtbfScore * 0.20;

        // MTTR weight: 15% (lower is better, inverse)
        $mttrScore = max(0, 100 - (($metrics['mttr'] ?? 0) * 10)); // 10 hours = 0 score
        $score += $mttrScore * 0.15;

        // PM compliance weight: 25%
        $score += ($metrics['pm_compliance'] ?? 0) * 0.25;

        // Trend weight: 10%
        $trendScore = match($metrics['trouble_trend'] ?? 'stable') {
            'improving' => 100,
            'stable' => 70,
            'worsening' => 30,
            default => 50,
        };
        $score += $trendScore * 0.10;

        return min(100, max(0, round($score)));
    }

    /**
     * Get performance grade
     */
    protected function getPerformanceGrade($score): string
    {
        if ($score >= 90) return 'A (Excellent)';
        if ($score >= 80) return 'B (Good)';
        if ($score >= 70) return 'C (Average)';
        if ($score >= 60) return 'D (Below Average)';
        return 'F (Needs Improvement)';
    }

    /**
     * Compare trouble counts
     */
    protected function compareTroubleCount($current, $previous): string
    {
        if ($previous == 0) return $current > 0 ? 'worsening' : 'stable';
        
        $change = (($current - $previous) / $previous) * 100;
        
        if ($change < -20) return 'improving';
        if ($change > 20) return 'worsening';
        return 'stable';
    }

    /**
     * Calculate trend percentage
     */
    protected function calculateTrendPercentage($previous, $current): string
    {
        if ($previous == 0) return $current > 0 ? '+100%' : '0%';
        
        $change = round((($current - $previous) / $previous) * 100, 1);
        return ($change >= 0 ? '+' : '') . $change . '%';
    }

    /**
     * Identify main issue
     */
    protected function identifyMainIssue($uptime, $mtbf, $mttr, $pmCompliance): ?string
    {
        $issues = [];

        if ($uptime < 95) $issues[] = 'Low uptime (' . $uptime . '%)';
        if ($mtbf !== null && $mtbf < 30) $issues[] = 'Frequent failures (MTBF: ' . $mtbf . ' days)';
        if ($mttr !== null && $mttr > 4) $issues[] = 'Long repair times (MTTR: ' . $mttr . ' hours)';
        if ($pmCompliance < 70) $issues[] = 'Low PM compliance (' . $pmCompliance . '%)';

        return count($issues) > 0 ? $issues[0] : null;
    }

    /**
     * Calculate average metrics across all equipment
     */
    protected function calculateAverageMetrics($benchmarks): array
    {
        $count = count($benchmarks);
        if ($count === 0) {
            return [
                'avg_uptime' => 0,
                'avg_mtbf' => 0,
                'avg_mttr' => 0,
                'avg_pm_compliance' => 0,
                'avg_performance_score' => 0,
            ];
        }

        return [
            'avg_uptime' => round(array_sum(array_column(array_column($benchmarks, 'metrics'), 'uptime')) / $count, 1),
            'avg_mtbf' => round(array_sum(array_column(array_column($benchmarks, 'metrics'), 'mtbf')) / $count, 1),
            'avg_mttr' => round(array_sum(array_column(array_column($benchmarks, 'metrics'), 'mttr')) / $count, 1),
            'avg_pm_compliance' => round(array_sum(array_column(array_column($benchmarks, 'metrics'), 'pm_compliance')) / $count, 1),
            'avg_performance_score' => round(array_sum(array_column($benchmarks, 'performance_score')) / $count, 1),
        ];
    }

    /**
     * Compare to average
     */
    protected function compareToAverage($benchmark, $avgMetrics): array
    {
        $metrics = $benchmark['metrics'];

        return [
            'uptime_vs_avg' => $this->getComparisonText($metrics['uptime'], $avgMetrics['avg_uptime']),
            'mtbf_vs_avg' => $this->getComparisonText($metrics['mtbf'], $avgMetrics['avg_mtbf']),
            'mttr_vs_avg' => $this->getComparisonText($avgMetrics['avg_mttr'], $metrics['mttr']), // Inverse - lower is better
            'pm_compliance_vs_avg' => $this->getComparisonText($metrics['pm_compliance'], $avgMetrics['avg_pm_compliance']),
            'overall' => $benchmark['performance_score'] >= $avgMetrics['avg_performance_score'] ? 'Above Average' : 'Below Average',
        ];
    }

    /**
     * Get comparison text
     */
    protected function getComparisonText($value, $average): string
    {
        if ($average == 0) return 'N/A';
        
        $diff = round((($value - $average) / $average) * 100, 1);
        
        if ($diff > 10) return '+' . $diff . '% (Above Avg)';
        if ($diff < -10) return $diff . '% (Below Avg)';
        return 'Average';
    }

    /**
     * Identify improvement opportunities
     */
    protected function identifyImprovementOpportunities($benchmarks, $avgMetrics): array
    {
        $opportunities = [];

        // Find equipment with low PM compliance
        $lowPmCompliance = array_filter($benchmarks, fn($b) => $b['metrics']['pm_compliance'] < 70);
        if (count($lowPmCompliance) > 0) {
            $opportunities[] = [
                'area' => 'PM Compliance',
                'equipment_count' => count($lowPmCompliance),
                'current_avg' => round(array_sum(array_column(array_column($lowPmCompliance, 'metrics'), 'pm_compliance')) / count($lowPmCompliance), 1) . '%',
                'target' => '90%',
                'action' => 'Improve PM scheduling and execution tracking',
                'potential_impact' => 'Reduce unplanned failures by 30-50%',
            ];
        }

        // Find equipment with high downtime
        $highDowntime = array_filter($benchmarks, fn($b) => $b['metrics']['downtime_hours'] > 24);
        if (count($highDowntime) > 0) {
            $opportunities[] = [
                'area' => 'Downtime Reduction',
                'equipment_count' => count($highDowntime),
                'total_downtime' => round(array_sum(array_column(array_column($highDowntime, 'metrics'), 'downtime_hours')), 1) . ' hours',
                'action' => 'Implement predictive maintenance and faster response',
                'potential_impact' => 'Reduce downtime by 40-60%',
            ];
        }

        // Find equipment with long MTTR
        $longMttr = array_filter($benchmarks, fn($b) => ($b['metrics']['mttr'] ?? 0) > 4);
        if (count($longMttr) > 0) {
            $opportunities[] = [
                'area' => 'Repair Efficiency',
                'equipment_count' => count($longMttr),
                'avg_mttr' => round(array_sum(array_column(array_column($longMttr, 'metrics'), 'mttr')) / count($longMttr), 1) . ' hours',
                'action' => 'Train technicians, stock critical spare parts',
                'potential_impact' => 'Reduce MTTR by 30-40%',
            ];
        }

        return $opportunities;
    }

    /**
     * Generate maintenance briefing
     * 
     * @param array $params
     * @return array
     */
    public function generateMaintenanceBriefing(array $params): array
    {
        $briefingType = $params['type'] ?? 'daily'; // daily, weekly, monthly
        $includeDetails = $params['include_details'] ?? true;

        $now = Carbon::now();
        
        // Determine date ranges based on briefing type
        switch ($briefingType) {
            case 'weekly':
                $currentStart = $now->copy()->startOfWeek();
                $currentEnd = $now->copy()->endOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                $periodLabel = 'Week ' . $now->weekOfYear . ', ' . $now->year;
                break;
            case 'monthly':
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                $periodLabel = $now->format('F Y');
                break;
            default: // daily
                $currentStart = $now->copy()->startOfDay();
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd = $now->copy()->subDay()->endOfDay();
                $periodLabel = $now->format('l, d F Y');
        }

        // Generate briefing sections
        $briefing = [
            'success' => true,
            'briefing_type' => $briefingType,
            'period' => $periodLabel,
            'generated_at' => $now->format('Y-m-d H:i:s'),
        ];

        // 1. Critical Alerts
        $briefing['critical_alerts'] = $this->getCriticalAlerts($currentStart, $currentEnd);

        // 2. Work Order Summary
        $briefing['work_orders'] = $this->getWoBriefingSummary($currentStart, $currentEnd, $previousStart, $previousEnd);

        // 3. PM Summary
        $briefing['preventive_maintenance'] = $this->getPmBriefingSummary($currentStart, $currentEnd);

        // 4. Equipment Status
        $briefing['equipment_status'] = $this->getEquipmentStatusSummary();

        // 5. Key Metrics
        $briefing['key_metrics'] = $this->getKeyMetrics($currentStart, $currentEnd, $previousStart, $previousEnd);

        // 6. Today's/This Period's Plan
        $briefing['action_plan'] = $this->getActionPlan($briefingType, $currentEnd);

        // 7. Recommendations
        $briefing['recommendations'] = $this->generateBriefingRecommendations($briefing);

        return $briefing;
    }

    /**
     * Get critical alerts for briefing
     */
    protected function getCriticalAlerts($startDate, $endDate): array
    {
        $alerts = [];

        // Equipment troubles (not resolved)
        $openTroubles = EquipmentTrouble::where('status', '!=', 'resolved')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($openTroubles as $trouble) {
            $alerts[] = [
                'type' => 'trouble',
                'severity' => $trouble->priority ?? 'medium',
                'equipment' => $trouble->equipment->name ?? 'Unknown',
                'issue' => $trouble->issue_description ?? 'Equipment trouble',
                'created_at' => Carbon::parse($trouble->created_at)->format('Y-m-d H:i'),
                'status' => $trouble->status,
            ];
        }

        // Overdue PMs (use pmExecutions relationship)
        $overduePms = PmSchedule::where('is_active', 1)
            ->whereHas('pmExecutions', function($q) use ($startDate) {
                $q->where('created_at', '<', $startDate->copy()->subDays(30));
            })
            ->orWhereDoesntHave('pmExecutions')
            ->limit(10)
            ->get();

        foreach ($overduePms as $pm) {
            $alerts[] = [
                'type' => 'overdue_pm',
                'severity' => 'high',
                'equipment' => $pm->subAsset->name ?? 'Unknown',
                'issue' => 'PM Overdue',
                'schedule' => $pm->title ?? 'Preventive Maintenance',
            ];
        }

        // Low stock parts
        $lowStockParts = Part::whereColumn('current_stock', '<=', 'min_stock')
            ->where('current_stock', '>', 0)
            ->limit(5)
            ->get();

        foreach ($lowStockParts as $part) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'medium',
                'item' => $part->name,
                'current_stock' => $part->current_stock,
                'min_stock' => $part->min_stock,
            ];
        }

        // Sort by severity
        usort($alerts, function($a, $b) {
            $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($severityOrder[$a['severity']] ?? 4) <=> ($severityOrder[$b['severity']] ?? 4);
        });

        return [
            'total' => count($alerts),
            'critical_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
            'high_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'high')),
            'alerts' => array_slice($alerts, 0, 10),
        ];
    }

    /**
     * Get WO briefing summary
     */
    protected function getWoBriefingSummary($currentStart, $currentEnd, $previousStart, $previousEnd): array
    {
        // Current period
        $currentWos = WorkOrder::whereBetween('created_at', [$currentStart, $currentEnd])->get();
        $completedWos = WorkOrder::whereBetween('completed_at', [$currentStart, $currentEnd])->get();
        $openWos = WorkOrder::whereNotIn('status', ['completed', 'closed', 'cancelled'])->get();

        // Previous period for comparison
        $prevWos = WorkOrder::whereBetween('created_at', [$previousStart, $previousEnd])->get();
        $prevCompletedWos = WorkOrder::whereBetween('completed_at', [$previousStart, $previousEnd])->get();

        return [
            'new_work_orders' => $currentWos->count(),
            'completed' => $completedWos->count(),
            'open_total' => $openWos->count(),
            'by_status' => [
                'pending' => $openWos->where('status', 'pending')->count(),
                'in_progress' => $openWos->where('status', 'in_progress')->count(),
                'waiting_parts' => $openWos->where('status', 'waiting_parts')->count(),
            ],
            'by_priority' => [
                'emergency' => $currentWos->where('priority', 'Emergency')->count(),
                'high' => $currentWos->where('priority', 'High')->count(),
                'medium' => $currentWos->where('priority', 'Medium')->count(),
                'low' => $currentWos->where('priority', 'Low')->count(),
            ],
            'comparison' => [
                'new_vs_previous' => $this->calculateTrendPercentage($prevWos->count(), $currentWos->count()),
                'completed_vs_previous' => $this->calculateTrendPercentage($prevCompletedWos->count(), $completedWos->count()),
            ],
        ];
    }

    /**
     * Get PM briefing summary
     */
    protected function getPmBriefingSummary($startDate, $endDate): array
    {
        $scheduledPms = PmSchedule::where('is_active', 1)->count();
        $executedPms = PmExecution::where('status', 'completed')
            ->whereBetween('actual_end', [$startDate, $endDate])
            ->get();
        
        // Get active PMs and filter by next_due_date accessor in PHP
        $allActivePms = PmSchedule::where('is_active', 1)
            ->where('schedule_type', 'weekly') // Only weekly schedules have due dates
            ->get();
        $cutoffDate = $endDate->copy()->addDays(7);
        $upcomingPms = $allActivePms->filter(function($pm) use ($cutoffDate) {
            $dueDate = $pm->next_due_date;
            return $dueDate && $dueDate <= $cutoffDate;
        });

        // Calculate compliance (completed within 3 days of scheduled date)
        $onTime = $executedPms->filter(function($pm) {
            if (!$pm->actual_end) return false;
            return Carbon::parse($pm->actual_end)->diffInDays(Carbon::parse($pm->scheduled_date)) <= 3;
        })->count();

        $compliance = $executedPms->count() > 0 
            ? round(($onTime / $executedPms->count()) * 100, 1) 
            : 0;

        return [
            'scheduled_total' => $scheduledPms,
            'executed_this_period' => $executedPms->count(),
            'compliance_rate' => $compliance . '%',
            'on_time' => $onTime,
            'late' => $executedPms->count() - $onTime,
            'upcoming_7_days' => $upcomingPms->count(),
            'upcoming_list' => $upcomingPms->take(5)->map(fn($pm) => [
                'equipment' => $pm->subAsset->name ?? 'Unknown',
                'schedule' => $pm->title ?? 'PM',
                'due_date' => $pm->next_due_date ? Carbon::parse($pm->next_due_date)->format('Y-m-d') : 'TBD',
            ])->toArray(),
        ];
    }

    /**
     * Get equipment status summary
     */
    protected function getEquipmentStatusSummary(): array
    {
        $totalEquipment = SubAsset::where('is_active', 1)->count();
        
        // Equipment with open troubles
        $troubledEquipment = EquipmentTrouble::where('status', '!=', 'resolved')
            ->distinct('equipment_id')
            ->count('equipment_id');

        return [
            'total_active' => $totalEquipment,
            'operational' => $totalEquipment - $troubledEquipment,
            'with_issues' => $troubledEquipment,
            'availability_rate' => $totalEquipment > 0 
                ? round((($totalEquipment - $troubledEquipment) / $totalEquipment) * 100, 1) . '%'
                : '100%',
        ];
    }

    /**
     * Get key metrics
     */
    protected function getKeyMetrics($currentStart, $currentEnd, $previousStart, $previousEnd): array
    {
        // Current period troubles
        $currentTroubles = EquipmentTrouble::whereBetween('created_at', [$currentStart, $currentEnd])->get();
        $prevTroubles = EquipmentTrouble::whereBetween('created_at', [$previousStart, $previousEnd])->get();

        // Downtime
        $totalDowntime = $currentTroubles->sum('downtime_minutes');

        // Costs
        $currentCosts = WoCost::whereBetween('created_at', [$currentStart, $currentEnd])->get();
        $totalCost = $currentCosts->sum(fn($c) => ($c->labour_cost ?? 0) + ($c->parts_cost ?? 0));

        return [
            'troubles' => [
                'count' => $currentTroubles->count(),
                'vs_previous' => $this->calculateTrendPercentage($prevTroubles->count(), $currentTroubles->count()),
            ],
            'downtime' => [
                'total_minutes' => $totalDowntime,
                'total_hours' => round($totalDowntime / 60, 1),
            ],
            'costs' => [
                'total' => $totalCost,
                'formatted' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            ],
        ];
    }

    /**
     * Get action plan
     */
    protected function getActionPlan($briefingType, $endDate): array
    {
        $actions = [];

        // Upcoming PMs - filter in PHP since next_due_date is an accessor
        $daysAhead = $briefingType === 'daily' ? 1 : ($briefingType === 'weekly' ? 7 : 30);
        $cutoffDate = $endDate->copy()->addDays($daysAhead);
        
        $allActivePms = PmSchedule::where('is_active', 1)
            ->where('schedule_type', 'weekly')
            ->get();
        
        $upcomingPms = $allActivePms->filter(function($pm) use ($cutoffDate) {
            $dueDate = $pm->next_due_date;
            return $dueDate && $dueDate <= $cutoffDate;
        })->sortBy(function($pm) {
            return $pm->next_due_date ? $pm->next_due_date->timestamp : PHP_INT_MAX;
        })->take(10);

        foreach ($upcomingPms as $pm) {
            $actions[] = [
                'type' => 'pm',
                'priority' => 'medium',
                'action' => 'Execute PM: ' . ($pm->title ?? 'Preventive Maintenance'),
                'equipment' => $pm->subAsset->name ?? 'Unknown',
                'due_date' => $pm->next_due_date ? $pm->next_due_date->format('Y-m-d') : 'TBD',
            ];
        }

        // Open high priority WOs
        $highPriorityWos = WorkOrder::whereIn('priority', ['Emergency', 'High'])
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->orderByRaw("FIELD(priority, 'Emergency', 'High')")
            ->limit(5)
            ->get();

        foreach ($highPriorityWos as $wo) {
            $actions[] = [
                'type' => 'wo',
                'priority' => strtolower($wo->priority),
                'action' => 'Complete WO: ' . ($wo->wo_number ?? $wo->id),
                'equipment' => $wo->subAsset->name ?? 'Unknown',
                'issue' => $wo->issue_description ?? 'Work order',
            ];
        }

        // Sort by priority
        usort($actions, function($a, $b) {
            $priorityOrder = ['emergency' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4);
        });

        return array_slice($actions, 0, 10);
    }

    /**
     * Generate briefing recommendations
     */
    protected function generateBriefingRecommendations($briefing): array
    {
        $recommendations = [];

        // Check critical alerts
        if (($briefing['critical_alerts']['critical_count'] ?? 0) > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'message' => 'Address critical equipment issues immediately',
                'action' => 'Review and assign technicians to critical alerts',
            ];
        }

        // Check PM compliance
        $compliance = floatval(str_replace('%', '', $briefing['preventive_maintenance']['compliance_rate'] ?? '100'));
        if ($compliance < 80) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => 'PM compliance is below target (' . $compliance . '%)',
                'action' => 'Review PM scheduling and resource allocation',
            ];
        }

        // Check open WOs
        if (($briefing['work_orders']['open_total'] ?? 0) > 20) {
            $recommendations[] = [
                'priority' => 'medium',
                'message' => 'High number of open work orders (' . $briefing['work_orders']['open_total'] . ')',
                'action' => 'Prioritize and close backlog WOs',
            ];
        }

        // Check equipment availability
        $availability = floatval(str_replace('%', '', $briefing['equipment_status']['availability_rate'] ?? '100'));
        if ($availability < 95) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => 'Equipment availability below target (' . $availability . '%)',
                'action' => 'Focus on resolving equipment issues',
            ];
        }

        return $recommendations;
    }

    // ========================================================================
    // PHASE 3: SMART RECOMMENDATIONS & WHAT-IF SIMULATOR
    // ========================================================================

    /**
     * Generate smart proactive recommendations based on current system state
     * 
     * @param array $params
     * @return array
     */
    public function getProactiveRecommendations(array $params): array
    {
        $category = $params['category'] ?? 'all'; // all, maintenance, inventory, cost, safety
        $urgencyLevel = $params['urgency_level'] ?? 'all'; // all, critical, high, medium, low
        $maxRecommendations = $params['max_recommendations'] ?? 10;

        $recommendations = [];
        $analysisTimestamp = Carbon::now();

        // 1. MAINTENANCE RECOMMENDATIONS
        if (in_array($category, ['all', 'maintenance'])) {
            $recommendations = array_merge($recommendations, $this->getMaintenanceRecommendations());
        }

        // 2. INVENTORY RECOMMENDATIONS
        if (in_array($category, ['all', 'inventory'])) {
            $recommendations = array_merge($recommendations, $this->getInventoryRecommendations());
        }

        // 3. COST RECOMMENDATIONS
        if (in_array($category, ['all', 'cost'])) {
            $recommendations = array_merge($recommendations, $this->getCostRecommendations());
        }

        // 4. SAFETY & COMPLIANCE RECOMMENDATIONS
        if (in_array($category, ['all', 'safety'])) {
            $recommendations = array_merge($recommendations, $this->getSafetyRecommendations());
        }

        // Filter by urgency level
        if ($urgencyLevel !== 'all') {
            $recommendations = array_filter($recommendations, function($r) use ($urgencyLevel) {
                return $r['urgency'] === $urgencyLevel;
            });
        }

        // Sort by priority score (higher = more urgent)
        usort($recommendations, function($a, $b) {
            return ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0);
        });

        // Limit results
        $recommendations = array_slice($recommendations, 0, $maxRecommendations);

        // Calculate summary stats
        $summary = [
            'total_recommendations' => count($recommendations),
            'by_urgency' => [
                'critical' => count(array_filter($recommendations, fn($r) => $r['urgency'] === 'critical')),
                'high' => count(array_filter($recommendations, fn($r) => $r['urgency'] === 'high')),
                'medium' => count(array_filter($recommendations, fn($r) => $r['urgency'] === 'medium')),
                'low' => count(array_filter($recommendations, fn($r) => $r['urgency'] === 'low')),
            ],
            'by_category' => [
                'maintenance' => count(array_filter($recommendations, fn($r) => $r['category'] === 'maintenance')),
                'inventory' => count(array_filter($recommendations, fn($r) => $r['category'] === 'inventory')),
                'cost' => count(array_filter($recommendations, fn($r) => $r['category'] === 'cost')),
                'safety' => count(array_filter($recommendations, fn($r) => $r['category'] === 'safety')),
            ],
            'total_potential_savings' => array_sum(array_map(fn($r) => $r['estimated_savings'] ?? 0, $recommendations)),
            'total_risk_reduction' => array_sum(array_map(fn($r) => $r['risk_reduction'] ?? 0, $recommendations)),
        ];

        return [
            'success' => true,
            'analysis_timestamp' => $analysisTimestamp->toIso8601String(),
            'filter_applied' => [
                'category' => $category,
                'urgency_level' => $urgencyLevel,
            ],
            'summary' => $summary,
            'recommendations' => array_values($recommendations),
            'metadata' => [
                'analysis_scope' => 'Real-time system state analysis',
                'data_freshness' => 'Live data as of ' . $analysisTimestamp->format('d M Y H:i'),
                'confidence_level' => 'High - based on historical patterns and current metrics',
            ],
        ];
    }

    /**
     * Get maintenance-related recommendations
     */
    protected function getMaintenanceRecommendations(): array
    {
        $recommendations = [];
        $now = Carbon::now();

        // 1. Check overdue PMs
        $overduePms = PmSchedule::where('is_active', true)
            ->whereHas('pmExecutions', function($q) use ($now) {
                $q->whereRaw('DATE_ADD(actual_end, INTERVAL frequency DAY) < ?', [$now]);
            }, '=', 0)
            ->orWhere(function($q) use ($now) {
                $q->where('is_active', true)
                  ->whereDoesntHave('pmExecutions');
            })
            ->with('subAsset')
            ->limit(10)
            ->get();

        foreach ($overduePms as $pm) {
            $recommendations[] = [
                'id' => 'pm_overdue_' . $pm->id,
                'category' => 'maintenance',
                'urgency' => 'high',
                'priority_score' => 85,
                'title' => 'Overdue PM: ' . ($pm->title ?? 'Preventive Maintenance'),
                'description' => 'PM schedule for ' . ($pm->subAsset->name ?? 'equipment') . ' is overdue and requires immediate attention.',
                'equipment' => $pm->subAsset->name ?? 'Unknown',
                'recommended_action' => 'Schedule and execute PM within 48 hours',
                'estimated_impact' => 'Prevents potential breakdown and extends equipment life',
                'estimated_savings' => 500000, // Estimated cost avoidance
                'risk_reduction' => 20, // 20% risk reduction
                'due_date' => $now->addDays(2)->toDateString(),
            ];
        }

        // 2. Check equipment with high trouble frequency
        $troubleFrequency = EquipmentTrouble::select('equipment_id', DB::raw('COUNT(*) as trouble_count'))
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('equipment_id')
            ->having('trouble_count', '>=', 3)
            ->get();

        foreach ($troubleFrequency as $trouble) {
            $equipment = SubAsset::find($trouble->equipment_id);
            if ($equipment) {
                $recommendations[] = [
                    'id' => 'trouble_freq_' . $trouble->equipment_id,
                    'category' => 'maintenance',
                    'urgency' => $trouble->trouble_count >= 5 ? 'critical' : 'high',
                    'priority_score' => min(95, 70 + ($trouble->trouble_count * 5)),
                    'title' => 'High Trouble Frequency: ' . $equipment->name,
                    'description' => $equipment->name . ' has had ' . $trouble->trouble_count . ' troubles in the last 30 days. Root cause analysis recommended.',
                    'equipment' => $equipment->name,
                    'recommended_action' => 'Conduct thorough inspection and root cause analysis',
                    'estimated_impact' => 'Reduce breakdown frequency by 60-70%',
                    'estimated_savings' => $trouble->trouble_count * 750000,
                    'risk_reduction' => min(40, $trouble->trouble_count * 8),
                    'due_date' => $now->addDays(3)->toDateString(),
                ];
            }
        }

        // 3. Check equipment with low PM compliance
        $lowCompliance = DB::table('pm_schedules as ps')
            ->select('ps.sub_asset_id', DB::raw('COUNT(pe.id) as executed'), DB::raw('COUNT(*) as scheduled'))
            ->leftJoin('pm_executions as pe', function($join) use ($now) {
                $join->on('pe.pm_schedule_id', '=', 'ps.id')
                     ->where('pe.actual_end', '>=', $now->copy()->subDays(90));
            })
            ->where('ps.is_active', true)
            ->groupBy('ps.sub_asset_id')
            ->havingRaw('(COUNT(pe.id) / GREATEST(COUNT(*), 1)) < 0.7')
            ->limit(5)
            ->get();

        foreach ($lowCompliance as $comp) {
            $equipment = SubAsset::find($comp->sub_asset_id);
            if ($equipment) {
                $compRate = $comp->scheduled > 0 ? round(($comp->executed / $comp->scheduled) * 100) : 0;
                $recommendations[] = [
                    'id' => 'low_compliance_' . $comp->sub_asset_id,
                    'category' => 'maintenance',
                    'urgency' => $compRate < 50 ? 'high' : 'medium',
                    'priority_score' => 80 - $compRate,
                    'title' => 'Low PM Compliance: ' . $equipment->name,
                    'description' => 'PM compliance for ' . $equipment->name . ' is only ' . $compRate . '%. Target is 90%.',
                    'equipment' => $equipment->name,
                    'recommended_action' => 'Review PM schedule and resource allocation',
                    'estimated_impact' => 'Improve equipment reliability and reduce unplanned downtime',
                    'estimated_savings' => (90 - $compRate) * 10000,
                    'risk_reduction' => (90 - $compRate) / 3,
                    'due_date' => $now->addDays(7)->toDateString(),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get inventory-related recommendations
     */
    protected function getInventoryRecommendations(): array
    {
        $recommendations = [];
        $now = Carbon::now();

        // 1. Critical low stock parts (parts table doesn't have is_active)
        $lowStockParts = Part::whereColumn('current_stock', '<=', 'min_stock')
            ->orderByRaw('current_stock / GREATEST(min_stock, 1) ASC')
            ->limit(10)
            ->get();

        foreach ($lowStockParts as $part) {
            $stockRatio = $part->min_stock > 0 ? round(($part->current_stock / $part->min_stock) * 100) : 0;
            $urgency = $part->current_stock == 0 ? 'critical' : ($stockRatio < 50 ? 'high' : 'medium');
            
            $recommendations[] = [
                'id' => 'low_stock_' . $part->id,
                'category' => 'inventory',
                'urgency' => $urgency,
                'priority_score' => $part->current_stock == 0 ? 95 : (90 - $stockRatio),
                'title' => 'Low Stock: ' . $part->name,
                'description' => $part->name . ' stock is at ' . $part->current_stock . ' ' . ($part->unit ?? 'units') . ' (min: ' . $part->min_stock . '). ' . ($part->current_stock == 0 ? 'OUT OF STOCK!' : 'Reorder needed.'),
                'part_number' => $part->part_number,
                'recommended_action' => 'Place purchase order for ' . max($part->min_stock * 2 - $part->current_stock, $part->min_stock) . ' ' . ($part->unit ?? 'units'),
                'estimated_impact' => 'Prevent production delays due to part unavailability',
                'estimated_savings' => $part->current_stock == 0 ? 2000000 : 500000,
                'risk_reduction' => $part->current_stock == 0 ? 30 : 15,
                'due_date' => $now->addDays($part->current_stock == 0 ? 1 : 5)->toDateString(),
            ];
        }

        // 2. Parts with no movement (dead stock) - parts table doesn't have is_active
        $deadStockParts = Part::where('current_stock', '>', 0)
            ->whereNotIn('id', function($q) use ($now) {
                $q->select('part_id')
                  ->from('inventory_movements')
                  ->where('created_at', '>=', $now->copy()->subDays(180));
            })
            ->limit(5)
            ->get();

        foreach ($deadStockParts as $part) {
            $stockValue = $part->current_stock * ($part->unit_price ?? 0);
            if ($stockValue > 100000) {
                $recommendations[] = [
                    'id' => 'dead_stock_' . $part->id,
                    'category' => 'inventory',
                    'urgency' => 'low',
                    'priority_score' => 30,
                    'title' => 'Dead Stock: ' . $part->name,
                    'description' => $part->name . ' has no movement in 180 days. Stock value: Rp ' . number_format($stockValue, 0, ',', '.'),
                    'part_number' => $part->part_number,
                    'recommended_action' => 'Review if part is still needed, consider disposal or reallocation',
                    'estimated_impact' => 'Free up capital tied in non-moving inventory',
                    'estimated_savings' => $stockValue * 0.1, // 10% carrying cost
                    'risk_reduction' => 5,
                    'due_date' => $now->addDays(30)->toDateString(),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get cost-related recommendations
     */
    protected function getCostRecommendations(): array
    {
        $recommendations = [];
        $now = Carbon::now();

        // 1. High cost equipment (top spenders)
        $highCostEquipment = WoCost::select('work_orders.sub_asset_id', DB::raw('SUM(wo_costs.labour_cost + wo_costs.parts_cost) as total_cost'))
            ->join('work_orders', 'wo_costs.work_order_id', '=', 'work_orders.id')
            ->where('wo_costs.created_at', '>=', $now->copy()->subDays(90))
            ->groupBy('work_orders.sub_asset_id')
            ->having('total_cost', '>', 5000000)
            ->orderBy('total_cost', 'desc')
            ->limit(5)
            ->get();

        foreach ($highCostEquipment as $cost) {
            $equipment = SubAsset::find($cost->sub_asset_id);
            if ($equipment) {
                $recommendations[] = [
                    'id' => 'high_cost_' . $cost->sub_asset_id,
                    'category' => 'cost',
                    'urgency' => $cost->total_cost > 15000000 ? 'high' : 'medium',
                    'priority_score' => min(85, 50 + ($cost->total_cost / 500000)),
                    'title' => 'High Maintenance Cost: ' . $equipment->name,
                    'description' => $equipment->name . ' has spent Rp ' . number_format($cost->total_cost, 0, ',', '.') . ' in the last 90 days. Review cost optimization opportunities.',
                    'equipment' => $equipment->name,
                    'recommended_action' => 'Conduct maintenance strategy review - consider overhaul vs continued repairs',
                    'estimated_impact' => 'Potential 20-30% cost reduction through better maintenance planning',
                    'estimated_savings' => $cost->total_cost * 0.25,
                    'risk_reduction' => 10,
                    'due_date' => $now->addDays(14)->toDateString(),
                ];
            }
        }

        // 2. High overtime/emergency repairs
        $emergencyWoCost = WorkOrder::select(DB::raw('SUM(COALESCE((SELECT SUM(labour_cost + parts_cost) FROM wo_costs WHERE work_order_id = work_orders.id), 0)) as emergency_cost'))
            ->whereIn('priority', ['emergency', 'critical'])
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->first();

        if ($emergencyWoCost && $emergencyWoCost->emergency_cost > 3000000) {
            $recommendations[] = [
                'id' => 'high_emergency_cost',
                'category' => 'cost',
                'urgency' => 'high',
                'priority_score' => 80,
                'title' => 'High Emergency Repair Costs',
                'description' => 'Emergency repairs cost Rp ' . number_format($emergencyWoCost->emergency_cost, 0, ',', '.') . ' in the last 30 days. This is typically 2-3x normal repair costs.',
                'equipment' => 'Multiple',
                'recommended_action' => 'Improve PM compliance and predictive maintenance to reduce emergencies',
                'estimated_impact' => 'Reduce emergency repairs by 50% with proper PM',
                'estimated_savings' => $emergencyWoCost->emergency_cost * 0.5,
                'risk_reduction' => 25,
                'due_date' => $now->addDays(7)->toDateString(),
            ];
        }

        return $recommendations;
    }

    /**
     * Get safety & compliance recommendations
     */
    protected function getSafetyRecommendations(): array
    {
        $recommendations = [];
        $now = Carbon::now();

        // 1. Equipment without recent safety inspection
        // pm_executions has pm_schedule_id, need to join through pm_schedules to get sub_asset_id
        $recentlyInspectedEquipmentIds = \DB::table('pm_executions')
            ->join('pm_schedules', 'pm_executions.pm_schedule_id', '=', 'pm_schedules.id')
            ->where('pm_executions.actual_end', '>=', $now->copy()->subDays(90))
            ->whereNotNull('pm_schedules.sub_asset_id')
            ->pluck('pm_schedules.sub_asset_id')
            ->toArray();

        $noRecentInspection = SubAsset::where('is_active', true)
            ->whereNotIn('id', $recentlyInspectedEquipmentIds)
            ->limit(5)
            ->get();

        foreach ($noRecentInspection as $equipment) {
            $recommendations[] = [
                'id' => 'no_inspection_' . $equipment->id,
                'category' => 'safety',
                'urgency' => 'medium',
                'priority_score' => 65,
                'title' => 'No Recent Inspection: ' . $equipment->name,
                'description' => $equipment->name . ' has not been inspected in the last 90 days. Regular inspections ensure safe operation.',
                'equipment' => $equipment->name,
                'recommended_action' => 'Schedule safety inspection and PM execution',
                'estimated_impact' => 'Ensure equipment safety compliance and identify potential issues',
                'estimated_savings' => 200000,
                'risk_reduction' => 15,
                'due_date' => $now->addDays(7)->toDateString(),
            ];
        }

        // 2. RCA not completed for high-downtime incidents
        $rcaNeeded = WorkOrder::where('rca_required', true)
            ->where('rca_status', '!=', 'completed')
            ->whereNotNull('total_downtime')
            ->where('total_downtime', '>', 30) // More than 30 min downtime
            ->with('subAsset')
            ->limit(5)
            ->get();

        foreach ($rcaNeeded as $wo) {
            $recommendations[] = [
                'id' => 'rca_needed_' . $wo->id,
                'category' => 'safety',
                'urgency' => 'high',
                'priority_score' => 75,
                'title' => 'RCA Required: ' . $wo->wo_number,
                'description' => 'Work order ' . $wo->wo_number . ' caused ' . $wo->total_downtime . ' minutes downtime but RCA is not completed.',
                'equipment' => $wo->subAsset->name ?? 'Unknown',
                'recommended_action' => 'Complete Root Cause Analysis to prevent recurrence',
                'estimated_impact' => 'Prevent similar incidents and improve equipment reliability',
                'estimated_savings' => $wo->total_downtime * 50000, // Assume Rp 50K per minute downtime
                'risk_reduction' => 20,
                'due_date' => $now->addDays(5)->toDateString(),
            ];
        }

        return $recommendations;
    }

    /**
     * Simulate what-if scenarios for maintenance decisions
     * 
     * @param array $params
     * @return array
     */
    public function simulateScenario(array $params): array
    {
        $scenarioType = $params['scenario_type'] ?? 'pm_frequency';
        $equipmentId = $params['equipment_id'] ?? null;
        $parameters = $params['parameters'] ?? [];
        $simulationPeriod = $params['simulation_period'] ?? 365; // days

        $result = match ($scenarioType) {
            'pm_frequency' => $this->simulatePmFrequencyChange($equipmentId, $parameters, $simulationPeriod),
            'add_equipment' => $this->simulateAddEquipment($parameters, $simulationPeriod),
            'budget_change' => $this->simulateBudgetChange($parameters, $simulationPeriod),
            'staffing_change' => $this->simulateStaffingChange($parameters, $simulationPeriod),
            'shutdown_impact' => $this->simulateShutdownImpact($equipmentId, $parameters),
            default => ['error' => 'Unknown scenario type: ' . $scenarioType],
        };

        return array_merge($result, [
            'scenario_type' => $scenarioType,
            'simulation_period_days' => $simulationPeriod,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Simulate PM frequency change impact
     */
    protected function simulatePmFrequencyChange(?int $equipmentId, array $parameters, int $period): array
    {
        $currentFrequency = $parameters['current_frequency'] ?? 30; // days
        $newFrequency = $parameters['new_frequency'] ?? 45; // days
        
        // Get equipment data
        if ($equipmentId) {
            $equipment = SubAsset::find($equipmentId);
            $equipmentName = $equipment ? $equipment->name : 'Unknown';
        } else {
            $equipmentName = 'All Equipment';
        }

        // Calculate current state
        $currentPmCount = ceil($period / $currentFrequency);
        $newPmCount = ceil($period / $newFrequency);
        
        // Average PM cost from history (uses parts_cost instead of material_cost)
        $avgPmCost = PmCost::avg(DB::raw('labour_cost + parts_cost')) ?? 500000;

        // Current annual cost
        $currentAnnualCost = $currentPmCount * $avgPmCost;
        $newAnnualCost = $newPmCount * $avgPmCost;
        $costDifference = $newAnnualCost - $currentAnnualCost;

        // Estimate failure probability change
        // Longer intervals = higher failure risk (simplified model)
        $frequencyRatio = $newFrequency / $currentFrequency;
        $failureProbabilityChange = $frequencyRatio > 1 
            ? '+' . round(($frequencyRatio - 1) * 20) . '%'
            : round((1 - $frequencyRatio) * 15) . '%';

        // Estimate downtime impact
        $avgDowntimePerFailure = 120; // minutes
        $estimatedAdditionalDowntime = $frequencyRatio > 1 
            ? round(($frequencyRatio - 1) * 3 * $avgDowntimePerFailure)
            : -round((1 - $frequencyRatio) * 2 * $avgDowntimePerFailure);

        // Calculate net impact
        $downtimeCostPerMinute = 100000; // Rp 100K per minute
        $estimatedDowntimeCostImpact = $estimatedAdditionalDowntime * $downtimeCostPerMinute;
        $netFinancialImpact = $costDifference + $estimatedDowntimeCostImpact;

        return [
            'success' => true,
            'scenario' => 'PM Frequency Change',
            'equipment' => $equipmentName,
            'current_state' => [
                'pm_frequency_days' => $currentFrequency,
                'pm_count_per_year' => $currentPmCount,
                'estimated_annual_pm_cost' => 'Rp ' . number_format($currentAnnualCost, 0, ',', '.'),
            ],
            'simulated_state' => [
                'pm_frequency_days' => $newFrequency,
                'pm_count_per_year' => $newPmCount,
                'estimated_annual_pm_cost' => 'Rp ' . number_format($newAnnualCost, 0, ',', '.'),
            ],
            'impact_analysis' => [
                'pm_cost_change' => 'Rp ' . number_format($costDifference, 0, ',', '.'),
                'failure_probability_change' => $failureProbabilityChange,
                'estimated_additional_downtime_minutes' => $estimatedAdditionalDowntime,
                'estimated_downtime_cost_impact' => 'Rp ' . number_format($estimatedDowntimeCostImpact, 0, ',', '.'),
                'net_financial_impact' => 'Rp ' . number_format($netFinancialImpact, 0, ',', '.'),
            ],
            'recommendation' => $netFinancialImpact > 0 
                ? 'Not recommended - Higher risk and potential costs outweigh savings'
                : 'Can be considered - Potential savings with acceptable risk',
            'risk_assessment' => [
                'risk_level' => $frequencyRatio > 1.5 ? 'high' : ($frequencyRatio > 1.2 ? 'medium' : 'low'),
                'confidence' => 'Medium - Based on historical averages and industry benchmarks',
            ],
        ];
    }

    /**
     * Simulate adding new equipment
     */
    protected function simulateAddEquipment(array $parameters, int $period): array
    {
        $equipmentType = $parameters['equipment_type'] ?? 'general';
        $quantity = $parameters['quantity'] ?? 1;
        $acquisitionCost = $parameters['acquisition_cost'] ?? 100000000;

        // Industry benchmarks by equipment type
        $benchmarks = [
            'compressor' => ['pm_frequency' => 30, 'pm_cost' => 800000, 'mtbf' => 90, 'mttr' => 4],
            'chiller' => ['pm_frequency' => 30, 'pm_cost' => 1000000, 'mtbf' => 120, 'mttr' => 6],
            'ahu' => ['pm_frequency' => 14, 'pm_cost' => 300000, 'mtbf' => 180, 'mttr' => 2],
            'conveyor' => ['pm_frequency' => 7, 'pm_cost' => 200000, 'mtbf' => 60, 'mttr' => 1],
            'general' => ['pm_frequency' => 30, 'pm_cost' => 500000, 'mtbf' => 90, 'mttr' => 3],
        ];

        $benchmark = $benchmarks[$equipmentType] ?? $benchmarks['general'];

        // Calculate annual maintenance requirements
        $annualPmCount = ceil(365 / $benchmark['pm_frequency']) * $quantity;
        $annualPmCost = $annualPmCount * $benchmark['pm_cost'];

        // Estimate breakdown repairs
        $estimatedBreakdowns = ceil(365 / $benchmark['mtbf']) * $quantity;
        $avgBreakdownCost = $benchmark['pm_cost'] * 3; // Breakdowns cost 3x PM
        $annualBreakdownCost = $estimatedBreakdowns * $avgBreakdownCost;

        // Labor hours required
        $pmHoursPerExecution = 2;
        $breakdownHoursPerEvent = $benchmark['mttr'];
        $totalLaborHours = ($annualPmCount * $pmHoursPerExecution) + ($estimatedBreakdowns * $breakdownHoursPerEvent);

        // Spare parts inventory recommendation
        $recommendedSpareValue = $acquisitionCost * 0.05; // 5% of acquisition cost

        $totalAnnualCost = $annualPmCost + $annualBreakdownCost;

        return [
            'success' => true,
            'scenario' => 'Add New Equipment',
            'equipment_details' => [
                'type' => $equipmentType,
                'quantity' => $quantity,
                'acquisition_cost' => 'Rp ' . number_format($acquisitionCost, 0, ',', '.'),
            ],
            'annual_maintenance_impact' => [
                'pm_executions_per_year' => $annualPmCount,
                'estimated_pm_cost' => 'Rp ' . number_format($annualPmCost, 0, ',', '.'),
                'estimated_breakdowns' => $estimatedBreakdowns,
                'estimated_breakdown_cost' => 'Rp ' . number_format($annualBreakdownCost, 0, ',', '.'),
                'total_annual_maintenance_cost' => 'Rp ' . number_format($totalAnnualCost, 0, ',', '.'),
            ],
            'resource_requirements' => [
                'labor_hours_per_year' => $totalLaborHours,
                'equivalent_fte' => round($totalLaborHours / 2080, 2), // 2080 work hours/year
                'spare_parts_inventory_value' => 'Rp ' . number_format($recommendedSpareValue, 0, ',', '.'),
            ],
            'key_metrics' => [
                'maintenance_cost_ratio' => round(($totalAnnualCost / $acquisitionCost) * 100, 1) . '% of acquisition cost',
                'expected_mtbf' => $benchmark['mtbf'] . ' days',
                'expected_mttr' => $benchmark['mttr'] . ' hours',
            ],
            'recommendations' => [
                'Ensure adequate spare parts inventory before commissioning',
                'Train ' . ceil($totalLaborHours / 500) . ' technicians on the new equipment',
                'Create PM schedule with ' . $benchmark['pm_frequency'] . '-day frequency',
                'Budget Rp ' . number_format($totalAnnualCost * 1.2, 0, ',', '.') . ' for first year (includes contingency)',
            ],
        ];
    }

    /**
     * Simulate budget change impact
     */
    protected function simulateBudgetChange(array $parameters, int $period): array
    {
        $currentBudget = $parameters['current_budget'] ?? 100000000;
        $newBudget = $parameters['new_budget'] ?? 80000000;
        $budgetChange = $newBudget - $currentBudget;
        $changePercent = round(($budgetChange / $currentBudget) * 100, 1);

        // Current spending breakdown (from historical data)
        $currentPmCost = PmCost::where('created_at', '>=', Carbon::now()->subDays(365))
            ->sum(DB::raw('labour_cost + parts_cost')) ?? ($currentBudget * 0.4);
        $currentWoCost = WoCost::where('created_at', '>=', Carbon::now()->subDays(365))
            ->sum(DB::raw('labour_cost + parts_cost')) ?? ($currentBudget * 0.5);
        $currentInventoryCost = $currentBudget * 0.1; // Estimate

        // If budget decrease, simulate cuts
        if ($budgetChange < 0) {
            $cutPercent = abs($changePercent);
            
            // Impact scenarios
            $pmReduction = $cutPercent * 0.8; // PM gets hit harder
            $woReduction = $cutPercent * 0.5; // WO is harder to cut
            
            // Estimate consequences
            $additionalBreakdowns = round($pmReduction * 0.3); // Each % PM cut = 0.3 additional breakdowns
            $increasedDowntime = $additionalBreakdowns * 120; // 120 min per breakdown
            $downtimeCost = $increasedDowntime * 100000; // Rp 100K/minute

            return [
                'success' => true,
                'scenario' => 'Budget Decrease',
                'budget_change' => [
                    'current' => 'Rp ' . number_format($currentBudget, 0, ',', '.'),
                    'proposed' => 'Rp ' . number_format($newBudget, 0, ',', '.'),
                    'difference' => 'Rp ' . number_format($budgetChange, 0, ',', '.'),
                    'change_percent' => $changePercent . '%',
                ],
                'impact_analysis' => [
                    'pm_budget_reduction' => $pmReduction . '%',
                    'expected_additional_breakdowns' => $additionalBreakdowns . ' per year',
                    'expected_additional_downtime' => $increasedDowntime . ' minutes per year',
                    'estimated_downtime_cost' => 'Rp ' . number_format($downtimeCost, 0, ',', '.'),
                    'net_impact' => 'Rp ' . number_format($budgetChange + $downtimeCost, 0, ',', '.'),
                ],
                'risk_assessment' => [
                    'risk_level' => $cutPercent > 20 ? 'high' : ($cutPercent > 10 ? 'medium' : 'low'),
                    'reliability_impact' => 'Equipment reliability expected to decrease by ' . round($pmReduction * 0.5) . '%',
                    'safety_risk' => $cutPercent > 25 ? 'Elevated - Safety inspections may be delayed' : 'Acceptable',
                ],
                'recommendations' => [
                    $cutPercent > 20 ? 'Not recommended - High risk of increased breakdowns and costs' : 'Proceed with caution',
                    'Prioritize critical equipment for available PM budget',
                    'Focus on predictive maintenance to optimize resource usage',
                    'Build contingency fund for emergency repairs',
                ],
            ];
        } else {
            // Budget increase - positive impact
            $additionalPm = round(($budgetChange / $currentBudget) * 20); // 20% more PM executions
            $expectedBreakdownReduction = round($additionalPm * 2); // Each additional PM prevents 2 breakdowns
            $downtimeSavings = $expectedBreakdownReduction * 120 * 100000;

            return [
                'success' => true,
                'scenario' => 'Budget Increase',
                'budget_change' => [
                    'current' => 'Rp ' . number_format($currentBudget, 0, ',', '.'),
                    'proposed' => 'Rp ' . number_format($newBudget, 0, ',', '.'),
                    'difference' => '+Rp ' . number_format($budgetChange, 0, ',', '.'),
                    'change_percent' => '+' . $changePercent . '%',
                ],
                'expected_benefits' => [
                    'additional_pm_executions' => '+' . $additionalPm . '%',
                    'expected_breakdown_reduction' => $expectedBreakdownReduction . ' fewer breakdowns',
                    'expected_downtime_savings' => 'Rp ' . number_format($downtimeSavings, 0, ',', '.'),
                    'roi_estimate' => round(($downtimeSavings / $budgetChange) * 100) . '%',
                ],
                'recommendations' => [
                    'Allocate 60% to increasing PM frequency on critical equipment',
                    'Invest 20% in predictive maintenance tools/training',
                    'Reserve 20% for spare parts inventory optimization',
                ],
            ];
        }
    }

    /**
     * Simulate staffing change impact
     */
    protected function simulateStaffingChange(array $parameters, int $period): array
    {
        $currentStaff = $parameters['current_staff'] ?? 5;
        $newStaff = $parameters['new_staff'] ?? 6;
        $staffChange = $newStaff - $currentStaff;

        // Calculate current workload
        $openWoCount = WorkOrder::whereNotIn('status', ['completed', 'closed', 'cancelled'])->count();
        $pmScheduleCount = PmSchedule::where('is_active', true)->count();
        
        $hoursPerWeekPerTech = 40;
        $currentCapacity = $currentStaff * $hoursPerWeekPerTech * 52;
        $newCapacity = $newStaff * $hoursPerWeekPerTech * 52;

        // Estimate workload
        $pmHoursPerYear = $pmScheduleCount * 12 * 2; // 12 executions per year, 2 hours each
        $woHoursPerYear = $openWoCount * 20 * 3; // 20 WO per month, 3 hours each
        $totalWorkload = $pmHoursPerYear + $woHoursPerYear;

        $currentUtilization = round(($totalWorkload / $currentCapacity) * 100);
        $newUtilization = round(($totalWorkload / $newCapacity) * 100);

        // Cost analysis
        $avgTechSalary = 6000000; // Rp 6M per month
        $annualCostChange = $staffChange * $avgTechSalary * 12;

        // Productivity impact
        $responseTimeImprovement = $staffChange > 0 ? round($staffChange * 15) : round($staffChange * 20);

        return [
            'success' => true,
            'scenario' => 'Staffing Change',
            'staffing_change' => [
                'current_staff' => $currentStaff,
                'proposed_staff' => $newStaff,
                'change' => ($staffChange > 0 ? '+' : '') . $staffChange,
            ],
            'capacity_analysis' => [
                'current_capacity_hours_year' => $currentCapacity,
                'new_capacity_hours_year' => $newCapacity,
                'estimated_workload_hours' => $totalWorkload,
                'current_utilization' => $currentUtilization . '%',
                'new_utilization' => $newUtilization . '%',
            ],
            'financial_impact' => [
                'annual_cost_change' => 'Rp ' . number_format($annualCostChange, 0, ',', '.'),
                'cost_per_work_hour' => 'Rp ' . number_format($avgTechSalary * 12 / ($hoursPerWeekPerTech * 52), 0, ',', '.'),
            ],
            'operational_impact' => [
                'wo_response_time_change' => ($responseTimeImprovement > 0 ? '-' : '+') . abs($responseTimeImprovement) . '%',
                'pm_compliance_impact' => $staffChange > 0 ? '+' . round($staffChange * 5) . '%' : round($staffChange * 8) . '%',
                'overtime_reduction' => $staffChange > 0 ? round($staffChange * 10) . '%' : 'Overtime may increase',
            ],
            'recommendations' => $staffChange > 0 
                ? [
                    'Hire technicians with skills matching current gaps',
                    'Implement mentorship program for new hires',
                    'Distribute workload evenly across team',
                ]
                : [
                    'Prioritize PM on critical equipment',
                    'Consider outsourcing low-priority tasks',
                    'Implement efficiency improvements before reduction',
                ],
        ];
    }

    /**
     * Simulate equipment shutdown impact
     */
    protected function simulateShutdownImpact(?int $equipmentId, array $parameters): array
    {
        $shutdownDays = $parameters['shutdown_days'] ?? 7;
        $shutdownReason = $parameters['reason'] ?? 'maintenance';

        if (!$equipmentId) {
            return ['error' => 'Equipment ID is required for shutdown simulation'];
        }

        $equipment = SubAsset::find($equipmentId);
        if (!$equipment) {
            return ['error' => 'Equipment not found'];
        }

        // Get equipment utilization data
        $avgDailyRunningHours = RunningHour::where('asset_id', $equipmentId)
            ->where('recorded_at', '>=', Carbon::now()->subDays(30))
            ->avg('hours') ?? 16;

        // Calculate production impact
        $totalDowntimeHours = $shutdownDays * $avgDailyRunningHours;
        $productionLossRate = 100000; // Rp 100K per hour (simplified)
        $estimatedProductionLoss = $totalDowntimeHours * $productionLossRate;

        // Get pending WOs for this equipment
        $pendingWos = WorkOrder::where('sub_asset_id', $equipmentId)
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->count();

        // Estimate maintenance that can be done during shutdown
        $maintenanceTasks = [
            'Major PM execution',
            'Pending corrective repairs (' . $pendingWos . ' WOs)',
            'Calibration and alignment',
            'Safety inspections',
            'Wear parts replacement',
        ];

        $estimatedMaintenanceCost = $shutdownDays * 2000000; // Rp 2M per day for comprehensive work

        return [
            'success' => true,
            'scenario' => 'Equipment Shutdown',
            'equipment' => [
                'name' => $equipment->name,
                'id' => $equipmentId,
                'average_daily_runtime' => round($avgDailyRunningHours, 1) . ' hours',
            ],
            'shutdown_details' => [
                'duration_days' => $shutdownDays,
                'reason' => $shutdownReason,
                'total_downtime_hours' => round($totalDowntimeHours, 1),
            ],
            'impact_analysis' => [
                'estimated_production_loss' => 'Rp ' . number_format($estimatedProductionLoss, 0, ',', '.'),
                'pending_work_orders' => $pendingWos,
                'estimated_maintenance_cost' => 'Rp ' . number_format($estimatedMaintenanceCost, 0, ',', '.'),
            ],
            'opportunity_cost' => [
                'total_cost' => 'Rp ' . number_format($estimatedProductionLoss + $estimatedMaintenanceCost, 0, ',', '.'),
                'cost_per_day' => 'Rp ' . number_format(($estimatedProductionLoss + $estimatedMaintenanceCost) / $shutdownDays, 0, ',', '.'),
            ],
            'recommended_tasks' => $maintenanceTasks,
            'recommendations' => [
                'Plan shutdown during low-demand period if possible',
                'Pre-order all required spare parts',
                'Assign dedicated team for maximum efficiency',
                'Complete all pending WOs during shutdown',
                'Document all work for future reference',
            ],
        ];
    }

    /**
     * Send AI briefing via WhatsApp
     */
    public function sendWhatsAppBriefing(array $params): array
    {
        $briefingType = $params['type'] ?? 'daily';
        $recipientGroup = $params['recipient_group'] ?? 'default';

        // Generate briefing
        $briefing = $this->generateMaintenanceBriefing(['type' => $briefingType]);

        if (!$briefing['success']) {
            return ['success' => false, 'message' => 'Failed to generate briefing'];
        }

        // Format message for WhatsApp
        $message = $this->formatWhatsAppBriefing($briefing);

        // Send via WhatsApp service
        try {
            $whatsAppService = app(WhatsAppService::class);
            $sent = $whatsAppService->sendMessage($message);

            return [
                'success' => $sent,
                'message' => $sent ? 'Briefing sent successfully via WhatsApp' : 'Failed to send WhatsApp message',
                'briefing_type' => $briefingType,
                'timestamp' => Carbon::now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'WhatsApp service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format briefing for WhatsApp message
     */
    protected function formatWhatsAppBriefing(array $briefing): string
    {
        $type = strtoupper($briefing['briefing_type'] ?? 'DAILY');
        $period = $briefing['period_display'] ?? date('d M Y');

        $message = "📊 *{$type} MAINTENANCE BRIEFING*\n";
        $message .= "📅 {$period}\n\n";

        // Critical Alerts
        $alerts = $briefing['critical_alerts'] ?? [];
        $message .= "🚨 *CRITICAL ALERTS*\n";
        $message .= "• Total: " . ($alerts['total_count'] ?? 0) . "\n";
        $message .= "• Critical: " . ($alerts['critical_count'] ?? 0) . "\n";
        $message .= "• High: " . ($alerts['high_count'] ?? 0) . "\n\n";

        // Work Orders
        $wo = $briefing['work_orders'] ?? [];
        $message .= "📋 *WORK ORDERS*\n";
        $message .= "• New: " . ($wo['new_count'] ?? 0) . "\n";
        $message .= "• Open: " . ($wo['open_total'] ?? 0) . "\n";
        $message .= "• Completed: " . ($wo['completed_count'] ?? 0) . "\n\n";

        // PM Status
        $pm = $briefing['preventive_maintenance'] ?? [];
        $message .= "🔧 *PM STATUS*\n";
        $message .= "• Executed: " . ($pm['executed_count'] ?? 0) . "\n";
        $message .= "• Compliance: " . ($pm['compliance_rate'] ?? '0%') . "\n";
        $message .= "• Upcoming (7d): " . ($pm['upcoming_count'] ?? 0) . "\n\n";

        // Equipment Status
        $eq = $briefing['equipment_status'] ?? [];
        $message .= "⚙️ *EQUIPMENT*\n";
        $message .= "• Active: " . ($eq['total_active'] ?? 0) . "\n";
        $message .= "• With Issues: " . ($eq['with_issues'] ?? 0) . "\n";
        $message .= "• Availability: " . ($eq['availability_rate'] ?? '100%') . "\n\n";

        // Top Recommendations
        $recs = array_slice($briefing['recommendations'] ?? [], 0, 3);
        if (!empty($recs)) {
            $message .= "💡 *TOP ACTIONS*\n";
            foreach ($recs as $rec) {
                $icon = match ($rec['priority'] ?? 'medium') {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟡',
                    default => '🟢',
                };
                $message .= "{$icon} " . ($rec['message'] ?? $rec['action'] ?? 'Action required') . "\n";
            }
        }

        $message .= "\n✅ Generated by CMMS AI Assistant";

        return $message;
    }
}

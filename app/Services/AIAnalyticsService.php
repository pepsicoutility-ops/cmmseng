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
}

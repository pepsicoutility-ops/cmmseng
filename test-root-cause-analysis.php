<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================\n";
echo "ROOT CAUSE ANALYSIS - Testing\n";
echo "=================================================\n\n";

// Test 1: Analyze equipment with troubles (Chiller 1 - ID 1)
echo "TEST 1: Analyze Chiller 1 (ID: 1)\n";
echo "-------------------------------------------------\n";

$analyticsService = new App\Services\AIAnalyticsService();

$result = $analyticsService->analyzeRootCause([
    'equipment_id' => 1,
    'analysis_period' => 90,
    'trouble_threshold' => 3,
]);

if ($result['success']) {
    if (isset($result['insufficient_data'])) {
        echo "⚠️  Insufficient data: {$result['message']}\n";
    } else {
        echo "✅ Analysis completed successfully!\n\n";
        
        echo "EQUIPMENT: {$result['equipment_name']}\n";
        echo "PERIOD: {$result['analysis_period_text']}\n";
        echo "DATE RANGE: {$result['date_range']['start']} to {$result['date_range']['end']}\n\n";
        
        echo "TROUBLE SUMMARY:\n";
        echo "- Total troubles: {$result['trouble_summary']['total_troubles']}\n";
        echo "- Average per week: {$result['trouble_summary']['average_per_week']}\n";
        echo "- Average per month: {$result['trouble_summary']['average_per_month']}\n";
        echo "- Trend: {$result['trouble_summary']['trend']} ({$result['trouble_summary']['trend_percentage']}%)\n\n";
        
        echo "TIMING PATTERNS:\n";
        if (!empty($result['timing_patterns']['by_shift'])) {
            echo "By Shift:\n";
            foreach ($result['timing_patterns']['by_shift'] as $shift => $data) {
                echo "  - {$shift}: {$data['count']} incidents ({$data['percentage']}%)\n";
            }
        }
        if (!empty($result['timing_patterns']['by_day_of_week'])) {
            echo "By Day of Week:\n";
            arsort($result['timing_patterns']['by_day_of_week']);
            $topDays = array_slice($result['timing_patterns']['by_day_of_week'], 0, 3, true);
            foreach ($topDays as $day => $data) {
                echo "  - {$day}: {$data['count']} incidents ({$data['percentage']}%)\n";
            }
        }
        if (isset($result['timing_patterns']['peak_time'])) {
            echo "Peak Time: {$result['timing_patterns']['peak_time']}\n";
        }
        echo "\n";
        
        echo "ISSUE TYPE PATTERNS:\n";
        if (!empty($result['issue_type_patterns'])) {
            foreach ($result['issue_type_patterns'] as $category => $data) {
                echo "- {$category}: {$data['count']} incidents ({$data['percentage']}%)\n";
                if (!empty($data['examples'])) {
                    echo "  Examples:\n";
                    foreach (array_slice($data['examples'], 0, 2) as $example) {
                        echo "  * {$example}\n";
                    }
                }
            }
        }
        echo "\n";
        
        echo "CORRELATION ANALYSIS:\n";
        if (isset($result['correlation_analysis']['pm_compliance'])) {
            $pm = $result['correlation_analysis']['pm_compliance'];
            echo "- PM Compliance: {$pm['rate']}% (Target: {$pm['target']}%) - Status: {$pm['status']}\n";
            echo "  Scheduled: {$pm['scheduled']}, Completed: {$pm['completed']}\n";
        }
        if (isset($result['correlation_analysis']['last_major_pm'])) {
            $lastPm = $result['correlation_analysis']['last_major_pm'];
            if ($lastPm['date']) {
                echo "- Last Major PM: {$lastPm['date']} ({$lastPm['days_ago']} days ago)";
                echo $lastPm['overdue'] ? " ⚠️ OVERDUE\n" : " ✅\n";
            } else {
                echo "- Last Major PM: Not found ⚠️\n";
            }
        }
        if (isset($result['correlation_analysis']['work_orders'])) {
            $wo = $result['correlation_analysis']['work_orders'];
            echo "- Work Orders: {$wo['total']} total, {$wo['emergency']} emergency ({$wo['emergency_rate']}%)\n";
        }
        echo "\n";
        
        echo "ROOT CAUSES IDENTIFIED:\n";
        foreach ($result['root_causes'] as $index => $cause) {
            $severityEmoji = match($cause['severity']) {
                'primary' => '🔴',
                'secondary' => '🟡',
                'tertiary' => '🟢',
                default => '⚪',
            };
            echo "{$severityEmoji} " . strtoupper($cause['severity']) . ": {$cause['cause']}\n";
            echo "   Confidence: " . strtoupper($cause['confidence']) . "\n";
            echo "   Evidence:\n";
            foreach ($cause['evidence'] as $evidence) {
                echo "   - {$evidence}\n";
            }
            echo "\n";
        }
        
        echo "RECOMMENDATIONS:\n";
        foreach ($result['recommendations'] as $rec) {
            $urgencyEmoji = match($rec['urgency']) {
                'urgent' => '🚨',
                'high' => '⚠️',
                'medium' => '📋',
                default => '💡',
            };
            echo "{$urgencyEmoji} PRIORITY {$rec['priority']}: {$rec['action']} (Urgency: {$rec['urgency']})\n";
            echo "   Timeline: {$rec['timeline']}\n";
            echo "   Details:\n";
            foreach ($rec['details'] as $detail) {
                echo "   - {$detail}\n";
            }
            echo "\n";
        }
        
        echo "IMPACT ESTIMATE:\n";
        echo "- Current troubles per quarter: {$result['impact_estimate']['current_troubles_per_quarter']}\n";
        echo "- Estimated after fix: {$result['impact_estimate']['estimated_troubles_after_fix']}\n";
        echo "- Reduction: {$result['impact_estimate']['reduction_percentage']}% ({$result['impact_estimate']['troubles_avoided']} troubles avoided)\n";
        echo "- Cost savings: {$result['impact_estimate']['estimated_cost_savings_formatted']}\n";
        echo "- Downtime reduction: {$result['impact_estimate']['total_downtime_reduction_hours']} hours\n";
    }
} else {
    echo "❌ Error: {$result['message']}\n";
}

echo "\n=================================================\n\n";

// Test 2: Try analyzing equipment with insufficient data
echo "TEST 2: Analyze equipment with insufficient data (ID: 5)\n";
echo "-------------------------------------------------\n";

$result2 = $analyticsService->analyzeRootCause([
    'equipment_id' => 5,
    'analysis_period' => 30,
    'trouble_threshold' => 3,
]);

if ($result2['success']) {
    if (isset($result2['insufficient_data'])) {
        echo "⚠️  Expected: {$result2['message']}\n";
        echo "    Equipment: {$result2['equipment_name']}\n";
        echo "    Troubles found: {$result2['trouble_count']}\n";
        echo "    Threshold: {$result2['threshold']}\n";
    } else {
        echo "✅ Has sufficient data for analysis\n";
    }
} else {
    echo "❌ Error: {$result2['message']}\n";
}

echo "\n=================================================\n\n";

// Test 3: Integration test with AI function calling
echo "TEST 3: Integration with AI function calling\n";
echo "-------------------------------------------------\n";

$toolDefinitions = App\Services\AIToolsService::getToolDefinitions();
$hasRootCause = false;

foreach ($toolDefinitions as $tool) {
    if ($tool['function']['name'] === 'analyze_root_cause') {
        $hasRootCause = true;
        echo "✅ Function 'analyze_root_cause' found in tool definitions\n";
        echo "   Description: {$tool['function']['description']}\n";
        echo "   Parameters: " . json_encode($tool['function']['parameters']['properties'], JSON_PRETTY_PRINT) . "\n";
        break;
    }
}

if (!$hasRootCause) {
    echo "❌ Function 'analyze_root_cause' NOT found in tool definitions\n";
}

echo "\n";

// Test execution via AIToolsService
echo "Testing execution via AIToolsService:\n";
$executionResult = App\Services\AIToolsService::executeTool('analyze_root_cause', [
    'equipment_id' => 1,
    'analysis_period' => 90,
]);

if (isset($executionResult['success']) && $executionResult['success']) {
    echo "✅ Successfully executed via AIToolsService\n";
    echo "   Equipment: {$executionResult['equipment_name']}\n";
    
    if (isset($executionResult['insufficient_data'])) {
        echo "   Status: Insufficient data ({$executionResult['trouble_count']} troubles)\n";
    } else {
        echo "   Troubles analyzed: {$executionResult['trouble_summary']['total_troubles']}\n";
    }
} else {
    echo "❌ Execution failed\n";
    if (isset($executionResult['error'])) {
        echo "   Error: {$executionResult['error']}\n";
    }
}

echo "\n=================================================\n";
echo "ROOT CAUSE ANALYSIS TEST COMPLETE\n";
echo "=================================================\n";

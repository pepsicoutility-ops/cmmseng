<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================\n";
echo "COST OPTIMIZATION ANALYSIS - Testing\n";
echo "=================================================\n\n";

$analyticsService = new App\Services\AIAnalyticsService();

// Test 1: Full cost optimization analysis (90 days)
echo "TEST 1: Cost Optimization Analysis (Last 90 Days)\n";
echo "-------------------------------------------------\n";

$result = $analyticsService->analyzeCostOptimization([
    'period' => 90,
    'cost_threshold' => 100000,
    'include_opportunities' => true,
]);

if ($result['success']) {
    echo "✅ Analysis completed successfully!\n\n";
    
    echo "ANALYSIS PERIOD: {$result['analysis_period_text']}\n";
    echo "DATE RANGE: {$result['date_range']['start']} to {$result['date_range']['end']}\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "SPENDING SUMMARY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Total Cost: {$result['spending_summary']['total_cost_formatted']}\n\n";
    
    echo "Breakdown:\n";
    foreach ($result['spending_summary']['breakdown'] as $category => $data) {
        $categoryName = ucwords(str_replace('_', ' ', $category));
        echo "- {$categoryName}: {$data['total_formatted']} ({$data['percentage']}%)\n";
        if (isset($data['labour']) && isset($data['parts'])) {
            echo "  Labour: Rp " . number_format($data['labour'], 0, ',', '.') . 
                 " | Parts: Rp " . number_format($data['parts'], 0, ',', '.') . "\n";
        }
    }
    
    echo "\nLabour vs Parts:\n";
    echo "- Labour: {$result['spending_summary']['labour_vs_parts']['labour_percentage']}%\n";
    echo "- Parts: {$result['spending_summary']['labour_vs_parts']['parts_percentage']}%\n";
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "COST DRIVERS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if (!empty($result['cost_drivers'])) {
        foreach ($result['cost_drivers'] as $key => $driver) {
            $severityEmoji = match($driver['severity']) {
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🟢',
                default => '⚪',
            };
            
            echo "{$severityEmoji} {$driver['category']}\n";
            echo "   Total Cost: {$driver['total_cost_formatted']}\n";
            
            if (isset($driver['count'])) {
                echo "   Count: {$driver['count']}";
                if (isset($driver['average_cost'])) {
                    echo " | Avg: Rp " . number_format($driver['average_cost'], 0, ',', '.');
                }
                echo "\n";
            }
            
            if (isset($driver['percentage_of_labour'])) {
                echo "   Percentage of Labour: {$driver['percentage_of_labour']}%\n";
            }
            
            if (isset($driver['equipment_count'])) {
                echo "   Equipment Affected: {$driver['equipment_count']}\n";
            }
            
            echo "\n";
        }
    } else {
        echo "No significant cost drivers found above threshold.\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "OPTIMIZATION OPPORTUNITIES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if (!empty($result['opportunities'])) {
        foreach ($result['opportunities'] as $index => $opp) {
            $confidenceStars = str_repeat('⭐', match($opp['confidence']) {
                'high' => 4,
                'medium' => 3,
                'low' => 2,
                default => 1,
            });
            
            echo "\n💡 OPPORTUNITY #" . ($index + 1) . ": {$opp['title']}\n";
            echo "   {$opp['description']}\n\n";
            
            if ($opp['current_cost'] > 0) {
                echo "   Current Cost: Rp " . number_format($opp['current_cost'], 0, ',', '.') . "\n";
            }
            echo "   Potential Savings: {$opp['potential_savings_formatted']}\n";
            echo "   Confidence: {$opp['confidence']} {$confidenceStars}\n";
            echo "   Difficulty: {$opp['difficulty']}\n";
            echo "   Timeline: {$opp['timeline']}\n\n";
            
            echo "   Actions:\n";
            foreach ($opp['actions'] as $action) {
                echo "   • {$action}\n";
            }
            echo "\n";
        }
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "TOTAL POTENTIAL SAVINGS: {$result['total_potential_savings_formatted']}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    } else {
        echo "No specific optimization opportunities identified.\n";
        echo "Current spending is within acceptable range.\n";
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "IMPLEMENTATION PLAN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if (!empty($result['implementation_plan']['phases'])) {
        echo "Total Duration: {$result['implementation_plan']['total_duration']}\n\n";
        
        foreach ($result['implementation_plan']['phases'] as $phase) {
            echo "{$phase['phase_name']}\n";
            echo "Expected Savings: {$phase['total_savings_formatted']}\n";
            echo "Initiatives:\n";
            foreach ($phase['opportunities'] as $opp) {
                echo "  • {$opp['title']} - {$opp['savings']} (Confidence: {$opp['confidence']})\n";
            }
            echo "\n";
        }
    } else {
        echo "No implementation plan needed at this time.\n";
    }
    
} else {
    echo "❌ Error: {$result['message']}\n";
}

echo "\n=================================================\n\n";

// Test 2: Short period analysis (30 days)
echo "TEST 2: Cost Analysis (Last 30 Days)\n";
echo "-------------------------------------------------\n";

$result2 = $analyticsService->analyzeCostOptimization([
    'period' => 30,
    'cost_threshold' => 50000,
]);

if ($result2['success']) {
    echo "✅ Analysis completed\n";
    echo "Total Cost: {$result2['spending_summary']['total_cost_formatted']}\n";
    echo "Opportunities Found: " . count($result2['opportunities']) . "\n";
    echo "Potential Savings: {$result2['total_potential_savings_formatted']}\n";
} else {
    echo "❌ Error: {$result2['message']}\n";
}

echo "\n=================================================\n\n";

// Test 3: Integration test with AI function calling
echo "TEST 3: Integration with AI Function Calling\n";
echo "-------------------------------------------------\n";

$toolDefinitions = App\Services\AIToolsService::getToolDefinitions();
$hasCostOpt = false;

foreach ($toolDefinitions as $tool) {
    if ($tool['function']['name'] === 'analyze_cost_optimization') {
        $hasCostOpt = true;
        echo "✅ Function 'analyze_cost_optimization' found in tool definitions\n";
        echo "   Description: {$tool['function']['description']}\n";
        break;
    }
}

if (!$hasCostOpt) {
    echo "❌ Function 'analyze_cost_optimization' NOT found in tool definitions\n";
}

echo "\n";

// Test execution via AIToolsService
echo "Testing execution via AIToolsService:\n";
$executionResult = App\Services\AIToolsService::executeTool('analyze_cost_optimization', [
    'period' => 90,
]);

if (isset($executionResult['success']) && $executionResult['success']) {
    echo "✅ Successfully executed via AIToolsService\n";
    echo "   Total Cost: {$executionResult['spending_summary']['total_cost_formatted']}\n";
    echo "   Opportunities: " . count($executionResult['opportunities']) . "\n";
    echo "   Potential Savings: {$executionResult['total_potential_savings_formatted']}\n";
} else {
    echo "❌ Execution failed\n";
    if (isset($executionResult['error'])) {
        echo "   Error: {$executionResult['error']}\n";
    }
}

echo "\n=================================================\n";
echo "COST OPTIMIZATION ANALYSIS TEST COMPLETE\n";
echo "=================================================\n";

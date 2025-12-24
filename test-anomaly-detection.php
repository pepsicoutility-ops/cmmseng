<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AIAnalyticsService;
use App\Services\AIToolsService;
use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
Facade::setFacadeApplication($app);

echo "=================================================\n";
echo "ANOMALY DETECTION - Testing\n";
echo "=================================================\n\n";

// TEST 1: Detect anomalies in Compressor 1
echo "TEST 1: Detect Anomalies - Compressor 1 (Medium Sensitivity)\n";
echo "-------------------------------------------------\n";

try {
    $service = new AIAnalyticsService();
    $result = $service->detectAnomalies([
        'equipment_type' => 'compressor1',
        'sensitivity' => 'medium',
        'lookback_days' => 90,
        'recent_days' => 7,
    ]);

    if ($result['success']) {
        echo "✅ Analysis completed successfully!\n\n";
        
        echo "ANALYSIS PERIOD: {$result['analysis_period']['baseline_days']} days baseline, {$result['analysis_period']['recent_days']} days recent\n";
        echo "DATE RANGE: {$result['analysis_period']['baseline_start']} to {$result['analysis_period']['current_date']}\n";
        echo "SENSITIVITY: {$result['sensitivity']} (z-score threshold: {$result['threshold_zscore']})\n\n";
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "SUMMARY\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔴 Critical: {$result['summary']['critical']}\n";
        echo "🟡 Warning: {$result['summary']['warning']}\n";
        echo "🟢 Info: {$result['summary']['info']}\n";
        echo "Total Anomalies: {$result['summary']['total']}\n\n";
        
        if ($result['summary']['total'] > 0) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "ANOMALIES DETECTED\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            foreach ($result['anomalies'] as $idx => $anomaly) {
                $icon = match($anomaly['severity']) {
                    'critical' => '🔴',
                    'warning' => '🟡',
                    'info' => '🟢',
                    default => '⚪',
                };
                
                echo "{$icon} ANOMALY #" . ($idx + 1) . ": {$anomaly['parameter']}\n";
                echo "Equipment: {$anomaly['equipment_type']}\n";
                echo "Status: {$anomaly['status']}\n";
                echo "Severity: " . strtoupper($anomaly['severity']) . "\n\n";
                
                echo "Values:\n";
                echo "- Current: {$anomaly['current_value']} {$anomaly['unit']}\n";
                echo "- Normal Range: {$anomaly['normal_range']['min']} - {$anomaly['normal_range']['max']} {$anomaly['unit']}\n";
                echo "- Mean: {$anomaly['mean']} {$anomaly['unit']}\n";
                echo "- Deviation: {$anomaly['deviation_percent']}%\n";
                echo "- Z-Score: {$anomaly['z_score']}\n\n";
                
                echo "Trend:\n";
                echo "- Direction: {$anomaly['trend']['direction']}\n";
                echo "- Change: {$anomaly['trend']['change']} {$anomaly['unit']} ({$anomaly['trend']['percentage']}%)\n";
                echo "- From: {$anomaly['trend']['first_value']} → To: {$anomaly['trend']['last_value']}\n\n";
                
                echo "Recent Readings:\n";
                foreach ($anomaly['recent_readings'] as $reading) {
                    echo "  • {$reading['date']}: {$reading['value']} {$anomaly['unit']}\n";
                }
                echo "\n";
                
                echo "Risk Assessment:\n";
                echo "- Level: " . strtoupper($anomaly['risk_assessment']['level']) . "\n";
                echo "- Breakdown Probability: {$anomaly['risk_assessment']['breakdown_probability']}%\n";
                echo "- Timeframe: {$anomaly['risk_assessment']['timeframe']}\n";
                echo "- Est. Downtime: {$anomaly['risk_assessment']['estimated_downtime']}\n";
                echo "- Est. Cost: {$anomaly['risk_assessment']['estimated_cost']}\n\n";
                
                echo "Recommendations:\n";
                foreach ($anomaly['recommendations'] as $rec) {
                    echo "  • {$rec}\n";
                }
                echo "\n";
                echo "Confidence: {$anomaly['confidence']}%\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            }
        } else {
            echo "✅ No anomalies detected - All parameters within normal range\n\n";
        }
    } else {
        echo "❌ Analysis failed: {$result['message']}\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n\n";
}

echo "=================================================\n\n";

// TEST 2: Detect anomalies - ALL Equipment (High Sensitivity)
echo "TEST 2: Detect Anomalies - All Equipment (High Sensitivity)\n";
echo "-------------------------------------------------\n";

try {
    $service = new AIAnalyticsService();
    $result = $service->detectAnomalies([
        'sensitivity' => 'high',
        'lookback_days' => 60,
        'recent_days' => 3,
    ]);

    if ($result['success']) {
        echo "✅ Analysis completed\n";
        echo "Anomalies Found: {$result['summary']['total']}\n";
        echo "- Critical: {$result['summary']['critical']}\n";
        echo "- Warning: {$result['summary']['warning']}\n";
        echo "- Info: {$result['summary']['info']}\n\n";
        
        if ($result['summary']['total'] > 0) {
            echo "Top 3 Anomalies:\n";
            foreach (array_slice($result['anomalies'], 0, 3) as $idx => $anomaly) {
                echo ($idx + 1) . ". {$anomaly['equipment_type']} - {$anomaly['parameter']}: {$anomaly['current_value']} {$anomaly['unit']} (Severity: {$anomaly['severity']})\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=================================================\n\n";

// TEST 3: Integration with AI Function Calling
echo "TEST 3: Integration with AI Function Calling\n";
echo "-------------------------------------------------\n";

try {
    // Get tool definitions
    $tools = AIToolsService::getToolDefinitions();
    $anomalyTool = null;
    
    foreach ($tools as $tool) {
        if (isset($tool['function']['name']) && $tool['function']['name'] === 'detect_anomalies') {
            $anomalyTool = $tool;
            break;
        }
    }
    
    if ($anomalyTool) {
        echo "✅ Function 'detect_anomalies' found in tool definitions\n";
        echo "   Description: {$anomalyTool['function']['description']}\n\n";
        
        // Test execution
        echo "Testing execution via AIToolsService:\n";
        $result = AIToolsService::executeTool('detect_anomalies', [
            'equipment_type' => 'compressor2',
            'sensitivity' => 'medium',
        ]);
        
        if (isset($result['success']) && $result['success']) {
            echo "✅ Successfully executed via AIToolsService\n";
            echo "   Anomalies: {$result['summary']['total']}\n";
            if ($result['summary']['critical'] > 0) {
                echo "   ⚠️ CRITICAL anomalies detected: {$result['summary']['critical']}\n";
            }
        } else {
            echo "⚠️ Execution returned with issues\n";
            if (isset($result['error'])) {
                echo "   Error: {$result['error']}\n";
            }
        }
    } else {
        echo "❌ Function 'detect_anomalies' NOT found in tool definitions\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=================================================\n";
echo "ANOMALY DETECTION TEST COMPLETE\n";
echo "=================================================\n";

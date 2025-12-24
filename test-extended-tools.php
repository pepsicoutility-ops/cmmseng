<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AIToolsService;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing AI Extended Tools ===\n\n";

// Test 1: Get Areas List
echo "1. Testing get_areas_list...\n";
$result = AIToolsService::executeTool('get_areas_list', []);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: Search Parts
echo "2. Testing search_parts (search: 'bearing')...\n";
$result = AIToolsService::executeTool('search_parts', ['search' => 'bearing']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: Get Inventory Stock
echo "3. Testing get_inventory_stock (low stock only)...\n";
$result = AIToolsService::executeTool('get_inventory_stock', ['low_stock_only' => true]);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 4: Get Stock Alerts
echo "4. Testing get_stock_alerts...\n";
$result = AIToolsService::executeTool('get_stock_alerts', []);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 5: Get PM Schedules
echo "5. Testing get_pm_schedules...\n";
$result = AIToolsService::executeTool('get_pm_schedules', ['status' => 'active']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 6: Get PM Compliance
echo "6. Testing get_pm_compliance (monthly)...\n";
$result = AIToolsService::executeTool('get_pm_compliance', ['period' => 'month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 7: Get WO Statistics
echo "7. Testing get_wo_statistics (monthly)...\n";
$result = AIToolsService::executeTool('get_wo_statistics', ['period' => 'month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 8: Get Maintenance Costs
echo "8. Testing get_maintenance_costs...\n";
$result = AIToolsService::executeTool('get_maintenance_costs', ['period' => 'month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 9: Get Technician Workload
echo "9. Testing get_technician_workload...\n";
$result = AIToolsService::executeTool('get_technician_workload', []);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 10: Get Top Issues
echo "10. Testing get_top_issues (top 5)...\n";
$result = AIToolsService::executeTool('get_top_issues', ['limit' => 5, 'period' => 'month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== Testing Complete ===\n";

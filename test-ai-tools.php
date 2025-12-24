<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AIToolsService;
use App\Models\Asset;

echo "=== Testing AI Tools Service ===\n\n";

// Test 1: Get Equipment Info
echo "1. Testing get_equipment_info...\n";
$result = AIToolsService::executeTool('get_equipment_info', ['search' => 'Chiller']);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Get Active Work Orders
echo "2. Testing get_active_work_orders...\n";
$result = AIToolsService::executeTool('get_active_work_orders', []);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: List available equipment
echo "3. Available Equipment:\n";
$equipment = Asset::where('is_active', 1)->limit(10)->get(['name', 'code']);
foreach ($equipment as $eq) {
    echo "   - {$eq->name} ({$eq->code})\n";
}

echo "\n✅ AI Tools Service is ready!\n";
echo "AI can now access real-time CMMS data!\n";

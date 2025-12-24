<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AIToolsService;

echo "=== Testing Checklist Data Function ===\n\n";

// Test 1: Get Compressor 1 data
echo "1. Get Compressor 1 Checklist Data:\n";
$result = AIToolsService::executeTool('get_checklist_data', [
    'equipment_type' => 'compressor1',
    'limit' => 5
]);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: Get Chiller 1 data
echo "2. Get Chiller 1 Checklist Data:\n";
$result = AIToolsService::executeTool('get_checklist_data', [
    'equipment_type' => 'chiller1',
    'limit' => 3
]);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: Get Compressor 1 data filtered by shift
echo "3. Get Compressor 1 Checklist Data (Shift 1):\n";
$result = AIToolsService::executeTool('get_checklist_data', [
    'equipment_type' => 'compressor1',
    'limit' => 5,
    'shift' => 1
]);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "✅ Checklist function is ready!\n";

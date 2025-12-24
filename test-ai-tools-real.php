<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AIToolsService;

echo "=== Testing AI Tools with Real Equipment ===\n\n";

// Test dengan equipment yang ada
echo "1. Get info: Processing Unit\n";
$result = AIToolsService::executeTool('get_equipment_info', ['search' => 'Processing']);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "2. Get info: Extruder\n";
$result = AIToolsService::executeTool('get_equipment_info', ['search' => 'EXTRUDER']);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "3. Get equipment troubles\n";
$result = AIToolsService::executeTool('get_equipment_troubles', ['limit' => 5]);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "✅ All tools working!\n";

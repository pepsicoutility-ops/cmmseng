<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AIExcelService;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Excel Export Function ===\n\n";

// Test 1: Work Orders this month
echo "1. Generating Work Orders report (this month)...\n";
$result = AIExcelService::generateReport('work_orders', ['period' => 'this_month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: Compressor 1 Checklist
echo "2. Generating Compressor 1 Checklist report (this month)...\n";
$result = AIExcelService::generateReport('compressor1_checklist', ['period' => 'this_month']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: PM Executions this year
echo "3. Generating PM Executions report (this year)...\n";
$result = AIExcelService::generateReport('pm_executions', ['period' => 'this_year']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== Test Complete ===\n";

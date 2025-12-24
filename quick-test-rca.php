<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\AIAnalyticsService();
$result = $service->analyzeRootCause([
    'equipment_id' => 6,
    'analysis_period' => 90,
    'trouble_threshold' => 3,
]);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

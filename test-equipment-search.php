<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing get_equipment_info for 'Compressor 1':\n\n";

$result = App\Services\AIToolsService::executeTool('get_equipment_info', [
    'search' => 'Compressor 1'
]);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

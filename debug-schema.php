<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tools = App\Services\AIToolsService::getToolDefinitions();

// Check get_areas_list specifically
foreach ($tools as $tool) {
    if ($tool['function']['name'] === 'get_areas_list') {
        echo "Found get_areas_list:\n";
        echo json_encode($tool['function']['parameters'], JSON_PRETTY_PRINT);
        echo "\n\n";
        
        if (!isset($tool['function']['parameters']['properties'])) {
            echo "ERROR: properties is missing!\n";
        } elseif (is_array($tool['function']['parameters']['properties']) && empty($tool['function']['parameters']['properties'])) {
            echo "properties is empty array []\n";
        } elseif (is_object($tool['function']['parameters']['properties'])) {
            echo "properties is empty object (stdClass)\n";
        }
        break;
    }
}

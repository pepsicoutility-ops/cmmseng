<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Validating Function Schemas ===\n\n";

$tools = App\Services\AIToolsService::getToolDefinitions();

echo "Total tools: " . count($tools) . "\n\n";

$errors = [];
foreach ($tools as $tool) {
    $name = $tool['function']['name'];
    $params = $tool['function']['parameters'];
    
    // Check required fields
    $hasType = isset($params['type']);
    $hasProperties = isset($params['properties']);
    $hasRequired = isset($params['required']);
    
    $status = ($hasType && $hasProperties && $hasRequired) ? '✅' : '❌';
    
    echo "{$status} {$name}\n";
    
    if (!$hasType) {
        echo "   - Missing: type\n";
        $errors[] = $name;
    }
    if (!$hasProperties) {
        echo "   - Missing: properties\n";
        $errors[] = $name;
    }
    if (!$hasRequired) {
        echo "   - Missing: required\n";
        $errors[] = $name;
    }
}

echo "\n";
if (empty($errors)) {
    echo "✅ All schemas are valid!\n";
} else {
    echo "❌ Found errors in: " . implode(', ', array_unique($errors)) . "\n";
}

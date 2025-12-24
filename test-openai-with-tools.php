<?php

require_once __DIR__ . '/vendor/autoload.php';

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing OpenAI API with Updated Schemas ===\n\n";

try {
    $tools = App\Services\AIToolsService::getToolDefinitions();
    
    echo "Total tools loaded: " . count($tools) . "\n\n";
    
    echo "Sending test request to OpenAI...\n";
    
    $response = OpenAI::chat()->create([
        'model' => config('openai.model'),
        'messages' => [
            ['role' => 'user', 'content' => 'Test message'],
        ],
        'tools' => $tools,
    ]);
    
    echo "✅ SUCCESS!\n";
    echo "Response: " . $response->choices[0]->message->content . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString() . "\n";
}

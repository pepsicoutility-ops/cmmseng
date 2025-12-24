<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Http;

echo "Testing OpenAI Connection...\n";
echo "API Key: " . substr(config('services.openai.api_key'), 0, 10) . "...\n";
echo "Model: " . config('services.openai.model') . "\n\n";

// Test with direct HTTP request to see actual response
echo "=== Testing with HTTP Client ===\n";
try {
    $apiKey = config('services.openai.api_key');
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => 'Say test']
        ],
        'max_tokens' => 10
    ]);
    
    echo "HTTP Status: " . $response->status() . "\n";
    echo "Response Body:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->successful()) {
        echo "✅ API Key is working!\n";
        $data = $response->json();
        if (isset($data['choices'][0]['message']['content'])) {
            echo "Response: " . $data['choices'][0]['message']['content'] . "\n";
        }
    } else {
        echo "❌ API Request failed!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ HTTP Error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing with OpenAI Package ===\n";

try {
    echo "Sending request to OpenAI...\n";
    
    $client = \OpenAI::client(config('services.openai.api_key'));
    
    $response = $client->chat()->create([
        'model' => 'gpt-3.5-turbo', // Try cheaper model first
        'messages' => [
            ['role' => 'user', 'content' => 'Say "test successful"']
        ],
        'max_tokens' => 20
    ]);
    
    echo "✅ SUCCESS with gpt-3.5-turbo!\n";
    echo "Message: " . ($response->choices[0]->message->content ?? 'No content') . "\n\n";
    
    // Now try gpt-4
    echo "Testing gpt-4-turbo-preview...\n";
    $response2 = $client->chat()->create([
        'model' => 'gpt-4-turbo-preview',
        'messages' => [
            ['role' => 'user', 'content' => 'Say "test successful"']
        ],
        'max_tokens' => 20
    ]);
    
    echo "✅ SUCCESS with gpt-4-turbo-preview!\n";
    echo "Message: " . ($response2->choices[0]->message->content ?? 'No content') . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Error Type: " . get_class($e) . "\n\n";
    
    echo "Possible causes:\n";
    echo "1. ❌ Invalid API key (most likely)\n";
    echo "2. ❌ No credit/balance in OpenAI account\n";
    echo "3. ❌ API key format incorrect\n";
    echo "4. ❌ API key doesn't have access to the model\n\n";
    
    echo "Solutions:\n";
    echo "1. Generate new API key at: https://platform.openai.com/api-keys\n";
    echo "2. Check billing at: https://platform.openai.com/account/billing\n";
    echo "3. Ensure API key starts with 'sk-proj-' or 'sk-'\n";
}

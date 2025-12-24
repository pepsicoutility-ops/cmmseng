<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = config('services.openai.api_key');
$baseUrl = config('openai.base_uri') ?: 'https://ai.sumopod.com/v1';

echo "=== Testing SumoPod Function Calling Support ===\n\n";
echo "Base URL: $baseUrl\n";
echo "Model: gpt-4o-mini\n\n";

// Test simple function calling
$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'user', 'content' => 'What is the weather like in Boston?']
    ],
    'tools' => [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_weather',
                'description' => 'Get the current weather',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city name',
                        ],
                    ],
                    'required' => ['location'],
                ],
            ],
        ],
    ],
    'tool_choice' => 'auto',
];

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post($baseUrl . '/chat/completions', $payload);
    
    if ($response->successful()) {
        $data = $response->json();
        
        echo "✅ Response received!\n\n";
        
        if (isset($data['choices'][0]['message']['tool_calls'])) {
            echo "✅ Function calling IS supported!\n\n";
            echo "Tool calls:\n";
            print_r($data['choices'][0]['message']['tool_calls']);
        } else {
            echo "❌ Function calling NOT supported or not triggered\n\n";
            echo "Response:\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo "❌ Request failed: " . $response->status() . "\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT);
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

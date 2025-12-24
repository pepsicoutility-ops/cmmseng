<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = "sk-M3Alp-0183H9MMLESCEQQ2";

echo "Testing SumoPod API Key...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// SumoPod base URL
$baseUrls = [
    'https://api.sumopod.com/v1/chat/completions',
    'https://sumopod.com/api/v1/chat/completions',
    'https://api.openai.com/v1/chat/completions', // Standard OpenAI
];

foreach ($baseUrls as $url) {
    echo "Testing: $url\n";
    
    try {
        $response = Http::timeout(10)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Say "test successful"']
            ],
            'max_tokens' => 10
        ]);
        
        echo "Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            echo "✅ SUCCESS!\n";
            $data = $response->json();
            if (isset($data['choices'][0]['message']['content'])) {
                echo "Response: " . $data['choices'][0]['message']['content'] . "\n";
                echo "\n🎉 CORRECT BASE URL: $url\n";
                break;
            }
        } else {
            echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
        }
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

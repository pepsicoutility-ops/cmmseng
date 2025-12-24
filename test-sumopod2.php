<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = "sk-M3Alp-0183H9MMLESCEQQ2";

echo "Testing SumoPod API...\n\n";

// Kemungkinan base URLs untuk SumoPod
$endpoints = [
    'https://api.sumopod.com/v1/chat/completions',
    'https://sumopod.com/v1/chat/completions', 
    'https://api.sumopod.ai/v1/chat/completions',
    'https://sumopod.ai/v1/chat/completions',
    'https://gateway.sumopod.com/v1/chat/completions',
];

foreach ($endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    
    try {
        $response = Http::timeout(5)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, [
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'Hi']],
                'max_tokens' => 5
            ]);
        
        if ($response->successful()) {
            echo "✅ SUCCESS! Correct endpoint found!\n";
            echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
            
            echo "\n=== Configuration ===\n";
            echo "Add to .env:\n";
            echo "OPENAI_API_KEY=sk-M3Alp-0183H9MMLESCEQQ2\n";
            echo "OPENAI_BASE_URL=$endpoint\n";
            break;
        } else {
            echo "Status: " . $response->status() . " - " . ($response->body() ?: 'No response') . "\n";
        }
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo str_repeat("-", 70) . "\n";
}

echo "\n💡 Tip: Cek dokumentasi SumoPod untuk base URL yang benar\n";
echo "Biasanya di dashboard atau dokumentasi API mereka.\n";

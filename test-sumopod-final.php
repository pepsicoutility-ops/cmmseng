<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = "sk-kuIBrCFl4X005YVPBCN2LA";
$baseUrl = "https://ai.sumopod.com/v1/chat/completions";

echo "Testing SumoPod API Key...\n";
echo "Base URL: $baseUrl\n";
echo "API Key: " . substr($apiKey, 0, 15) . "...\n\n";

try {
    $response = Http::timeout(30)->withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post($baseUrl, [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'Say "test successful" in Indonesian']
        ],
        'max_tokens' => 20,
        'temperature' => 0.7
    ]);
    
    echo "HTTP Status: " . $response->status() . "\n\n";
    
    if ($response->successful()) {
        echo "✅ SUCCESS! SumoPod API Key is working!\n\n";
        
        $data = $response->json();
        
        if (isset($data['choices'][0]['message']['content'])) {
            echo "AI Response: " . $data['choices'][0]['message']['content'] . "\n\n";
            
            echo "=== Configuration for .env ===\n";
            echo "OPENAI_API_KEY=sk-kuIBrCFl4X005YVPBCN2LA\n";
            echo "OPENAI_BASE_URL=https://ai.sumopod.com/v1\n";
            echo "OPENAI_MODEL=gpt-4o-mini\n\n";
            
            echo "Available models:\n";
            echo "- gpt-4o-mini (fastest, cheapest)\n";
            echo "- gpt-4o (most capable)\n";
            echo "- claude-3-haiku\n";
            echo "- deepseek-chat\n";
        }
        
        echo "\nFull Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "❌ ERROR\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

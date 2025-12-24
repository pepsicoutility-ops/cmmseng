<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use OpenAI\Laravel\Facades\OpenAI;

echo "Testing OpenAI Package with SumoPod...\n";
echo "API Key: " . substr(config('services.openai.api_key'), 0, 15) . "...\n";
echo "Base URL: " . config('openai.base_uri') . "\n";
echo "Model: " . config('services.openai.model') . "\n\n";

try {
    echo "Sending request to SumoPod...\n";
    
    $response = OpenAI::chat()->create([
        'model' => config('services.openai.model', 'gpt-4o-mini'),
        'messages' => [
            ['role' => 'user', 'content' => 'Jelaskan dalam 1 kalimat apa itu CMMS']
        ],
        'max_tokens' => 100,
        'temperature' => 0.7
    ]);
    
    echo "\n✅ SUCCESS! AI Chat is working!\n\n";
    echo "AI Response:\n";
    echo $response->choices[0]->message->content . "\n\n";
    
    echo "Model Used: " . $response->model . "\n";
    echo "Tokens Used: " . $response->usage->totalTokens . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check your configuration.\n";
}

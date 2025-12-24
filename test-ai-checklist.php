<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ChatAIService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$service = new ChatAIService();
$conversation = $service->createConversation('Test Checklist Integration');

echo "=== Testing AI Chat with Checklist Data ===\n\n";

// Test questions about checklist
$questions = [
    "Tampilkan data checklist Compressor 1 terakhir",
    "Berapa run hours Chiller 1?",
    "Data terakhir shift 1 untuk Compressor 1",
];

foreach ($questions as $i => $question) {
    echo ($i + 1) . ". Question: \"{$question}\"\n";
    echo str_repeat("-", 60) . "\n";
    
    try {
        $result = $service->sendMessage($conversation->id, $question);
        echo "AI Response:\n";
        echo $result['assistantMessage']->content . "\n\n";
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo str_repeat("=", 60) . "\n\n";
}

echo "✅ Test completed!\n";

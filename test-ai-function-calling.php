<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ChatAIService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Testing AI Chat with Function Calling ===\n\n";

// Login as first user
$user = User::first();
if (!$user) {
    echo "❌ No user found. Please create a user first.\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->name}\n\n";

$service = new ChatAIService();

// Create new conversation
echo "Creating new conversation...\n";
$conversation = $service->createConversation('Test Function Calling');
echo "✅ Conversation created: ID {$conversation->id}\n\n";

// Test with a question that should trigger function call
$testMessage = "Tampilkan info Processing Unit";
echo "Sending message: \"{$testMessage}\"\n";
echo str_repeat("-", 60) . "\n";

try {
    $result = $service->sendMessage($conversation->id, $testMessage);
    
    echo "\n✅ SUCCESS!\n\n";
    echo "User Message:\n";
    echo $result['userMessage']->content . "\n\n";
    
    echo "AI Response:\n";
    echo $result['assistantMessage']->content . "\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nCheck logs: storage/logs/laravel.log\n";
}

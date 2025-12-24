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
$conversation = $service->createConversation('Simple Test');

echo "Testing simple question without tools...\n";
$result = $service->sendMessage($conversation->id, "Halo, siapa kamu?");

echo "✅ Response: " . $result['assistantMessage']->content . "\n";

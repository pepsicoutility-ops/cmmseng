<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ChatAIService;
use App\Models\ChatConversation;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing AI with Extended Database Functions ===\n\n";

// Create or get conversation
$testUser = \App\Models\User::first();
$conversation = ChatConversation::firstOrCreate(
    ['user_id' => $testUser->id, 'title' => 'Extended Functions Test'],
    ['title' => 'Extended Functions Test']
);

$chatService = new ChatAIService();

// Test queries
$testQueries = [
    "Tampilkan daftar semua area produksi",
    "Cari spare parts bearing",
    "Tampilkan parts yang stock nya rendah",
    "Berapa PM compliance rate bulan ini?",
    "Tampilkan statistik work order bulan ini",
    "Berapa total biaya maintenance bulan ini?",
    "Tampilkan workload semua teknisi",
    "Apa 3 masalah yang paling sering terjadi?",
];

foreach ($testQueries as $index => $query) {
    echo "Query " . ($index + 1) . ": {$query}\n";
    echo str_repeat('-', 80) . "\n";
    
    try {
        $response = $chatService->sendMessage($conversation->id, $query);
        
        echo "AI Response:\n";
        echo $response . "\n";
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('=', 80) . "\n\n";
    
    // Sleep to avoid rate limits
    sleep(2);
}

echo "=== Testing Complete ===\n";

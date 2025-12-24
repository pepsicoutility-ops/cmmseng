<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;

it('casts metadata to array', function (): void {
    $user = User::factory()->create();
    $conversation = ChatConversation::create(['user_id' => $user->id, 'title' => 'Test']);

    $message = ChatMessage::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'assistant',
        'content' => 'Hi',
        'metadata' => ['model' => 'gpt-4'],
    ]);

    expect($message->metadata)->toBeArray();
    expect($message->metadata['model'])->toBe('gpt-4');
});

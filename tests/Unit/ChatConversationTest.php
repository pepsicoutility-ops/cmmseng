<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;

it('scopes conversations by user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    ChatConversation::create(['user_id' => $userA->id, 'title' => 'A']);
    ChatConversation::create(['user_id' => $userB->id, 'title' => 'B']);

    $results = ChatConversation::query()->forUser($userA->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->user_id)->toBe($userA->id);
});

it('relates messages to a conversation', function (): void {
    $user = User::factory()->create();
    $conversation = ChatConversation::create(['user_id' => $user->id, 'title' => 'Test']);

    ChatMessage::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    expect($conversation->messages)->toHaveCount(1);
});

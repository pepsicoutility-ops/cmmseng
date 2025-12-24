<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatAIService;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Laravel\Facades\OpenAI;

it('stores user and assistant messages', function (): void {
    config()->set('services.openai.api_key', 'test-key');
    OpenAI::fake([
        CreateResponse::fake(),
    ]);

    $user = User::factory()->create();
    $conversation = ChatConversation::create([
        'user_id' => $user->id,
        'title' => 'New Chat',
    ]);

    $this->actingAs($user);

    $service = app(ChatAIService::class);
    $result = $service->sendMessage($conversation->id, 'Hello AI');

    expect($result['assistantMessage'])->toBeInstanceOf(ChatMessage::class);
    expect(ChatMessage::query()->where('conversation_id', $conversation->id)->count())->toBe(2);
});

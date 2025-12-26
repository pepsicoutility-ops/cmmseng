<?php

namespace App\Services;

use RuntimeException;
use Generator;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class ChatAIService
{
    public function createConversation(?string $title = null): ChatConversation
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new RuntimeException('User must be authenticated.');
        }

        return ChatConversation::create([
            'user_id' => $userId,
            'title' => $title ?: 'New Chat',
        ]);
    }

    /**
     * @return array{conversation: ChatConversation, userMessage: ChatMessage, assistantMessage: ChatMessage}
     */
    public function sendMessage(int $conversationId, string $message): array
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new RuntimeException('User must be authenticated.');
        }

        // Check AI usage limit before proceeding
        AiUsageService::checkUsageLimit($userId);

        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is missing. Set OPENAI_API_KEY in your .env file.');
        }

        $conversation = ChatConversation::query()
            ->forUser($userId)
            ->findOrFail($conversationId);

        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'role' => 'user',
            'content' => $message,
        ]);

        try {
            // Check if tools are enabled via config
            $useTools = config('openai.tools_enabled', true);
            
            $payload = [
                'model' => config('services.openai.model', 'gpt-4-turbo-preview'),
                'temperature' => (float) config('openai.temperature', 0.7),
                'max_tokens' => (int) config('openai.max_tokens', 1500),
                'messages' => $this->buildMessagesPayload($conversation),
            ];
            
            // Only add tools if using official OpenAI API
            if ($useTools) {
                $payload['tools'] = AIToolsService::getToolDefinitions();
                $payload['tool_choice'] = 'auto';
            }

            Log::info('Sending request to AI', [
                'model' => $payload['model'],
                'base_url' => config('openai.base_uri') ?: 'default (api.openai.com)',
                'tools_enabled' => $useTools,
                'tools_count' => isset($payload['tools']) ? count($payload['tools']) : 0,
                'messages_count' => count($payload['messages']),
            ]);

            try {
                $response = OpenAI::chat()->create($payload);
                
                Log::info('AI raw response received', [
                    'response_type' => get_class($response),
                    'has_choices' => isset($response->choices),
                    'choices_count' => isset($response->choices) ? count($response->choices) : 0,
                ]);
                
                // Validate response structure
                if (!isset($response->choices) || empty($response->choices)) {
                    Log::error('OpenAI response missing choices', [
                        'response' => method_exists($response, 'toArray') ? $response->toArray() : 'N/A',
                    ]);
                    throw new RuntimeException('AI response was empty or invalid. Please try again.');
                }
                
            } catch (Throwable $exception) {
                Log::error('OpenAI chat request failed.', [
                    'conversation_id' => $conversation->id,
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'api_key_set' => !empty(config('services.openai.api_key')),
                    'model' => config('services.openai.model'),
                    'base_url' => config('openai.base_uri'),
                ]);

                $errorMessage = $exception->getMessage();
                
                // Check for specific error types
                if (str_contains($errorMessage, 'Undefined array key') || 
                    str_contains($errorMessage, 'choices') ||
                    str_contains($errorMessage, 'Could not resolve')) {
                    throw new RuntimeException('AI service connection failed. Please check API configuration.');
                }
                
                if (str_contains($errorMessage, '401') || str_contains($errorMessage, 'Unauthorized')) {
                    throw new RuntimeException('AI API key is invalid. Please check OPENAI_API_KEY.');
                }
                
                if (str_contains($errorMessage, '429') || str_contains($errorMessage, 'rate limit')) {
                    throw new RuntimeException('AI rate limit exceeded. Please wait a moment and try again.');
                }
                
                if (str_contains($errorMessage, 'model')) {
                    throw new RuntimeException('AI model not available. Please check OPENAI_MODEL setting.');
                }

                throw new RuntimeException('AI request failed: ' . $errorMessage);
            }

            $choice = $response->choices[0];
            
            // Check if tool_calls exists
            $toolCalls = null;
            $responseMessage = $choice->message;
            
            // @phpstan-ignore-next-line - Dynamic property from OpenAI response
            if (isset($responseMessage->toolCalls) && !empty($responseMessage->toolCalls)) {
    $toolCalls = $responseMessage->toolCalls;
}
            
            Log::info('Processing AI choice', [
                'has_tool_calls' => $toolCalls !== null && !empty($toolCalls),
                'tool_calls_count' => $toolCalls ? (is_array($toolCalls) ? count($toolCalls) : count((array)$toolCalls)) : 0,
                'has_content' => isset($responseMessage->content) && $responseMessage->content !== null,
                'content_preview' => isset($responseMessage->content) ? substr((string)$responseMessage->content, 0, 100) : 'null',
                'finish_reason' => $choice->finish_reason ?? 'unknown',
            ]);
            
            // Handle function calls
            if ($toolCalls && !empty($toolCalls)) {
                $toolResults = [];
                
                foreach ($toolCalls as $toolCall) {
                    $functionName = $toolCall->function->name;
                    $arguments = json_decode($toolCall->function->arguments, true);
                    
                    Log::info('AI calling function', [
                        'function' => $functionName,
                        'arguments' => $arguments,
                    ]);
                    
                    $result = AIToolsService::executeTool($functionName, $arguments);
                    $toolResults[] = [
                        'tool_call_id' => $toolCall->id,
                        'role' => 'tool',
                        'name' => $functionName,
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];
                }
                
                // Call AI again with tool results
                $messages = $this->buildMessagesPayload($conversation);
                
                // Add assistant message with tool calls
                $assistantMessage = [
                    'role' => 'assistant',
                    'content' => '',  // Empty string when there are tool_calls
                    'tool_calls' => array_map(function ($toolCall) {
                        return [
                            'id' => $toolCall->id,
                            'type' => 'function',
                            'function' => [
                                'name' => $toolCall->function->name,
                                'arguments' => $toolCall->function->arguments,
                            ],
                        ];
                    }, $toolCalls),
                ];
                
                $messages[] = $assistantMessage;
                $messages = array_merge($messages, $toolResults);
                
                Log::info('Calling AI with tool results', [
                    'messages_count' => count($messages),
                    'tool_results_count' => count($toolResults),
                ]);
                
                $secondPayload = [
                    'model' => config('services.openai.model', 'gpt-4-turbo-preview'),
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'messages' => $messages,
                ];
                
                $response = OpenAI::chat()->create($secondPayload);
                $assistantContent = trim((string) ($response->choices[0]->message->content ?? ''));
                
                if ($assistantContent === '') {
                    Log::error('Empty response after tool call', [
                        'response' => $response->toArray(),
                    ]);
                    throw new RuntimeException('AI tidak memberikan response setelah mengakses data. Silakan coba lagi.');
                }
            } else {
                $assistantContent = trim((string) ($choice->message->content ?? ''));
            }

            if ($assistantContent === '') {
                throw new RuntimeException('OpenAI returned an empty response.');
            }

            // Log AI usage with token counts from response
            $totalPromptTokens = $response->usage->promptTokens ?? 0;
            $totalCompletionTokens = $response->usage->completionTokens ?? 0;
            
            AiUsageService::logUsage(
                $totalPromptTokens,
                $totalCompletionTokens,
                $payload['model'],
                'chat',
                [
                    'conversation_id' => $conversation->id,
                    'has_tool_calls' => $toolCalls !== null && !empty($toolCalls),
                ],
                $userId
            );

            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'role' => 'assistant',
                'content' => $assistantContent,
                'metadata' => [
                    'model' => $payload['model'],
                    'tokens' => [
                        'prompt' => $totalPromptTokens,
                        'completion' => $totalCompletionTokens,
                        'total' => $totalPromptTokens + $totalCompletionTokens,
                    ],
                ],
            ]);

            if (! $conversation->title || $conversation->title === 'New Chat') {
                $conversation->title = $this->generateTitle($userMessage->content);
            }

            $conversation->touch();
            $conversation->save();

            return [
                'conversation' => $conversation,
                'userMessage' => $userMessage,
                'assistantMessage' => $assistantMessage,
            ];
        } catch (Throwable $exception) {
            Log::error('ChatAIService failed to send message.', [
                'conversation_id' => $conversation->id,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return Generator<int, string>
     */
    public function streamMessage(int $conversationId, string $message): Generator
    {
        $result = $this->sendMessage($conversationId, $message);
        $content = (string) $result['assistantMessage']->content;

        foreach (preg_split('/\s+/', trim($content)) as $word) {
            if ($word === '') {
                continue;
            }

            yield $word . ' ';
        }
    }

    public function generateTitle(string $message): string
    {
        $title = Str::of($message)->squish()->limit(60, '');

        return $title === '' ? 'New Chat' : (string) $title;
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessagesPayload(ChatConversation $conversation): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah PEP Engineering AI Assistant untuk CMMS PepsiCo.

PANDUAN MENJAWAB:
- Jawab dalam Bahasa Indonesia yang jelas dan langsung
- Fokus pada HASIL AKHIR, bukan rumus atau teori
- Jika diminta hitung sesuatu, langsung berikan ANGKA HASIL jawabannya
- Jangan berikan rumus atau langkah-langkah kecuali diminta
- Gunakan tools yang tersedia untuk data real-time dari database
- Format jawaban: ringkas, praktis, to the point

EQUIPMENT: Chiller, Compressor, AHU, Motor

CONTOH YANG BENAR:
User: "Berapa konsumsi energi motor 5 kW yang jalan 8 jam?"
AI: "Motor 5 kW yang beroperasi 8 jam mengkonsumsi 40 kWh energi."

CONTOH YANG SALAH:
User: "Berapa konsumsi energi motor 5 kW yang jalan 8 jam?"
AI: "Untuk menghitung konsumsi energi... Rumus: [formula]... Langkah 1... Langkah 2..."

Ingat: User butuh JAWABAN CEPAT, bukan pelajaran!',
            ],
        ];

        $history = $conversation->messages()
            ->orderBy('id')
            ->get(['role', 'content']);

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry->role,
                'content' => $entry->content,
            ];
        }

        return $messages;
    }
}

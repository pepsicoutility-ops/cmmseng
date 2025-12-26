<?php

namespace App\Filament\Pages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatAIService;
use App\Services\AiUsageService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use BackedEnum;

class ChatAI extends Page
{
    protected string $view = 'filament.pages.chat-ai';

    // Memaksa tampilan melebar penuh (Full Width)
    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role !== 'operator';
    }

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'AI Chat';
    protected static ?string $title = 'AI Chat';

    public ?int $activeConversationId = null;
    public string $message = '';
    public array $conversations = [];
    public array $messages = [];
    public bool $isLoading = false;
    public array $usageStats = [];

    public function mount(): void
    {
        $this->loadConversations();
        $this->loadUsageStats();

        // Otomatis pilih chat terakhir jika ada
        if ($this->activeConversationId === null && !empty($this->conversations)) {
            $this->activeConversationId = $this->conversations[0]['id'];
        }

        $this->loadMessages();
    }

    public function loadUsageStats(): void
    {
        $this->usageStats = AiUsageService::getUserStats();
    }

    public function loadConversations(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->conversations = [];
            return;
        }

        $this->conversations = ChatConversation::query()
            ->forUser($userId)
            ->latest('updated_at')
            ->get()
            ->map(fn (ChatConversation $conversation) => [
                'id' => $conversation->id,
                'title' => $conversation->title ?: 'New Diagnostics',
                'updated_at' => $conversation->updated_at, // Object Carbon untuk diffForHumans
            ])
            ->all();
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = ChatConversation::query()->findOrFail($conversationId);
        Gate::authorize('view', $conversation);

        $this->activeConversationId = $conversationId;
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        if (!$this->activeConversationId) {
            $this->messages = [];
            return;
        }

        $conversation = ChatConversation::query()->findOrFail($this->activeConversationId);
        Gate::authorize('view', $conversation);

        $this->messages = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at?->format('H:i'),
            ])
            ->all();
    }

    public function createNewConversation(): void
    {
        $conversation = app(ChatAIService::class)->createConversation();

        $this->activeConversationId = $conversation->id;
        $this->loadConversations();
        $this->loadMessages();
    }

    public function sendMessage(): void
    {
        $content = trim($this->message);

        if ($content === '') {
            return;
        }

        if (!$this->activeConversationId) {
            $this->createNewConversation();
        }

        $rateKey = 'chat-ai:' . Auth::id();
        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            Notification::make()
                ->title('Rate limit exceeded')
                ->warning()
                ->send();
            return;
        }

        RateLimiter::hit($rateKey, 60);

        $this->isLoading = true;
        $this->message = '';

        try {
            app(ChatAIService::class)->sendMessage($this->activeConversationId, $content);
            $this->loadMessages();
            $this->loadConversations();
            $this->loadUsageStats(); // Refresh usage stats after sending
            
            // Event untuk men-trigger scroll ke bawah dan highlight code
            $this->dispatch('message-sent'); 
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Failed to send')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function deleteConversation(int $conversationId): void
    {
        $conversation = ChatConversation::query()->findOrFail($conversationId);
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        if ($this->activeConversationId === $conversationId) {
            $this->activeConversationId = null;
            $this->messages = [];
        }

        $this->loadConversations();
    }
}
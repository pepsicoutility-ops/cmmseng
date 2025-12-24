<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function delete(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatMessage $message): bool
    {
        return $message->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}

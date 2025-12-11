<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\ChatConversation;

Broadcast::channel('conversations.{conversationId}', function (User $user, int $conversationId) {
    $conversation = ChatConversation::query()
        ->where('id', $conversationId)
        ->where('is_active', true)
        ->first();

    if (!$conversation) {
        return false;
    }

    // Superadmin: full access
    if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
        return true;
    }

    if (!$user->id_empresa || $conversation->empresa_id !== (int) $user->id_empresa) {
        return false;
    }

    return $conversation->users()
        ->where('users.id', $user->id)
        ->exists();
});

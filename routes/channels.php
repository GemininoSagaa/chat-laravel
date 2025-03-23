<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;

// Canal privado para mensajes directos
Broadcast::channel('chat.{receiverId}.{senderId}', function ($user, $receiverId, $senderId) {
    return (int) $user->id === (int) $receiverId || (int) $user->id === (int) $senderId;
});

// Canal privado para grupos
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    return Group::find($groupId)->users()->where('users.id', $user->id)->exists();
});
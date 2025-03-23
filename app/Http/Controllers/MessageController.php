<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getUserMessages(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Verificar si son amigos
        $areFriends = $currentUser->friends()->where('users.id', $user->id)->exists();
        
        if (!$areFriends) {
            return response()->json(['message' => 'No tienen una relación de amistad'], 403);
        }
        
        // Obtener mensajes entre los usuarios
        $messages = Message::where(function ($query) use ($currentUser, $user) {
                $query->where('sender_id', $currentUser->id)
                      ->where('receiver_id', $user->id);
            })->orWhere(function ($query) use ($currentUser, $user) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $currentUser->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Marcar como leídos los mensajes recibidos
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $currentUser->id)
            ->where('read', false)
            ->update(['read' => true]);
            
        return response()->json(['messages' => $messages]);
    }
    
    public function sendMessage(Request $request, User $user)
    {
        $request->validate([
            'content' => 'required|string'
        ]);
        
        $currentUser = $request->user();
        
        // Verificar si son amigos
        $areFriends = $currentUser->friends()->where('users.id', $user->id)->exists();
        
        if (!$areFriends) {
            return response()->json(['message' => 'No tienen una relación de amistad'], 403);
        }
        
        $message = Message::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $user->id,
            'content' => $request->content,
        ]);
        
        // Enviar evento en tiempo real
        broadcast(new MessageSent($message))->toOthers();
        
        return response()->json(['message' => $message]);
    }
    
    public function getGroupMessages(Request $request, Group $group)
    {
        $currentUser = $request->user();
        
        // Verificar si el usuario es miembro del grupo
        $isMember = $group->users()->where('users.id', $currentUser->id)->exists();
        
        if (!$isMember) {
            return response()->json(['message' => 'No eres miembro de este grupo'], 403);
        }
        
        // Obtener mensajes del grupo
        $messages = $group->messages()->with('sender')->orderBy('created_at', 'asc')->get();
        
        return response()->json(['messages' => $messages]);
    }
    
    public function sendGroupMessage(Request $request, Group $group)
    {
        $request->validate([
            'content' => 'required|string'
        ]);
        
        $currentUser = $request->user();
        
        // Verificar si el usuario es miembro del grupo
        $isMember = $group->users()->where('users.id', $currentUser->id)->exists();
        
        if (!$isMember) {
            return response()->json(['message' => 'No eres miembro de este grupo'], 403);
        }
        
        $message = Message::create([
            'sender_id' => $currentUser->id,
            'group_id' => $group->id,
            'content' => $request->content,
        ]);
        
        // Enviar evento en tiempo real
        broadcast(new MessageSent($message))->toOthers();
        
        return response()->json(['message' => $message]);
    }
    
    public function userTyping(Request $request, $receiverId)
    {
        $sender = $request->user();
        
        broadcast(new UserTyping($sender->id, $receiverId))->toOthers();
        
        return response()->json(['status' => 'ok']);
    }
}
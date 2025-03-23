<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Obtener amistades aceptadas
        $friends = $user->friends()->get();
        
        // Obtener solicitudes pendientes enviadas por el usuario
        $sentRequests = Friendship::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('friend')
            ->get();
            
        // Obtener solicitudes pendientes recibidas por el usuario
        $receivedRequests = Friendship::where('friend_id', $user->id)
            ->where('status', 'pending')
            ->with('user')
            ->get();
            
        return response()->json([
            'friends' => $friends,
            'sent_requests' => $sentRequests,
            'received_requests' => $receivedRequests,
        ]);
    }
    
    public function sendRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        
        $user = $request->user();
        $friend = User::where('email', $request->email)->first();
        
        if ($user->id === $friend->id) {
            return response()->json(['message' => 'No puedes enviarte una solicitud a ti mismo'], 400);
        }
        
        $existingFriendship = Friendship::where(function ($query) use ($user, $friend) {
                $query->where('user_id', $user->id)
                      ->where('friend_id', $friend->id);
            })->orWhere(function ($query) use ($user, $friend) {
                $query->where('user_id', $friend->id)
                      ->where('friend_id', $user->id);
            })->first();
            
        if ($existingFriendship) {
            return response()->json(['message' => 'Ya existe una relación de amistad o solicitud'], 400);
        }
        
        $friendship = Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'pending',
        ]);
        
        return response()->json([
            'message' => 'Solicitud de amistad enviada',
            'friendship' => $friendship
        ]);
    }
    
    public function acceptRequest(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        $friendship = Friendship::where('user_id', $user->id)
            ->where('friend_id', $currentUser->id)
            ->where('status', 'pending')
            ->firstOrFail();
            
        $friendship->status = 'accepted';
        $friendship->save();
        
        return response()->json([
            'message' => 'Solicitud de amistad aceptada',
            'friendship' => $friendship
        ]);
    }
    
    public function rejectRequest(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        $friendship = Friendship::where('user_id', $user->id)
            ->where('friend_id', $currentUser->id)
            ->where('status', 'pending')
            ->firstOrFail();
            
        $friendship->status = 'rejected';
        $friendship->save();
        
        return response()->json([
            'message' => 'Solicitud de amistad rechazada',
            'friendship' => $friendship
        ]);
    }
}
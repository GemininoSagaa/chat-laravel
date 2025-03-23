<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $groups = $user->groups()->with('creator')->get();
        
        return response()->json(['groups' => $groups]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'required|array',
            'members.*' => 'exists:users,id'
        ]);
        
        $user = $request->user();
        
        // Crear el grupo
        $group = Group::create([
            'name' => $request->name,
            'creator_id' => $user->id,
        ]);
        
        // Añadir al creador como miembro
        $group->users()->attach($user->id);
        
        // Añadir miembros
        foreach ($request->members as $memberId) {
            // Verificar que es amigo del creador
            $isFriend = $user->friends()->where('users.id', $memberId)->exists();
            
            if ($isFriend) {
                $group->users()->attach($memberId);
            }
        }
        
        return response()->json([
            'message' => 'Grupo creado exitosamente',
            'group' => $group->load('users')
        ], 201);
    }
    
    public function show(Request $request, Group $group)
    {
        $user = $request->user();
        
        // Verificar si el usuario es miembro del grupo
        $isMember = $group->users()->where('users.id', $user->id)->exists();
        
        if (!$isMember) {
            return response()->json(['message' => 'No eres miembro de este grupo'], 403);
        }
        
        return response()->json([
            'group' => $group->load(['users', 'creator'])
        ]);
    }
    
    public function addMember(Request $request, Group $group)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        
        $currentUser = $request->user();
        $newMember = User::findOrFail($request->user_id);
        
        // Verificar si el usuario actual es miembro del grupo
        $isMember = $group->users()->where('users.id', $currentUser->id)->exists();
        
        if (!$isMember) {
            return response()->json(['message' => 'No tienes permiso para añadir miembros'], 403);
        }
        
        // Verificar si el usuario a añadir ya es miembro
        $isAlreadyMember = $group->users()->where('users.id', $newMember->id)->exists();
        
        if ($isAlreadyMember) {
            return response()->json(['message' => 'Este usuario ya es miembro del grupo'], 400);
        }
        
        // Verificar que es amigo del usuario actual
        $isFriend = $currentUser->friends()->where('users.id', $newMember->id)->exists();
        
        if (!$isFriend) {
            return response()->json(['message' => 'Solo puedes añadir amigos al grupo'], 400);
        }
        
        // Añadir al nuevo miembro
        $group->users()->attach($newMember->id);
        
        return response()->json([
            'message' => 'Miembro añadido exitosamente',
            'group' => $group->load('users')
        ]);
    }
}
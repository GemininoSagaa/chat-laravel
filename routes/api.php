<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Amistades
    Route::get('/friends', [FriendshipController::class, 'index']);
    Route::post('/friends/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/accept/{user}', [FriendshipController::class, 'acceptRequest']);
    Route::post('/friends/reject/{user}', [FriendshipController::class, 'rejectRequest']);
    
    // Mensajes
    Route::get('/messages/{user}', [MessageController::class, 'getUserMessages']);
    Route::post('/messages/{user}', [MessageController::class, 'sendMessage']);
    
    // Grupos
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::post('/groups/{group}/messages', [MessageController::class, 'sendGroupMessage']);
    Route::get('/groups/{group}/messages', [MessageController::class, 'getGroupMessages']);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
});
<?php

use Illuminate\Http\Request;


// ----------------------------------------------------
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Route publique (non protégée par Sanctum) pour l'authentification
Route::post('/login', [AuthController::class, 'login']);

// Route protégée par Sanctum pour la déconnexion
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    
    // Routes CRUD Utilisateurs
    Route::apiResource('users', UserController::class);
    
    // Route pour récupérer tous les rôles (pour les selects)
    Route::get('roles', [UserController::class, 'getRoles']);
    
    // Route pour récupérer les permissions
    Route::get('permissions', [UserController::class, 'getPermissions']);
    
    // Route pour assigner/retirer des rôles
    Route::post('users/{user}/roles', [UserController::class, 'syncRoles']);
    
    // Route pour l'utilisateur connecté
    Route::get('me', [UserController::class, 'me']);
});
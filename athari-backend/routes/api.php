<?php

use Illuminate\Http\Request;


// ----------------------------------------------------
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Route publique (non protégée par Sanctum) pour l'authentification
Route::post('/login', [AuthController::class, 'login']);

// Route protégée par Sanctum pour la déconnexion
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
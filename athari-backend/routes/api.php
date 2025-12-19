<?php

use Illuminate\Http\Request;


// ----------------------------------------------------
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\AgencyController;
 use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TypesCompteController;


// Route publique (non protégée par Sanctum) pour l'authentification
Route::post('/login', [AuthController::class, 'login']);

// Route protégée par Sanctum pour la déconnexion
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Routes protégées pour authentification
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
    Route::apiResource('agencies', AgencyController::class);


});

// route gestion clients

Route::middleware('auth:sanctum')->group(function () {
    
    // Routes de création (Chef Agence & DG via les FormRequests)
    Route::post('/clients/physique', [ClientController::class, 'storePhysique']);
    Route::post('/clients/morale', [ClientController::class, 'storeMorale']);
    
    // Autres routes CRUD
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
    
});

Route::middleware(['auth:sanctum', 'permission:consulter logs'])->group(function () {
    Route::get('/audit/logs', [AuditLogController::class, 'index']);
});

// Routes pour la gestion des Comptes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Types de comptes
    Route::apiResource('account-types', TypesCompteController::class);
    
    // Comptes bancaires
    Route::prefix('accounts')->name('accounts.')->group(function () {
        // Liste et CRUD de base
        Route::get('/', [CompteController::class, 'index'])->name('index');
        Route::post('/', [CompteController::class, 'store'])->name('store');
        Route::get('/{account}', [CompteController::class, 'show'])->name('show');
        Route::put('/{account}', [CompteController::class, 'update'])->name('update');
        
        // Validation workflow
        Route::post('/{account}/validate', [CompteController::class, 'validate'])->name('validate');
        Route::get('/pending/validation', [CompteController::class, 'enAttenteValidation'])->name('pending');
        
        // Actions spéciales
        Route::post('/{account}/opposition', [CompteController::class, 'opposition'])->name('opposition');
        Route::post('/{account}/cloturer', [CompteController::class, 'cloturer'])->name('cloturer');
        
        // Historique
        Route::get('/{account}/transactions', [CompteController::class, 'transactions'])->name('transactions');
    });
});
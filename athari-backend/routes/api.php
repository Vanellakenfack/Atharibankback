<?php

use Illuminate\Http\Request;


// ----------------------------------------------------
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\AgencyController;
 use App\Http\Controllers\ClientController;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\TypesCompteController;


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
    Route::apiResource('agencies', AgencyController::class);

        // Comptes
    Route::prefix('accounts')->group(function () {
        Route::get('/', [CompteController::class, 'index']);
        Route::post('/', [CompteController::class, 'store']);
        Route::get('/pending-validation', [CompteController::class, 'pendingValidation']);
        Route::get('/{account}', [CompteController::class, 'show']);
        Route::put('/{account}', [CompteController::class, 'update']);
        Route::delete('/{account}', [CompteController::class, 'destroy']);
        Route::post('/{account}/validate', [CompteController::class, 'validate']);
        Route::post('/{account}/close', [CompteController::class, 'close']);
        Route::post('/{account}/block', [CompteController::class, 'block']);
        Route::post('/{account}/unblock', [CompteController::class, 'unblock']);
        Route::get('/{account}/statement', [CompteController::class, 'statement']);
    });

    // Types de comptes
    Route::prefix('account-types')->group(function () {
        Route::get('/', [TypesCompteController::class, 'index']);
        Route::post('/', [TypesCompteController::class, 'store']);
        Route::get('/categories', [TypesCompteController::class, 'categories']);
        Route::get('/{accountType}', [TypesCompteController::class, 'show']);
        Route::put('/{accountType}', [TypesCompteController::class, 'update']);
        Route::delete('/{accountType}', [TypesCompteController::class, 'destroy']);
    });

    // Chapitres comptables
    Route::apiResource('accounting-chapters', AccountingChapterController::class);



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
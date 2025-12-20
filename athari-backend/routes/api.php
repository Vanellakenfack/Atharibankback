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
use App\Http\Controllers\TypeCompteController;
use App\Http\Controllers\DocumentCompteController;


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

// route audit
Route::middleware(['auth:sanctum', 'permission:consulter logs'])->group(function () {
    Route::get('/audit/logs', [AuditLogController::class, 'index']);
});

// Routes pour la gestion des Comptes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Types de comptes
    Route::prefix('types-comptes')->group(function () {
    
        // Liste et recherche
        Route::get('/', [TypeCompteController::class, 'index']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        
        // Informations utilitaires
        Route::get('/rubriques-mata', [TypeCompteController::class, 'getRubriquesMata']);
        Route::get('/durees-blocage', [TypeCompteController::class, 'getDureesBlocage']);
        
        // Consultation
        Route::get('/{id}', [TypeCompteController::class, 'show']);
        Route::get('/code/{code}', [TypeCompteController::class, 'showByCode']);
        
        //crud operation
        Route::post('/', [TypeCompteController::class, 'store']);
        Route::put('/{id}', [TypeCompteController::class, 'update']);
        Route::delete('/{id}', [TypeCompteController::class, 'destroy']);
        Route::patch('/{id}/toggle-actif', [TypeCompteController::class, 'toggleActif']);
        });
    
   // Routes pour les comptes
    Route::prefix('comptes')->group(function () {
    
    // Initialisation de l'ouverture de compte
    Route::get('/init', [CompteController::class, 'initOuverture']);
    
    // Validation des étapes
    Route::post('/etape1/valider', [CompteController::class, 'validerEtape1']);
    Route::post('/etape2/valider', [CompteController::class, 'validerEtape2']);
    Route::post('/etape3/valider', [CompteController::class, 'validerEtape3']);
    
    // CRUD des comptes
    Route::get('/', [CompteController::class, 'index']);
    Route::post('/creer', [CompteController::class, 'store']);
    Route::get('/{id}', [CompteController::class, 'show']);
    Route::put('/{id}', [CompteController::class, 'update']);
    Route::delete('/{id}', [CompteController::class, 'destroy']);
    // Actions spécifiques
    Route::post('/{id}/cloturer', [CompteController::class, 'cloturer']);

    // Documents du compte
    Route::get('/{compteId}/documents', [DocumentCompteController::class, 'index']);
    Route::post('/{compteId}/documents', [DocumentCompteController::class, 'store']);

    });
});
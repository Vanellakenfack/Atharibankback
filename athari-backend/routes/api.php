<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TypeCompteController;
use App\Http\Controllers\Admin\CategorieComptableController;
use App\Http\Controllers\Admin\PlanComptableController;
use App\Http\Controllers\DocumentCompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes d'authentification
|
*/

// Routes publiques (sans authentification)
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    
    /*
    |--------------------------------------------------------------------------
    | Gestion des utilisateurs et autorisations
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::get('/permissions', [UserController::class, 'getPermissions']);
        Route::post('/{user}/roles', [UserController::class, 'syncRoles']);
    });
    Route::apiResource('users', UserController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Gestion des agences
    |--------------------------------------------------------------------------
    */
    Route::apiResource('agencies', AgencyController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Gestion des clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->group(function () {
        // Création
        Route::post('/physique', [ClientController::class, 'storePhysique']);
        Route::post('/morale', [ClientController::class, 'storeMorale']);
        
        // CRUD standard
        Route::get('/', [ClientController::class, 'index']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Comptabilité - Administration
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/comptabilite')->group(function () {
        // Catégories comptables (rubriques)
        Route::get('/categories', [CategorieComptableController::class, 'index']);
        Route::post('/categories', [CategorieComptableController::class, 'store']);
        
        // Plan comptable (comptes de détail)
        Route::get('/comptes', [PlanComptableController::class, 'index']);
        Route::post('/comptes', [PlanComptableController::class, 'store']);
        Route::get('/comptes/{planComptable}', [PlanComptableController::class, 'show']);
        Route::patch('/comptes/{planComptable}/archive', [PlanComptableController::class, 'archive']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Types de comptes
    |--------------------------------------------------------------------------
    */
    Route::prefix('types-comptes')->group(function () {
        // Consultation
        Route::get('/', [TypeCompteController::class, 'index']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        Route::get('/{id}', [TypeCompteController::class, 'show']);
        Route::get('/code/{code}', [TypeCompteController::class, 'showByCode']);
        
        // Informations utilitaires
        Route::get('/rubriques-mata', [TypeCompteController::class, 'getRubriquesMata']);
        Route::get('/durees-blocage', [TypeCompteController::class, 'getDureesBlocage']);
        
        // CRUD
        Route::post('/', [TypeCompteController::class, 'store']);
        Route::put('/{id}', [TypeCompteController::class, 'update']);
        Route::delete('/{id}', [TypeCompteController::class, 'destroy']);
        Route::patch('/{id}/toggle-actif', [TypeCompteController::class, 'toggleActif']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestion des comptes bancaires
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes')->group(function () {
        // Initialisation et validation d'ouverture
        Route::get('/init', [CompteController::class, 'initOuverture']);
        Route::post('/etape1/valider', [CompteController::class, 'validerEtape1']);
        Route::post('/etape2/valider', [CompteController::class, 'validerEtape2']);
        Route::post('/etape3/valider', [CompteController::class, 'validerEtape3']);
        
        // CRUD
        Route::get('/', [CompteController::class, 'index']);
        Route::post('/creer', [CompteController::class, 'store']);
        Route::get('/{id}', [CompteController::class, 'show']);
        Route::put('/{id}', [CompteController::class, 'update']);
        Route::delete('/{id}', [CompteController::class, 'destroy']);
        
        // Actions spécifiques
        Route::post('/{id}/cloturer', [CompteController::class, 'cloturer']);
        
        // Documents associés
        Route::prefix('/{compteId}')->group(function () {
            Route::get('/documents', [DocumentCompteController::class, 'index']);
            Route::post('/documents', [DocumentCompteController::class, 'store']);
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | Audit et logs
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:consulter logs')->group(function () {
        Route::get('/audit/logs', [AuditLogController::class, 'index']);
    });
});
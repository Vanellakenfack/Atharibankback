<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Compte\DatContratController;
use App\Http\Controllers\Compte\DatTypeController;

use App\Http\Controllers\CompteController;
use App\Http\Controllers\TypeCompteController;
use App\Http\Controllers\DocumentCompteController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\Plancomptable\PlanComptableController;
use App\Http\Controllers\Plancomptable\CategorieComptableController;
use App\Http\Controllers\frais\FraisCommissionController;
use App\Http\Controllers\frais\FraisApplicationController;
use App\Http\Controllers\frais\MouvementRubriqueMataController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Routes protégées (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentification
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Utilisateurs & rôles
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
    | Agences
    |--------------------------------------------------------------------------
    */
   // Route::apiResource('agencies', AgencyController::class);


 Route::get('/agencies', [AgencyController::class, 'index']);
Route::post('/agencies', [AgencyController::class, 'index']); // <-- ERREUR ICI


    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->group(function () {
        Route::post('/physique', [ClientController::class, 'storePhysique']);
        Route::post('/morale', [ClientController::class, 'storeMorale']);
        Route::get('/', [ClientController::class, 'index']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Plan comptable
    |--------------------------------------------------------------------------
    */
    Route::prefix('plan-comptable')->group(function () {
        Route::get('categories', [CategorieComptableController::class, 'index']);
        Route::post('categories', [CategorieComptableController::class, 'store']);
        Route::put('categories/{id}', [CategorieComptableController::class, 'update']);

        Route::get('comptes', [PlanComptableController::class, 'index']);
        Route::post('comptes', [PlanComptableController::class, 'store']);
        Route::put('comptes/{id}', [PlanComptableController::class, 'update']);
        Route::patch('comptes/{planComptable}/archive', [PlanComptableController::class, 'archive']);
    });

    /*
    |--------------------------------------------------------------------------
    | Types de comptes
    |--------------------------------------------------------------------------
    */
    Route::prefix('types-comptes')->group(function () {
        Route::get('/', [TypeCompteController::class, 'index']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        Route::get('/{id}', [TypeCompteController::class, 'show']);
        Route::get('/code/{code}', [TypeCompteController::class, 'showByCode']);
        Route::get('/rubriques-mata', [TypeCompteController::class, 'getRubriquesMata']);
        Route::get('/durees-blocage', [TypeCompteController::class, 'getDureesBlocage']);
        Route::post('/', [TypeCompteController::class, 'store']);
        Route::put('/{id}', [TypeCompteController::class, 'update']);
        Route::delete('/{id}', [TypeCompteController::class, 'destroy']);
        Route::patch('/{id}/toggle-actif', [TypeCompteController::class, 'toggleActif']);
    });

    /*
    |--------------------------------------------------------------------------
    | Gestion des DAT (Dépôts à Terme)
    |--------------------------------------------------------------------------
    */
    Route::prefix('dat')->group(function () {
        // Liste des contrats pour le tableau
        Route::get('/contracts', [DatContratController::class, 'index']); 
        
        // Liste des types (offres) pour la modale
        Route::get('/types', [DatTypeController::class, 'index']); 
        Route::post('/types', [DatTypeController::class, 'store']);
        
        // Action de souscription
        Route::post('/subscribe', [DatContratController::class, 'store']); 
        Route::post('/simulate', [DatContratController::class, 'simulate']);
        
        // Actions individuelles
        Route::get('/{id}', [DatContratController::class, 'show']);
        Route::post('/{id}/cloturer', [DatContratController::class, 'cloturer']);
    });

    /*
    |--------------------------------------------------------------------------
    | Comptes bancaires
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes')->group(function () {
        Route::get('/init', [CompteController::class, 'initOuverture']);
        Route::post('/etape1/valider', [CompteController::class, 'validerEtape1']);
        Route::post('/etape2/valider', [CompteController::class, 'validerEtape2']);
        Route::post('/etape3/valider', [CompteController::class, 'validerEtape3']);

        Route::get('/', [CompteController::class, 'index']);
        Route::post('/', [CompteController::class, 'store']);
        Route::get('/{id}', [CompteController::class, 'show']);
        Route::put('/{id}', [CompteController::class, 'update']);
        Route::delete('/{id}', [CompteController::class, 'destroy']);
        Route::post('/{id}/cloturer', [CompteController::class, 'cloturer']);

        Route::prefix('{compteId}')->group(function () {
            Route::get('/documents', [DocumentCompteController::class, 'index']);
            Route::post('/documents', [DocumentCompteController::class, 'store']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Audit & Logs
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:consulter logs')->group(function () {
        Route::get('/audit/logs', [AuditLogController::class, 'index']);
    });
});
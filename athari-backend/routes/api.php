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
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TypeCompteController;
use App\Http\Controllers\DocumentCompteController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\Plancomptable\PlanComptableController as PlanComptableNewController;
use App\Http\Controllers\Plancomptable\CategorieComptableController as CategorieComptableNewController;
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
    Route::apiResource('agencies', AgencyController::class);

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->group(function () {
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
    | Plan comptable
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/comptabilite')->group(function () {
        // Catégories comptables (rubriques)
        Route::get('/categories', [CategorieComptableNewController::class, 'index']);
        Route::post('/categories', [CategorieComptableNewController::class, 'store']);

        // Plan comptable (comptes de détail)
        Route::get('/comptes', [PlanComptableNewController::class, 'index']);
        Route::post('/comptes', [PlanComptableNewController::class, 'store']);
        Route::get('/comptes/{planComptable}', [PlanComptableNewController::class, 'show']);
        Route::put('/comptes/{id}', [PlanComptableNewController::class, 'update']);
        Route::patch('/comptes/{planComptable}/archive', [PlanComptableNewController::class, 'archive']);
    });

    /*
    |--------------------------------------------------------------------------
    | Types de comptes
    |--------------------------------------------------------------------------
    */
    Route::prefix('types-comptes')->group(function () {
        // Routes de lecture
        Route::get('/', [TypeCompteController::class, 'index']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        Route::get('/code/{code}', [TypeCompteController::class, 'showByCode']);
        Route::get('/{id}', [TypeCompteController::class, 'show']);

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
    | Comptes bancaires
    |--------------------------------------------------------------------------
    */
    // Routes pour les types de comptes
    Route::get('/types-comptes', [TypeCompteController::class, 'index']);

    Route::prefix('comptes')->group(function () {

        // Ouverture de compte
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
        Route::prefix('{compteId}')->group(function () {
            Route::get('/documents', [DocumentCompteController::class, 'index']);
            Route::post('/documents', [DocumentCompteController::class, 'store']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Frais & commissions
    |--------------------------------------------------------------------------
    */
    Route::prefix('frais-commissions')->group(function () {
        Route::get('/', [FraisCommissionController::class, 'index']);
        Route::get('/type-compte/{typeCompteId}', [FraisCommissionController::class, 'getByTypeCompte']);
        Route::post('/simuler', [FraisCommissionController::class, 'simulerFrais']);

        Route::post('/', [FraisCommissionController::class, 'store']);
        Route::get('/{id}', [FraisCommissionController::class, 'show']);
        Route::put('/{id}', [FraisCommissionController::class, 'update']);
        Route::delete('/{id}', [FraisCommissionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Application des frais
    |--------------------------------------------------------------------------
    */
    Route::prefix('frais-applications')->group(function () {
        Route::get('/', [FraisApplicationController::class, 'index']);

        Route::post('/appliquer-ouverture', [FraisApplicationController::class, 'appliquerFraisOuverture']);
        Route::post('/lancer-commissions-mensuelles', [FraisApplicationController::class, 'lancerCommissionsMensuelles']);
        Route::post('/lancer-commissions-sms', [FraisApplicationController::class, 'lancerCommissionsSMS']);
        Route::post('/calculer-interets', [FraisApplicationController::class, 'calculerInterets']);

        Route::put('/{id}/valider', [FraisApplicationController::class, 'validerApplication']);
        Route::get('/compte/{compte}/en-attente', [FraisApplicationController::class, 'getEnAttente']);
    });

    /*
    |--------------------------------------------------------------------------
    | Rubriques MATA (par compte)
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes/{compte}/rubriques-mata')->group(function () {
        Route::get('/', [MouvementRubriqueMataController::class, 'index']);
        Route::get('/recapitulatif', [MouvementRubriqueMataController::class, 'recapitulatif']);

        Route::post('/versement', [MouvementRubriqueMataController::class, 'versement']);
        Route::post('/retrait', [MouvementRubriqueMataController::class, 'retrait']);
        Route::post('/transferer', [MouvementRubriqueMataController::class, 'transferer']);
        Route::post('/repartir', [MouvementRubriqueMataController::class, 'repartir']);

        Route::get('/{rubrique}/historique', [MouvementRubriqueMataController::class, 'historiqueRubrique']);
        Route::get('/{rubrique}/solde', [MouvementRubriqueMataController::class, 'soldeRubrique']);
    });

    /*
    |--------------------------------------------------------------------------
    | Plan Comptable
    |--------------------------------------------------------------------------
    */
    Route::get('/plan-comptable/categories', [\App\Http\Controllers\PlanComptableController::class, 'getCategories']);
    Route::get('/plan-comptable/chapitres', [\App\Http\Controllers\PlanComptableController::class, 'getChapitres']);

    /*
    |--------------------------------------------------------------------------
    | Audit & Logs (permissions)
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:consulter logs')->group(function () {
        Route::get('/audit/logs', [AuditLogController::class, 'index']);
    });
});

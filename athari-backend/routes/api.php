<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\logs\AuditLogController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TypesCompteController;
use App\Http\Controllers\Plancomptable\PlanComptableController;
use App\Http\Controllers\Plancomptable\CategorieComptableController;


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

    // Chapitres comptables


Route::prefix('plan_comptable')->group(function () {
    // Routes pour les rubriques (371, 372...)
    Route::get('categories', [CategorieComptableController::class, 'index']);
    Route::post('categories', [CategorieComptableController::class, 'store']);

    // Routes pour les comptes de détail (37225000...)
    Route::get('comptes', [PlanComptableController::class, 'index']);
    Route::post('comptes', [PlanComptableController::class, 'store']);
        Route::put('comptes/{id}', [PlanComptableController::class, 'update']);

    Route::get('comptes/{planComptable}', [PlanComptableController::class, 'show']);
    Route::patch('comptes/{planComptable}/archive', [PlanComptableController::class, 'archive']);
});



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
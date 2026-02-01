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
use App\Http\Controllers\Plancomptable\PlanComptableController;
use App\Http\Controllers\Plancomptable\CategorieComptableController;
use App\Http\Controllers\frais\FraisCommissionController;
use App\Http\Controllers\frais\FraisApplicationController;
use App\Http\Controllers\frais\MouvementRubriqueMataController;
use App\Http\Controllers\Compte\DatContratController;
use App\Http\Controllers\Compte\DatTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Sessions\SessionAgenceController;
use App\Http\Controllers\Caisse\VersementController;
use App\Http\Controllers\Caisse\RetraitController;
use App\Http\Controllers\Caisse\SupervisionController;
use App\Http\Controllers\Caisse\GuichetController;
use App\Http\Controllers\Caisse\CaisseControllerC;
use App\Http\Controllers\Caisse\CaisseDashboardController;
use App\Http\Controllers\OperationDiversController;
use App\Http\Controllers\Caisse\JournalCaisseController;

// Credit Controllers
use App\Http\Controllers\Api\CreditProductController;
use App\Http\Controllers\Api\CreditApplicationController;
use App\Http\Controllers\Api\CreditApprovalController;
use App\Http\Controllers\Api\CreditCommitteeController;
use App\Http\Controllers\Api\CreditDocumentController;
use App\Http\Controllers\Api\CreditScheduleController;
use App\Http\Controllers\Api\CreditClientController;
use App\Http\Controllers\AARController;
use App\Http\Controllers\Api\CreditFlashController;


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

    /* --- Authentification --- */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    /* --- Crédits Types --- */
    Route::get('/credit-types', fn () => \App\Models\CreditType::active()->get());

    /* --- Credit Applications --- */
    Route::prefix('credit-applications')->group(function () {
        Route::get('/', [CreditApplicationController::class, 'index']);
        Route::get('/{creditApplication}', [CreditApplicationController::class, 'show']);

        Route::post('/', [CreditApplicationController::class, 'store']); // POST /api/credit-applications
        Route::put('/{creditApplication}', [CreditApplicationController::class, 'update']);
        Route::post('/{creditApplication}/submit', [CreditApplicationController::class, 'submit']);
        Route::post('/{creditApplication}/upload-documents', [CreditApplicationController::class, 'uploadDocuments']);
        Route::delete('/documents/{creditDocument}', [CreditApplicationController::class, 'deleteDocument']);
        Route::get('/credit-applications/{id}', [CreditApplicationController::class, 'show']);
        Route::get('/credit-applications', [CreditApplicationController::class, 'index']);

        // Middleware roles commentés pour test
        // Route::middleware('role:aar')->post('/{creditApplication}/review-aar', [CreditApplicationController::class, 'reviewAar']);
        // Route::middleware('role:chef_agence')->post('/{creditApplication}/decide-chef', [CreditApplicationController::class, 'decideChef']);
    });

    /* --- Credit Products --- */
    Route::get('/credit-products', [CreditProductController::class, 'index']);

    /* --- Credit Committees --- */
    Route::post('/credits/{id}/committee-agence', [CreditCommitteeController::class, 'agence']);
    Route::post('/credits/{id}/committee-siege', [CreditCommitteeController::class, 'siege']);

    /* --- Credit Approvals --- */
    Route::post('/credits/{id}/approvals', [CreditApprovalController::class, 'store']);

    /* --- Credit Documents --- */
    Route::post('/credits/{id}/documents', [CreditDocumentController::class, 'store']);
    Route::get('/credits/{id}/documents', [CreditDocumentController::class, 'index']);
    Route::get('/documents/{id}/download', [CreditDocumentController::class, 'download']);

    /* --- Credit Repayment Schedules --- */
    Route::get('/credits/{id}/schedule', [CreditScheduleController::class, 'show']);
    Route::post('/credits/{id}/schedule/{mois}/penalty', [CreditScheduleController::class, 'penalize']);

    /* --- Credit Contract --- */
    Route::get('/credits/{id}/contract', [CreditClientController::class, 'generatePdf']);
    Route::post('/credits/{id}/accept', [CreditClientController::class, 'accept']);

    /* --- Opérations Diverses (OD) --- */
    Route::prefix('operation-diverses')->group(function () {
        Route::get('/', [OperationDiversController::class, 'index']);
        Route::post('/', [OperationDiversController::class, 'store']);
        Route::get('/{operationDiverse}', [OperationDiversController::class, 'show']);
        Route::put('/{operationDiverse}', [OperationDiversController::class, 'update']);
        Route::delete('/{operationDiverse}', [OperationDiversController::class, 'destroy']);

        Route::get('/{operationDiverse}/historique', [OperationDiversController::class, 'historique']);
        Route::post('/{operationDiverse}/valider', [OperationDiversController::class, 'valider']); // middleware permission
        Route::post('/{operationDiverse}/rejeter', [OperationDiversController::class, 'rejeter']); // middleware permission
        Route::post('/{operationDiverse}/comptabiliser', [OperationDiversController::class, 'comptabiliser']); // middleware permission
        Route::post('/{operationDiverse}/annuler', [OperationDiversController::class, 'annuler']); // middleware permission

        Route::post('/{operationDiverse}/upload-justificatif', [OperationDiversController::class, 'uploadJustificatif']); // middleware permission
        Route::get('/{operationDiverse}/justificatif', [OperationDiversController::class, 'downloadJustificatif']);
        
        Route::get('/journal/liste', [OperationDiversController::class, 'journal']); // middleware permission
        Route::get('/statistiques/generales', [OperationDiversController::class, 'statistiques']);
        Route::get('/export/data', [OperationDiversController::class, 'export']); // middleware permission
    });

    /* --- Utilisateurs & Rôles --- */
    Route::prefix('users')->group(function () {
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::get('/permissions', [UserController::class, 'getPermissions']);
        Route::put('/{user}/sync-roles', [UserController::class, 'syncRoles']);
    });
    Route::apiResource('users', UserController::class);

    Route::prefix('roles')->group(function () {
        Route::get('/', [UserController::class, 'getRoles']);
        Route::post('/creer', [UserController::class, 'storeRole']);
        Route::put('/{role}', [UserController::class, 'updateRole']);
        Route::delete('/{role}', [UserController::class, 'destroyRole']);
        Route::post('/{role}/sync-permissions', [UserController::class, 'syncRolePermissions']);
    });

    /* --- Clients --- */
    Route::prefix('clients')->group(function () {
        Route::post('/physique', [ClientController::class, 'storePhysique']);
        Route::post('/morale', [ClientController::class, 'storeMorale']);
        Route::get('/', [ClientController::class, 'index']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });

    /* --- Agences --- */
    Route::get('/agencies', [AgencyController::class, 'index']);
    Route::post('/agencies', [AgencyController::class, 'store']); 
    Route::delete('/agencies/{id}', [AgencyController::class, 'destroy']); 
    Route::get('/agencies/{id}', [AgencyController::class, 'show']);
    Route::get('/agencies/{agency}/next-number', [ClientController::class, 'getNextNumber']);

    /* --- Plan Comptable --- */
    Route::prefix('plan-comptable')->group(function () {
        Route::get('categories', [CategorieComptableController::class, 'index']);
        Route::post('categories', [CategorieComptableController::class, 'store']);
        Route::get('comptes', [PlanComptableController::class, 'index']);
        Route::post('comptes', [PlanComptableController::class, 'store']);
        Route::put('comptes/{id}', [PlanComptableController::class, 'update']);
    });

    /* --- Types de Comptes --- */
    Route::prefix('types-comptes')->group(function () {
        Route::get('/', [TypeCompteController::class, 'index']);
        Route::post('/creer', [TypeCompteController::class, 'store']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        Route::get('/{id}', [TypeCompteController::class, 'show']);
    });

    /* --- Sessions d'agence --- */
    Route::prefix('sessions')->group(function () {
        Route::post('/ouvrir-agence', [SessionAgenceController::class, 'ouvrirAgence']);
        Route::post('/ouvrir-caisse', [SessionAgenceController::class, 'ouvrirCaisse']);
        Route::post('/fermer-caisse', [SessionAgenceController::class, 'fermerCaisse']);
        Route::get('/caisses/{caisse_session_id}/bilan', [SessionAgenceController::class, 'getBilanCaisse']);
    });

    /* --- Supervision --- */
    Route::prefix('supervision-caisse')->group(function () {
        Route::get('/attente', [SupervisionController::class, 'index']);
        Route::post('/approuver/{id}', [SupervisionController::class, 'approuver']);
    });

    /* --- Dashboard & Recherche --- */
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    // Route::get('/clients/search', [App\Http\Controllers\ClientSearchController::class, 'search']);


    

Route::middleware('auth:sanctum')->group(function () {
    // Pour tous les utilisateurs qui donnent un avis
    Route::get('/aar/credit-applications', [AARController::class, 'index']);
    Route::post('/aar/credit-applications/{id}/review', [AARController::class, 'review']);

    // Pour le chef d’agence ou superviseur
    Route::get('/chief/credit-applications', [AARController::class, 'applicationsForChief']);
});
// Ajoutez cette route
Route::get('/credit-applications/stats', [CreditApplicationController::class, 'stats']);


Route::middleware('auth:sanctum')->group(function () {
     // AAR : donner son avis → envoie automatique au chef d’agence
    Route::post(
        '/aar/credit-applications/{id}/avis',
        [AvisController::class, 'donnerAvisAAR']
    );
       // Chef d’agence : voir les dossiers envoyés par l’AAR
    Route::get(
        '/chef-agence/credit-applications',
        [CreditApplicationController::class, 'dossiersChefAgence']
    );
     // Chef d’agence : donner l’avis final
    Route::post(
        '/chef-agence/credit-applications/{id}/avis',
        [AvisController::class, 'donnerAvisChefAgence']
    );
   Route::get(
        '/credit-applications/{id}/avis',
        [AvisController::class, 'getAvis']
    );
});

Route::middleware('auth:sanctum')->group(function () {

    // AAR
    Route::get('/aar/credit-applications', [AARController::class, 'index']);
    Route::post('/aar/credit-applications/{id}/avis', [AARController::class, 'review']);

    // Chef d’agence
    Route::get('/chef-agence/credit-applications', [AARController::class, 'applicationsForChief']);

});

Route::prefix('credit-flash')->group(function () {
    Route::get('/', [CreditFlashController::class, 'index']);      // GET /api/credit-flash
    Route::post('/simuler', [CreditFlashController::class, 'simuler']); // POST /api/credit-flash/simuler
});


Route::prefix('credit-flash')->group(function () {
    // Public routes
    Route::get('/', [CreditFlashController::class, 'index']);
    Route::get('/grille', [CreditFlashController::class, 'grilleTarifaire']);
    Route::post('/simuler', [CreditFlashController::class, 'simuler']);
    Route::get('/check', [CreditFlashController::class, 'checkProduct']);
    Route::post('/amortissement', [CreditFlashController::class, 'amortissement']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/store', [CreditFlashController::class, 'store']);
    });
});

// Pour les autres contrôleurs existants
Route::middleware('auth:sanctum')->group(function () {
    // Vos autres routes...
});

// Dans le groupe middleware('auth:sanctum')
Route::get('/plan-comptable/{id}', function ($id) {
    try {
        $chapter = \App\Models\chapitre\PlanComptable::find($id);
        
        if (!$chapter) {
            return response()->json([
                'success' => false,
                'message' => 'Chapitre comptable non trouvé'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chapter->id,
                'code' => $chapter->code,
                'libelle' => $chapter->libelle,
                'nature_solde' => $chapter->nature_solde,
                'est_actif' => $chapter->est_actif,
                'categorie' => $chapter->categorie ? [
                    'id' => $chapter->categorie->id,
                    'type_compte' => $chapter->categorie->type_compte
                ] : null,
                'code_libelle' => $chapter->code . ' - ' . $chapter->libelle
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Erreur route plan-comptable: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur'
        ], 500);
    }
});

Route::get('/credit-products', [CreditProductController::class, 'index']);
Route::get('/credit-products/{code}', [CreditProductController::class, 'show']);

});

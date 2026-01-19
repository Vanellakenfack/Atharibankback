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
use  App\Http\Controllers\Caisse\VersementController;
use  App\Http\Controllers\Caisse\RetraitController;
use  App\Http\Controllers\Caisse\SupervisionController;
use App\Models\Caisse\CaisseDemandeValidation;
use App\Http\Controllers\Caisse\GuichetController;
use App\Http\Controllers\Caisse\CaisseControllerC;
use App\Http\Controllers\Caisse\CaisseDashboardController;



use App\Http\Controllers\OperationDiversController;
use App\Http\Controllers\Caisse\JournalCaisseController;


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

    /* ==========================================
        Routes pour les OD (Opérations Diverses) - AJOUTER TOUT CE BLOC
    ========================================== */ 
    Route::prefix('operation-diverses')->group(function () {
        
        // CRUD de base
        Route::get('/', [OperationDiversController::class, 'index']);
        Route::post('/', [OperationDiversController::class, 'store']);
        Route::get('/{operationDiverse}', [OperationDiversController::class, 'show']);
        Route::put('/{operationDiverse}', [OperationDiversController::class, 'update']);
        Route::delete('/{operationDiverse}', [OperationDiversController::class, 'destroy']);
        
        // Historique et suivi
        Route::get('/{operationDiverse}/historique', [OperationDiversController::class, 'historique']);
        
        // Workflow de validation
        Route::post('/{operationDiverse}/valider', [OperationDiversController::class, 'valider'])
            ->middleware('permission:valider les od');
        
        Route::post('/{operationDiverse}/rejeter', [OperationDiversController::class, 'rejeter'])
            ->middleware('permission:valider les od');
        
        Route::post('/{operationDiverse}/comptabiliser', [OperationDiversController::class, 'comptabiliser'])
            ->middleware('permission:comptabiliser od');
        
        Route::post('/{operationDiverse}/annuler', [OperationDiversController::class, 'annuler'])
            ->middleware('permission:annuler od');
        
        // Gestion des justificatifs
        Route::post('/{operationDiverse}/upload-justificatif', [OperationDiversController::class, 'uploadJustificatif'])
            ->middleware('permission:saisir od');
        
        Route::get('/{operationDiverse}/justificatif', [OperationDiversController::class, 'downloadJustificatif']);
        
        // Reporting et statistiques
        Route::get('/journal/liste', [OperationDiversController::class, 'journal'])
            ->middleware('permission:exporter od');
        
        Route::get('/statistiques/generales', [OperationDiversController::class, 'statistiques']);
        
        Route::get('/export/data', [OperationDiversController::class, 'export'])
            ->middleware('permission:exporter od');
        
        // Recherche et filtres
        Route::get('/recherche/avancee', [OperationDiversController::class, 'rechercheAvancee']);
        
        // Vues spécialisées par rôle
        Route::get('/etat/en-attente-validation', [OperationDiversController::class, 'enAttenteValidation'])
            ->middleware('permission:valider les od');
        
        Route::get('/etat/a-comptabiliser', [OperationDiversController::class, 'aComptabiliser'])
            ->middleware('permission:comptabiliser od');
    });

    /*
    |--------------------------------------------------------------------------
    | Utilisateurs & rôles
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::get('/permissions', [UserController::class, 'getPermissions']);
        Route::put('/{user}/sync-roles', [UserController::class, 'syncRoles']);

    });
    Route::apiResource('users', UserController::class);

        // Routes pour les rôles
    Route::prefix('roles')->group(function () {
        Route::get('/', [UserController::class, 'getRoles']);
        Route::post('/creer', [UserController::class, 'storeRole']);
        Route::put('/{role}', [UserController::class, 'updateRole']);
        Route::delete('/{role}', [UserController::class, 'destroyRole']);
        Route::post('/{role}/sync-permissions', [UserController::class, 'syncRolePermissions']);
    });

       // Routes pour les permissions
    Route::prefix('permissions')->group(function () {
        Route::get('/', [UserController::class, 'getPermissions']);
        Route::post('/creer', [UserController::class, 'storePermission']);
        Route::put('/{permission}', [UserController::class, 'updatePermission']);
        Route::delete('/{permission}', [UserController::class, 'destroyPermission']);
    });

    /*
    |--------------------------------------------------------------------------
    | Agences
    |--------------------------------------------------------------------------
    */

   // Route::apiResource('agencies', AgencyController::class);

  
    Route::get('/agencies', [AgencyController::class, 'index']);
    Route::post('/agencies', [AgencyController::class, 'store']); 
    Route::delete('/agencies/{id}', [AgencyController::class, 'destroy']); 
    Route::get('/agencies/{id}', [AgencyController::class, 'show']);
    Route::get('/agencies/{agency}/next-number', [ClientController::class, 'getNextNumber']);

    // ==========================================
    // DÉPLACER CES ROUTES ICI DANS LE GROUPE auth:sanctum
    // ==========================================
    
    // Routes pour les guichets
    Route::prefix('caisse')->name('caisse.')->group(function () {
        
        // Routes pour les Guichets (index, create, store, show, edit, update, destroy)
        // URL: /caisse/guichets
        Route::resource('guichets', GuichetController::class);

        // Routes pour les Caisses (index, create, store, show, edit, update, destroy)
        // URL: /caisse/caisses
        Route::resource('caisses', CaisseControllerC::class);
            // Route pour récupérer le journal de caisse
    Route::get('/journal', [JournalCaisseController::class, 'obtenirJournal']);
    
    // Route pour exporter en PDF
    Route::get('/journal/export-pdf', [JournalCaisseController::class, 'exportPdf']);

    });
    
    // AJOUTER CETTE ROUTE POUR L'EXPORT PDF DU JOURNAL DE CAISSE
   // Route::get('/caisse/journal/export-pdf', [JournalCaisseController::class, 'exportPdf']);

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
        // Catégories comptables
        Route::get('categories', [CategorieComptableController::class, 'index']);
        Route::post('categories', [CategorieComptableController::class, 'store']);
        Route::put('categories/{id}', [CategorieComptableController::class, 'update']);

        // Comptes comptables
        Route::get('comptes', [PlanComptableController::class, 'index']);
        Route::post('comptes', [PlanComptableController::class, 'store']);
        Route::get('comptes/{planComptable}', [PlanComptableController::class, 'show']);
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
        Route::post('/creer', [TypeCompteController::class, 'store']);
        Route::get('/statistiques', [TypeCompteController::class, 'statistiques']);
        Route::get('/rubriques-mata', [TypeCompteController::class, 'getRubriquesMata']);
        Route::get('/durees-blocage', [TypeCompteController::class, 'getDureesBlocage']);
        Route::get('/code/{code}', [TypeCompteController::class, 'showByCode']);

        Route::put('/{id}', [TypeCompteController::class, 'update']);
        Route::delete('/{id}', [TypeCompteController::class, 'destroy']);
         Route::get('/{id}', [TypeCompteController::class, 'show']);
           // Simulation
        Route::post('/{id}/simuler-frais', [TypeCompteController::class, 'simulerFrais']);

        // Utilitaires
        Route::get('/chapitres/disponibles', [TypeCompteController::class, 'getChapitresDisponibles']);

    });

    /*
    |--------------------------------------------------------------------------
    | Gestion des DAT (Dépôts à Terme)
    |--------------------------------------------------------------------------
    */
    Route::prefix('dat')->group(function () {
        // Liste des contrats pour le tableau
        Route::get('/contracts', [DatContratController::class, 'index']); 
        Route::post('contracts', [DatContratController::class, 'store']); // Route pour créer

       Route::post('{id}/valider', [DatContratController::class, 'valider']);
        // Liste des types (offres) pour la modale
        Route::get('/types', [DatTypeController::class, 'index']); 
        Route::post('/types', [DatTypeController::class, 'store']);
        Route::put('/types/{id}', [DatTypeController::class, 'update']);
        
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

         //journal ouverture de compte
       Route::get('/journal-ouverture', [CompteController::class, 'getJournalOuvertures']);
              Route::get('/cloture journe', [CompteController::class, 'clotureJourneeOuvertures']);
              Route::get('/journal-pdf', [CompteController::class, 'exporterJournalPdf']);

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
        Route::get('/{id}/details', [CompteController::class, 'getParametresTypeCompte']);

        // Documents
        Route::prefix('{compte}')->group(function () {
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
    | Audit & logs (permissions)
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:consulter logs')->group(function () {
        Route::get('/audit/logs', [AuditLogController::class, 'index']);
    });

    /**dashbord */
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    /*
    |--------------------------------------------------------------------------
    | Sessions d'agence
    |--------------------------------------------------------------------------
    */
    Route::prefix('sessions')->group(function () {
        
        // Sécurité pour l'Agence
        Route::post('/ouvrir-agence', [SessionAgenceController::class, 'ouvrirAgence'])
             ->middleware('permission:ouverture/fermeture agence');

        // Sécurité pour le Guichet
        Route::post('/ouvrir-guichet', [SessionAgenceController::class, 'ouvrirGuichet'])
             ->middleware('permission:ouverture/fermeture guichet');
        
        Route::post('/fermer-guichet', [SessionAgenceController::class, 'fermerGuichet'])
             ->middleware('permission:ouverture/fermeture guichet');

        // Sécurité pour la Caisse
        Route::post('/ouvrir-caisse', [SessionAgenceController::class, 'ouvrirCaisse'])
             ->middleware('permission:ouverture/fermeture caisse');
             
        Route::post('/fermer-caisse', [SessionAgenceController::class, 'fermerCaisse'])
             ->middleware('permission:ouverture/fermeture caisse');

        // AJOUTER CES ROUTES MANQUANTES :
        Route::post('/reouvrir-caisse', [SessionAgenceController::class, 'reouvrirCaisse'])
             ->middleware('permission:ouverture/fermeture caisse');

        // AJOUTER CETTE ROUTE (utilisée dans getBilanCaisse) :
        Route::get('/caisses/{caisse_session_id}/bilan', [SessionAgenceController::class, 'getBilanCaisse']);

        // Clôture finale
        Route::post('/fermer-agence', [SessionAgenceController::class, 'fermerAgence'])
             ->middleware('permission:ouverture/fermeture agence');
    });

    // AJOUTER CE GROUPE DE ROUTES POUR LA SUPERVISION DE CAISSE :
    Route::prefix('supervision-caisse')->group(function () {
        Route::get('/attente', [SupervisionController::class, 'index']);
        Route::post('/approuver/{id}', [SupervisionController::class, 'approuver']);
        Route::post('/rejeter/{id}', [SupervisionController::class, 'rejeter']);
    });

    // AJOUTER CETTE ROUTE POUR LES DEMANDES EN ATTENTE :
    Route::get('/assistant/demandes-en-attente', function() {
        return \App\Models\Caisse\CaisseDemandeValidation::where('statut', 'EN_ATTENTE')->get();
    });

});

// AJOUTER CE GROUPE DE ROUTES POUR LES TRANSACTIONS DE CAISSE :
Route::middleware(['auth:sanctum', 'verifier.caisse'])->prefix('caisse')->group(function () {
    
    // API Versement (Dépôt)
    Route::post('/versement', [VersementController::class, 'store']);
    
    // API Retrait
    Route::post('/retrait', [RetraitController::class, 'store']);
    // routes/api.php
   Route::get('/recu/{id}', [RetraitController::class, 'imprimerRecu']);
      Route::get('/recu/{id}', [VersementController::class, 'imprimerRecu']);
      Route::get('/dashboard', [CaisseDashboardController::class, 'index']);
    Route::get('/recapitulatif/{sessionId}', [CaisseDashboardController::class, 'recapitulatifFlux']);


});
    
// AJOUTER CETTE ROUTE POUR L'IMPRESSION DE RECU :
Route::get('/recu/{id}', [App\Http\Controllers\Caisse\RetraitController::class, 'imprimerRecu']);
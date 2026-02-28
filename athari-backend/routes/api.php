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
use App\Http\Controllers\Comptabilite\Balance\BalanceController;
use App\Http\Controllers\Sessions\SessionAgenceController;
use  App\Http\Controllers\Caisse\VersementController;
use  App\Http\Controllers\Caisse\RetraitController;
use  App\Http\Controllers\Caisse\SupervisionController;
use App\Models\Caisse\CaisseDemandeValidation;
use App\Http\Controllers\Caisse\GuichetController;
use App\Http\Controllers\Caisse\CaisseControllerC;
use App\Http\Controllers\Caisse\CaisseDashboardController;
use App\Http\Controllers\Compte\CompteValidationController;
    use App\Http\Controllers\Caisse\RetraitDistanceController;

use App\Http\Controllers\OperationDiversController;
use App\Http\Controllers\Caisse\JournalCaisseController;
use App\Http\Controllers\Gestionnaire\GestionnaireController;
use App\Http\Controllers\Caisse\CaisseOperationController;
    use App\Http\Controllers\Caisse\JournalDigitalController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
    Route::get('/journal-digital/export-pdf', [JournalDigitalController::class, 'genererPdf']);


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
        Routes pour les OD (Opérations Diverses)
    ========================================== */ 
Route::prefix('operation-diverses')->group(function () {
    // CRUD de base
    Route::get('/', [OperationDiversController::class, 'index']);
    Route::post('/', [OperationDiversController::class, 'store']);
    Route::get('/{operationDiverse}', [OperationDiversController::class, 'show']);
    Route::put('/{operationDiverse}', [OperationDiversController::class, 'update']);
    Route::delete('/{operationDiverse}', [OperationDiversController::class, 'destroy']);
    
    // Saisie par modèle
    Route::post('/saisie-modele', [OperationDiversController::class, 'storeParModele'])
        ->middleware('permission:saisir od');
    
    // Nouvelle route pour MATA BOOST avec répartition clients
    Route::post('/mata-boost-avec-clients', [OperationDiversController::class, 'creerMataBoostAvecClients'])
        ->middleware('permission:saisir od');
        
        // Route pour récupérer les clients d'un compte MATA BOOST
    Route::get('/comptes/mata-boost/{compteMataBoostId}/clients', [OperationDiversController::class, 'getClientsParCompteMataBoost']);
    
    /*Route::post('/epargne-journaliere', [OperationDiversController::class, 'creerEpargneJournaliere'])
        ->middleware('permission:saisir od');*/

        // new Route pour Épargne Journalière avec répartition clients
    Route::post('/epargne-journaliere-avec-clients', [OperationDiversController::class, 'creerEpargneJournaliereAvecClients'])
        ->middleware('permission:saisir od');
    
    // new Route pour récupérer les clients d'un compte Épargne Journalière
    Route::get('/comptes/epargne/{compteEpargneId}/clients', [OperationDiversController::class, 'getClientsParCompteEpargne']);
    
    Route::post('/charges', [OperationDiversController::class, 'creerCharge'])
        ->middleware('permission:saisir od');
    
    // Workflow de validation multi-niveaux
    Route::post('/{operationDiverse}/valider-agence', [OperationDiversController::class, 'validerAgence'])
        ->middleware('permission:valider od agence');
    
    Route::post('/{operationDiverse}/valider-comptable', [OperationDiversController::class, 'validerComptable'])
        ->middleware('permission:valider od comptable');
    
    Route::post('/{operationDiverse}/valider-dg', [OperationDiversController::class, 'validerDG'])
        ->middleware('permission:valider od dg');

    Route::post('{id}/enregistrer-code-dg', [OperationDiversController::class, 'enregistrerCodeValidationDG'])
        ->middleware('permission:valider od dg');

    // Récupérer les codes de validation DG (SANS ID dans l'URL)
    Route::get('/codes/validation-dg', [OperationDiversController::class, 'getCodesValidationDG'])
        ->name('operation-diverses.codes-validation-dg');

    Route::post('/{operationDiverse}/rejeter', [OperationDiversController::class, 'rejeter'])
        ->middleware('permission:valider les od');
    
    Route::post('/{operationDiverse}/comptabiliser', [OperationDiversController::class, 'comptabiliser'])
        ->middleware('permission:comptabiliser od');
    
    Route::post('/{operationDiverse}/annuler', [OperationDiversController::class, 'annuler'])
        ->middleware('permission:annuler od');
    
    // Historique et suivi
    Route::get('/{operationDiverse}/historique', [OperationDiversController::class, 'historique'])
        ->middleware('permission:consulter logs');
    
    // Gestion des justificatifs
    Route::post('/{operationDiverse}/upload-justificatif', [OperationDiversController::class, 'uploadJustificatif'])
        ->middleware('permission:saisir od');
    
    Route::get('/{operationDiverse}/justificatif', [OperationDiversController::class, 'downloadJustificatif']);
    
    Route::get('/journal/pdf', [OperationDiversController::class, 'journalPDF'])
        ->middleware('permission:exporter od');

    // Reporting et statistiques
    Route::get('/journal/liste', [OperationDiversController::class, 'journal'])
        ->middleware('permission:exporter od');


    Route::get('/statistiques/generales', [OperationDiversController::class, 'statistiques']);
    
    Route::get('/export/data', [OperationDiversController::class, 'export'])
        ->middleware('permission:exporter od');
    
    // Recherche et filtres
    Route::get('/recherche/avancee', [OperationDiversController::class, 'rechercheAvancee']);
    
    // Vues spécialisées par rôle
    Route::get('/etat/en-attente-validation-agence', [OperationDiversController::class, 'enAttenteValidationAgence'])
        ->middleware('permission:valider od agence');
    
    Route::get('/etat/en-attente-validation-comptable', [OperationDiversController::class, 'enAttenteValidationComptable'])
        ->middleware('permission:valider od comptable');
    
    Route::get('/etat/en-attente-validation-dg', [OperationDiversController::class, 'enAttenteValidationDG'])
        ->middleware('permission:valider od dg');
    
    Route::get('/etat/a-comptabiliser', [OperationDiversController::class, 'aComptabiliser'])
        ->middleware('permission:comptabiliser od');
    
    // Gestion des modèles
    Route::prefix('modeles')->group(function () {
        Route::get('/', [OperationDiversController::class, 'listerModeles'])
            ->middleware('permission:gerer modeles od');
        
        Route::post('/', [OperationDiversController::class, 'creerModele'])
            ->middleware('permission:gerer modeles od');
        
        Route::get('/{modele}', [OperationDiversController::class, 'afficherModele'])
            ->middleware('permission:gerer modeles od');
        
        Route::put('/{modele}', [OperationDiversController::class, 'modifierModele'])
            ->middleware('permission:gerer modeles od');
        
        Route::delete('/{modele}', [OperationDiversController::class, 'supprimerModele'])
            ->middleware('permission:gerer modeles od');
    });
    
    // Récupération des comptes spécifiques
    Route::get('/comptes/collecteurs', [OperationDiversController::class, 'getComptesCollecteurs']);
    Route::get('/comptes/mata-boost', [OperationDiversController::class, 'getComptesMataBoost']);
    Route::get('/comptes/epargne-journaliere', [OperationDiversController::class, 'getComptesEpargneJournaliere']);
    Route::get('/comptes/charges', [OperationDiversController::class, 'getComptesCharges']);
    Route::get('/comptes/passage', [OperationDiversController::class, 'getComptesPassage']);
    Route::get('/caisses/liste', [OperationDiversController::class, 'getCaisses']);
    
    // Récupération des références
    Route::get('/agences/liste', [OperationDiversController::class, 'getAgences']);
    Route::get('/comptes/plan', [OperationDiversController::class, 'getComptesPlan']);
    Route::get('/comptes/clients', [OperationDiversController::class, 'getComptesClients']);
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
    Route::delete('/agencies/{id}', [AgencyController::class    , 'destroy']); 
    Route::get('/agencies/{id}', [AgencyController::class, 'show']);
    Route::get('/agencies/{agency}/next-number', [ClientController::class, 'getNextNumber']);

    // ==========================================
    // DÉPLACER CES ROUTES ICI DANS LE GROUPE auth:sanctum
    // ==========================================
        Route::post('/retraits/demandes-validation', [SupervisionController::class, 'storeDemandeValidation']);

    // Routes pour les guichets
    Route::prefix('caisse')->name('caisse')->group(function () {
        
        // Routes pour les Guichets (index, create, store, show, edit, update, destroy)
        // URL: /caisse/guichets
        // Lectures sans middleware
        Route::get('/guichets', [GuichetController::class, 'index'])->name('guichets.index');
        Route::get('/guichets/{guichet}', [GuichetController::class, 'show'])->name('guichets.show');
        Route::get('/guichets/{guichet}/edit', [GuichetController::class, 'edit'])->name('guichets.edit');
        Route::get('/guichets/create', [GuichetController::class, 'create'])->name('guichets.create');
        
        // Écritures avec middleware
        Route::post('/guichets', [GuichetController::class, 'store'])
            ->name('guichets.store')
            ->middleware('check.agence.ouverte');
        Route::put('/guichets/{guichet}', [GuichetController::class, 'update'])
            ->name('guichets.update')
            ->middleware('check.agence.ouverte');
        Route::delete('/guichets/{guichet}', [GuichetController::class, 'destroy'])
            ->name('guichets.destroy')
            ->middleware('check.agence.ouverte');

        // Routes pour les Caisses (index, create, store, show, edit, update, destroy)
        // URL: /caisse/caisses
        // Lectures sans middleware
        Route::get('/caisses', [CaisseControllerC::class, 'index'])->name('caisses.index');
        Route::get('/caisses/{caisse}', [CaisseControllerC::class, 'show'])->name('caisses.show');
        Route::get('/caisses/{caisse}/edit', [CaisseControllerC::class, 'edit'])->name('caisses.edit');
        Route::get('/caisses/create', [CaisseControllerC::class, 'create'])->name('caisses.create');
        
        // Écritures avec middleware
        Route::post('/caisses', [CaisseControllerC::class, 'store'])
            ->name('caisses.store')
            ->middleware('check.agence.ouverte');
        Route::put('/caisses/{caisse}', [CaisseControllerC::class, 'update'])
            ->name('caisses.update')
            ->middleware('check.agence.ouverte');
        Route::delete('/caisses/{caisse}', [CaisseControllerC::class, 'destroy'])
            ->name('caisses.destroy')
            ->middleware('check.agence.ouverte');
        
        // Journal et statistiques (lectures)
        Route::get('/journal', [JournalCaisseController::class, 'obtenirJournal']);
        Route::get('/journal/export-pdf', [JournalCaisseController::class, 'exportPdf']);
        
        // Opérations de caisse (écritures)
        Route::post('/operation', [CaisseOperationController::class, 'store'])
             ->name('caisse.operation.store')
             ->middleware('check.agence.ouverte');

    });

    Route::get('/journal-digital', [JournalDigitalController::class, 'index'])->name('journal.digital');
    
    // AJOUTER CETTE ROUTE POUR L'EXPORT PDF DU JOURNAL DE CAISSE
   // Route::get('/caisse/journal/export-pdf', [JournalCaisseController::class, 'exportPdf']);

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->group(function () {
        Route::get('/export-pdf', [ClientController::class, 'exportPdf']);
        Route::post('/physique', [ClientController::class, 'storePhysique'])
            ->middleware('check.agence.ouverte');
        Route::post('/morale', [ClientController::class, 'storeMorale'])
            ->middleware('check.agence.ouverte');
        Route::get('/', [ClientController::class, 'index']);
        
        // Route avec paramètre en dernier
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update'])
            ->middleware('check.agence.ouverte');
        Route::delete('/{id}', [ClientController::class, 'destroy'])
            ->middleware('check.agence.ouverte');
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


    
    // Route pour charger les données (API React)
    Route::get('/comptes/{id}/historique', [CompteController::class, 'getHistorique']);

    // Route pour l'exportation PDF
    Route::get('/comptes/{id}/export-pdf', [CompteController::class, 'exporterHistoriquePdf']);


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
        Route::post('contracts', [DatContratController::class, 'store'])
            ->middleware('can:saisir dat', 'check.agence.ouverte');

       Route::post('{id}/valider', [DatContratController::class, 'valider'])
           ->middleware('can:valider dat', 'check.agence.ouverte');
        // Liste des types (offres) pour la modale
        Route::get('/types', [DatTypeController::class, 'index']); 
        Route::post('/types', [DatTypeController::class, 'store']);
        Route::put('/types/{id}', [DatTypeController::class, 'update']);
        
        // Action de souscription
        Route::post('/subscribe', [DatContratController::class, 'store'])
            ->middleware('can:saisir dat', 'check.agence.ouverte'); 
        Route::post('/simulate', [DatContratController::class, 'simulate'])
            ->middleware('can:saisir dat', 'check.agence.ouverte');
        
        // Actions individuelles
        Route::get('/{id}', [DatContratController::class, 'show']);
        Route::post('/{id}/cloturer', [DatContratController::class, 'cloturer'])
            ->middleware('can:cloturer dat', 'check.agence.ouverte');

    });

    /*
    |--------------------------------------------------------------------------
    | Comptes bancaires
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes')->group(function () {
      Route::get('/en-instruction', [CompteValidationController::class, 'getComptesEnInstruction']);

         //journal ouverture de compte (rapports - pas besoin d'agence ouverte)
       Route::get('/journal-ouverture', [CompteController::class, 'getJournalOuvertures']);
              Route::get('/cloture journe', [CompteController::class, 'clotureJourneeOuvertures']);
              Route::get('/journal-pdf', [CompteController::class, 'exporterJournalPdf']);

        // Ouverture de compte - opération complexe (protégée par middleware)
        Route::get('/init', [CompteController::class, 'initOuverture'])
            ->middleware('check.agence.ouverte');
        Route::post('/etape1/valider', [CompteController::class, 'validerEtape1'])
            ->middleware('check.agence.ouverte');

        Route::post('/etape2/valider', [CompteController::class, 'validerEtape2'])
            ->middleware('check.agence.ouverte');
             
        Route::post('/etape3/valider', [CompteController::class, 'validerEtape3'])
            ->middleware('check.agence.ouverte');


        // CRUD - Lectures simples (pas besoin d'agence ouverte)
        Route::get('/', [CompteController::class, 'index']);
        Route::get('/{id}', [CompteController::class, 'show']);
        Route::get('/{id}/details', [CompteController::class, 'getParametresTypeCompte']);

        // CRUD - Écritures/modifications (opérations complexes - middleware requis)
        Route::post('/creer', [CompteController::class, 'store'])
            ->middleware('check.agence.ouverte');
        Route::put('/{id}', [CompteController::class, 'update'])
            ->middleware('check.agence.ouverte');
        Route::delete('/{id}', [CompteController::class, 'destroy'])
            ->middleware('check.agence.ouverte');
            

        // Actions spécifiques (opérations complexes - middleware requis)
        Route::post('/{id}/cloturer', [CompteController::class, 'cloturer'])
            ->middleware('check.agence.ouverte');

        // Documents - lecture simple
        Route::prefix('{compte}')->group(function () {
            Route::get('/documents', [DocumentCompteController::class, 'index']);
            Route::post('/documents', [DocumentCompteController::class, 'store'])
                ->middleware('check.agence.ouverte');
              });

        // Validation - opérations complexes (middleware requis)
        Route::post('{id}/valider', [CompteValidationController::class, 'valider'])
             ->middleware('check.agence.ouverte');    

         Route::post('{id}/rejeter', [CompteValidationController::class, 'rejeter'])
             ->middleware('check.agence.ouverte');
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
        Route::get('/etat-agence/{agenceSessionId}', [SessionAgenceController::class, 'getEtatAgence']);
        Route::get('/bilan-caisse/{id}', [SessionAgenceController::class, 'getBilanCaisse']);
        Route::get('/guichets/disponibles/{agenceSessionId}', [GuichetController::class, 'getGuichetsDisponibles']);
        Route::get('/caisses/disponibles/{guichetSessionId}', [CaisseControllerC::class, 'getCaissesDisponiblesParGuichet']);

        // --- AJOUTER CETTE ROUTE POUR LE TFJ ---
        Route::post('/traiter-bilan-agence', [SessionAgenceController::class, 'executerTraitementFinJournee'])
        ->middleware('permission:ouverture/fermeture agence');

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

        Route::get('/imprimer-brouillard/{id}', [SessionAgenceController::class, 'imprimerBrouillard']);
    
    
         // Routes pour récupérer les sessions actives
    Route::get('actuelles', [SessionAgenceController::class, 'getSessionsActuelles']);
    Route::get('/agence/active', [SessionAgenceController::class, 'getAgenceActive']);
    Route::get('/guichet/active', [SessionAgenceController::class, 'getGuichetActive']);
    Route::get('/caisse/active', [SessionAgenceController::class, 'getCaisseActive']);
   
    
    
    
    });

    // AJOUTER CE GROUPE DE ROUTES POUR LA SUPERVISION DE CAISSE :
    Route::prefix('supervision-caisse')->group(function () {
        Route::get('/attente', [SupervisionController::class, 'index']);
        Route::post('/approuver/{id}', [SupervisionController::class, 'approuver'])
            ;
        Route::post('/rejeter/{id}', [SupervisionController::class, 'rejeter']);
        Route::get('/codes-approbation', [SupervisionController::class, 'codesApprobation']);

    });

    // AJOUTER CETTE ROUTE POUR LES DEMANDES EN ATTENTE :
    Route::get('/assistant/demandes-en-attente', function() {
        return \App\Models\Caisse\CaisseDemandeValidation::where('statut', 'EN_ATTENTE')->get();
    });

    Route::prefix('gestionnaires')->group(function () {
        Route::get('/', [GestionnaireController::class, 'index']);
        Route::get('/{id}', [GestionnaireController::class, 'show']);
        Route::post('/', [GestionnaireController::class, 'store']);
        Route::put('/{id}', [GestionnaireController::class, 'update']);
        Route::delete('/{id}', [GestionnaireController::class, 'destroy']);
        
        // Routes supplémentaires
        Route::get('/agence/{agenceId}', [GestionnaireController::class, 'parAgence']);
        Route::get('/corbeille', [GestionnaireController::class, 'corbeille']);
        Route::post('/{id}/restaurer', [GestionnaireController::class, 'restaurer']);
        Route::delete('/{id}/force', [GestionnaireController::class, 'supprimerDefinitivement']);
    });


    // Routes pour le caissier
    Route::post('/caisse/retrait-distance', [RetraitDistanceController::class, 'store'])
        ->middleware('check.agence.ouverte');

    
    // Routes pour le Chef d'Agence
    Route::get('caisse/retrait-distance/en-attente', [RetraitDistanceController::class, 'enAttente']);
        Route::get('caisse/retrait-distance/approuvees', [RetraitDistanceController::class, 'listeApprouvees']);
                Route::get('caisse/retrait-distance/rejetes', [RetraitDistanceController::class, 'listeRejetees'])
            ->middleware('check.agence.ouverte');



    Route::post('caisse/retrait-distance/{id}/approuver', [RetraitDistanceController::class, 'approuver'])
        ->middleware('check.agence.ouverte');

    Route::post('caisse/retrait-distance/{id}/rejeter', [RetraitDistanceController::class, 'rejeter'])
        ->middleware('check.agence.ouverte');

    Route::post('caisse/retrait-distance/{id}/confirmer', [RetraitDistanceController::class, 'confirmer'])
        ->middleware('check.agence.ouverte');
 // Validation finale Caissière


 //balance 
Route::prefix('comptabilite/balance')->group(function () {
    // Données JSON
    Route::get('/auxiliaire', [BalanceController::class, 'getBalanceAuxiliaire']);
    Route::get('/generale', [BalanceController::class, 'getBalanceGenerale']);

    // Exports Auxiliaire (Détails clients)
    Route::get('/auxiliaire/export-excel', [BalanceController::class, 'exporterExcelAuxiliaire']);
    Route::get('/auxiliaire/export-pdf', [BalanceController::class, 'exporterPdfAuxiliaire']);

    // Exports Générale (Grands comptes)
    Route::get('/generale/export-excel', [BalanceController::class, 'exporterExcelGenerale']);
    Route::get('/generale/export-pdf', [BalanceController::class, 'exporterPdfGenerale']); // <-- Celle-ci manquait probablement
    // Ajoutez ici la route PDF générale si vous créez la méthode plus tard
});
});



// AJOUTER CE GROUPE DE ROUTES POUR LES TRANSACTIONS DE CAISSE :
Route::middleware(['auth:sanctum', 'verifier.caisse','check.agence.ouverte'])->prefix('caisse')->group(function () {
    
    // API Versement (Dépôt)
    Route::post('/versement', [VersementController::class, 'store']);
   
    // API Retrait
    Route::post('/retrait', [RetraitController::class, 'store']);
    // routes/api.php
   Route::get('/recu/{id}', [RetraitController::class, 'imprimerRecu']);
      Route::get('/recu/{id}', [VersementController::class, 'imprimerRecu']);
      Route::get('/dashboard', [CaisseDashboardController::class, 'index']);
    Route::get('/recapitulatif/{sessionId}', [CaisseDashboardController::class, 'recapitulatifFlux']);



    
// AJOUTER CETTE ROUTE POUR L'IMPRESSION DE RECU :
//Route::get('/recu/{id}', [App\Http\Controllers\Caisse\RetraitController::class, 'imprimerRecu']);
});


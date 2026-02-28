<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OD\OperationDiverse;
use App\Services\OperationDiverseService;
use App\Models\OD\OdModele;
use App\Models\OD\OdModeleLigne;
use App\Models\OD\OdWorkflow;
use App\Models\Agency;
use App\Models\chapitre\PlanComptable;
use App\Models\compte\Compte;
use App\Models\Caisse;
use App\Models\Guichet;
use App\Models\compte\MouvementComptable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OperationDiversController extends Controller
{
    public function __construct()
    {
        // Note: Middleware 'check.agence.ouverte' est appliqué au niveau des routes (api.php)
        
        $this->middleware('permission:saisir od', ['only' => ['store', 'storeParModele', 'creerMataBoost', 'creerEpargneJournaliere', 'creerCharge', 'uploadJustificatif']]);
        $this->middleware('permission:valider od agence', ['only' => ['validerAgence']]);
        $this->middleware('permission:valider od comptable', ['only' => ['validerComptable']]);
        $this->middleware('permission:valider od dg', ['only' => ['validerDG']]);
        $this->middleware('permission:rejeter od', ['only' => ['rejeter']]);
        $this->middleware('permission:comptabiliser od', ['only' => ['comptabiliser']]);
        $this->middleware('permission:annuler od', ['only' => ['annuler']]);
        $this->middleware('permission:consulter logs od', ['only' => ['historique']]);
        $this->middleware('permission:gerer modeles od', ['only' => ['creerModele', 'listerModeles', 'afficherModele', 'modifierModele', 'supprimerModele', 'activerDesactiverModele']]);
        $this->middleware('permission:exporter od', ['only' => ['export', 'journal']]);
    }

    /**
     * Liste des OD (API)
     */
    public function index(Request $request)
    {
        try {
            $query = OperationDiverse::with([
                'agence', 
                'saisiPar', 
                'validePar', 
                'compteDebit', 
                'compteCredit',
                'compteDebitPrincipal',
                'compteCreditPrincipal',
                'workflow.validateur'
            ])->latest('date_operation');
            
            // Filtres
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }
            
            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }
            
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }
            
            if ($request->filled('type_collecte')) {
                $query->where('type_collecte', $request->type_collecte);
            }
            
            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }
            
            if ($request->filled('sens_operation')) {
                $query->where('sens_operation', $request->sens_operation);
            }
            
            if ($request->filled('est_collecte')) {
                $query->where('est_collecte', $request->boolean('est_collecte'));
            }
            
            if ($request->filled('est_bloque')) {
                $query->where('est_bloque', $request->boolean('est_bloque'));
            }
            
            if ($request->filled('code_operation')) {
                $query->where('code_operation', $request->code_operation);
            }
            
            if ($request->filled('numero_guichet')) {
                $query->where('numero_guichet', 'like', "%{$request->numero_guichet}%");
            }
            
            if ($request->filled('numero_piece')) {
                $query->where('numero_piece', 'like', "%{$request->numero_piece}%");
            }
            
            if ($request->filled('numero_bordereau')) {
                $query->where('numero_bordereau', 'like', "%{$request->numero_bordereau}%");
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_od', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('nom_tiers', 'like', "%{$search}%")
                      ->orWhere('reference_client', 'like', "%{$search}%")
                      ->orWhere('numero_piece', 'like', "%{$search}%")
                      ->orWhere('numero_bordereau', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $operationDiverses = $query->paginate($request->per_page ?? 20);
            $agences = Agency::all();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'operationDiverses' => $operationDiverses,
                    'agences' => $agences,
                    'statuts' => [
                        'BROUILLON' => 'Brouillon',
                        'SAISI' => 'Saisi',
                        'VALIDE_AGENCE' => 'Validé par agence',
                        'VALIDE_COMPTABLE' => 'Validé par comptable',
                        'VALIDE_DG' => 'Validé par DG',
                        'VALIDE' => 'Validé',
                        'REJETE' => 'Rejeté',
                        'ANNULE' => 'Annulé',
                    ],
                    'types_collecte' => [
                        'MATA_BOOST' => 'MATA BOOST',
                        'EPARGNE_JOURNALIERE' => 'Épargne Journalière',
                        'CHARGE' => 'Charge',
                        'AUTRE' => 'Autre',
                    ],
                    'types_operation' => [
                        'VIREMENT' => 'Virement',
                        'FRAIS' => 'Frais',
                        'COMMISSION' => 'Commission',
                        'REGULARISATION' => 'Régularisation',
                        'AUTRE' => 'Autre',
                    ],
                    'sens_operation' => [
                        'DEBIT' => 'Débit',
                        'CREDIT' => 'Crédit',
                    ],
                    'types_justificatif' => [
                        'FACTURE' => 'Facture',
                        'QUITTANCE' => 'Quittance',
                        'BON' => 'Bon',
                        'TICKET' => 'Ticket',
                        'AUTRE_VIREMENT' => 'Autre virement',
                        'NOTE_CORRECTION' => 'Note de correction',
                        'AUTRE' => 'Autre',
                    ],
                ],
                'message' => 'Liste des opérations diverses récupérée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder une nouvelle OD (API) - MODIFIÉ pour support multi-comptes
     */
/**
 * Sauvegarder une nouvelle OD (API) - Support comptes clients + plan comptable
 */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'agence_id' => 'required|exists:agencies,id',
        'date_operation' => 'required|date',
        'date_valeur' => 'nullable|date|after_or_equal:date_operation',
        'date_comptable' => 'nullable|date',
        'type_operation' => 'required|in:VIREMENT,FRAIS,COMMISSION,REGULARISATION,AUTRE',
        'type_collecte' => 'nullable|in:MATA_BOOST,EPARGNE_JOURNALIERE,CHARGE,AUTRE',
        'code_operation' => 'nullable|string|max:50',
        'libelle' => 'required|string|max:255',
        'description' => 'nullable|string',
        'montant_total' => 'required|numeric|min:0.01',
        'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
        
        // Sens de l'opération
        'sens_operation' => 'required|in:DEBIT,CREDIT',
        
        // Tableaux de comptes
        'comptes_debits' => 'nullable|array',
        'comptes_credits' => 'nullable|array',
        
        // Validation selon le type
        'comptes_debits.*.type' => 'required|in:client,plan',
        'comptes_debits.*.montant' => 'required|numeric|min:0.01',
        'comptes_debits.*.compte_client_id' => 'required_if:comptes_debits.*.type,client|exists:comptes,id',
        'comptes_debits.*.compte_id' => 'required_if:comptes_debits.*.type,plan|exists:plan_comptable,id',
        
        'comptes_credits.*.type' => 'required|in:client,plan',
        'comptes_credits.*.montant' => 'required|numeric|min:0.01',
        'comptes_credits.*.compte_client_id' => 'required_if:comptes_credits.*.type,client|exists:comptes,id',
        'comptes_credits.*.compte_id' => 'required_if:comptes_credits.*.type,plan|exists:plan_comptable,id',
        
        // Options
        'est_collecte' => 'boolean',
        'est_bloque' => 'boolean',
        'est_urgence' => 'boolean',
        
        // Références
        'numero_guichet' => 'required|string|max:50',
        'numero_piece' => 'nullable|string|max:50',
        'numero_bordereau' => 'nullable|string|max:50',
        'ref_lettrage' => 'nullable|string|max:100',
        'modele_id' => 'nullable|exists:od_modeles,id',
        
        // Justificatif
        'justificatif_type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE_VIREMENT,NOTE_CORRECTION,AUTRE',
        'justificatif_numero' => 'nullable|string|max:100',
        'justificatif_date' => 'nullable|date',
        'justificatif_base64' => 'nullable|string',
        'justificatif_filename' => 'nullable|string|max:255',
        'justificatif_mime_type' => 'nullable|string|max:100',
        'reference_client' => 'nullable|string|max:100',
        'nom_tiers' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation échouée',
            'errors' => $validator->errors()
        ], 422);
    }

    // Validation manuelle supplémentaire
    $errors = [];

    // Vérifier la présence des tableaux selon le sens
    if ($request->sens_operation === 'DEBIT') {
        if (empty($request->comptes_debits) || !is_array($request->comptes_debits) || count($request->comptes_debits) === 0) {
            $errors['comptes_debits'] = ['Au moins un compte débit est requis en mode DEBIT'];
        }
        if (empty($request->comptes_credits) || !is_array($request->comptes_credits) || count($request->comptes_credits) === 0) {
            $errors['comptes_credits'] = ['Au moins un compte crédit est requis en mode DEBIT'];
        }
    } else { // Mode CREDIT
        if (empty($request->comptes_credits) || !is_array($request->comptes_credits) || count($request->comptes_credits) === 0) {
            $errors['comptes_credits'] = ['Au moins un compte crédit est requis en mode CREDIT'];
        }
        if (empty($request->comptes_debits) || !is_array($request->comptes_debits) || count($request->comptes_debits) === 0) {
            $errors['comptes_debits'] = ['Au moins un compte débit est requis en mode CREDIT'];
        }
    }

    // Vérifier l'équilibre des montants
    if (!empty($request->comptes_debits) && !empty($request->comptes_credits)) {
        $totalDebits = collect($request->comptes_debits)->sum('montant');
        $totalCredits = collect($request->comptes_credits)->sum('montant');
        
        if (abs($totalDebits - $totalCredits) > 0.01) {
            $errors['montant_total'] = ['Les totaux débit et crédit ne correspondent pas'];
        }
        
        if (abs($totalDebits - $request->montant_total) > 0.01) {
            $errors['montant_total'] = ['Le montant total ne correspond pas aux totaux des comptes'];
        }
    }

    if (!empty($errors)) {
        return response()->json([
            'success' => false,
            'message' => 'Validation logique échouée',
            'errors' => $errors
        ], 422);
    }

    DB::beginTransaction();
    
    try {
        // Transformer les comptes débits pour stockage JSON
        $comptesDebitsJson = [];
        $premierComptePlanDebit = null;
        
        foreach ($request->comptes_debits as $item) {
            if ($item['type'] === 'client') {
                $comptesDebitsJson[] = [
                    'type' => 'client',
                    'compte_client_id' => $item['compte_client_id'],
                    'montant' => $item['montant']
                ];
            } else {
                $comptesDebitsJson[] = [
                    'type' => 'plan',
                    'compte_id' => $item['compte_id'],
                    'montant' => $item['montant']
                ];
                if (!$premierComptePlanDebit) {
                    $premierComptePlanDebit = $item['compte_id'];
                }
            }
        }

        // Transformer les comptes crédits pour stockage JSON
        $comptesCreditsJson = [];
        $premierComptePlanCredit = null;
        
        foreach ($request->comptes_credits as $item) {
            if ($item['type'] === 'client') {
                $comptesCreditsJson[] = [
                    'type' => 'client',
                    'compte_client_id' => $item['compte_client_id'],
                    'montant' => $item['montant']
                ];
            } else {
                $comptesCreditsJson[] = [
                    'type' => 'plan',
                    'compte_id' => $item['compte_id'],
                    'montant' => $item['montant']
                ];
                if (!$premierComptePlanCredit) {
                    $premierComptePlanCredit = $item['compte_id'];
                }
            }
        }

        // IMPORTANT: Tous les champs avec FK vers plan_comptable doivent être NULL ou des IDs plan_comptable
        // compte_debit_id, compte_credit_id, compte_debit_principal_id, compte_credit_principal_id
        
        $compteDebitId = $premierComptePlanDebit; // Premier compte plan en débit ou NULL
        $compteCreditId = $premierComptePlanCredit; // Premier compte plan en crédit ou NULL
        
        // Pour les comptes principaux, on utilise les mêmes (pas d'IDs clients)
        $compteDebitPrincipalId = $premierComptePlanDebit;
        $compteCreditPrincipalId = $premierComptePlanCredit;

        // Générer le numéro OD
        $numeroOd = OperationDiverse::generateNumeroOd();
        
        // Récupérer le jours_comptable_id
        $joursComptableId = $this->getJoursComptableId($request->date_comptable ?? $request->date_operation);

        $od = OperationDiverse::create([
            'agence_id' => $request->agence_id,
            'date_operation' => $request->date_operation,
            'date_valeur' => $request->date_valeur ?? $request->date_operation,
            'date_comptable' => $request->date_comptable ?? $request->date_operation,
            'type_operation' => $request->type_operation,
            'type_collecte' => $request->type_collecte ?? 'AUTRE',
            'code_operation' => $request->code_operation,
            'libelle' => $request->libelle,
            'description' => $request->description,
            'montant' => $request->montant_total,
            'montant_total' => $request->montant_total,
            'devise' => $request->devise,
            
            // Tous ces champs doivent être des IDs plan_comptable ou NULL
            'compte_debit_id' => $compteDebitId,
            'compte_credit_id' => $compteCreditId,
            'compte_debit_principal_id' => $compteDebitPrincipalId,
            'compte_credit_principal_id' => $compteCreditPrincipalId,
            
            // Données JSON pour les comptes multiples (contenant aussi les clients)
            'comptes_debits_json' => json_encode($comptesDebitsJson),
            'comptes_credits_json' => json_encode($comptesCreditsJson),
            'sens_operation' => $request->sens_operation,
            
            // Options
            'est_collecte' => $request->boolean('est_collecte', false),
            'est_bloque' => $request->boolean('est_bloque', false),
            'est_urgence' => $request->boolean('est_urgence', false),
            
            // Références
            'numero_guichet' => $request->numero_guichet,
            'numero_piece' => $request->numero_piece ?? OperationDiverse::generateNumeroPiece(),
            'numero_bordereau' => $request->numero_bordereau,
            'ref_lettrage' => $request->ref_lettrage,
            'modele_id' => $request->modele_id,
            'saisi_par' => Auth::id(),
            
            // Justificatif
            'justificatif_type' => $request->justificatif_type,
            'justificatif_numero' => $request->justificatif_numero,
            'justificatif_date' => $request->justificatif_date,
            'reference_client' => $request->reference_client,
            'nom_tiers' => $request->nom_tiers,
            
            // Champs obligatoires
            'statut' => 'SAISI',
            'numero_od' => $numeroOd,
            'jours_comptable_id' => $joursComptableId,
        ]);

        // Sauvegarder le justificatif en base64
        if ($request->justificatif_base64 && $request->justificatif_filename) {
            $fileName = uniqid() . '_' . $request->justificatif_filename;
            $path = "justificatifs/od/{$od->id}/{$fileName}";
            
            $fileContent = base64_decode($request->justificatif_base64);
            Storage::disk('public')->put($path, $fileContent);
            
            $od->update(['justificatif_path' => $path]);
        }

        // Enregistrer l'historique
        $od->enregistrerHistorique('CREATION', null, 'SAISI');

        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Opération diverse créée avec succès',
            'data' => $od->load(['agence', 'saisiPar'])
        ], 201);
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Erreur création OD:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de l\'opération diverse',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Helper pour obtenir le jours_comptable_id
 */
private function getJoursComptableId($date)
{
    // Adapte cette méthode selon ta logique métier
    // Exemple: retourne l'ID du jour comptable pour cette date
    return 51; // À remplacer par ta vraie logique
}
    /**
     * Créer une OD avec modèle (API)
     */
    public function storeParModele(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modele_id' => 'required|exists:od_modeles,id',
            'agence_id' => 'required|exists:agencies,id',
            'date_operation' => 'required|date',
            'date_valeur' => 'nullable|date|after_or_equal:date_operation',
            'montant_total' => 'required|numeric|min:0.01',
            'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'numero_guichet' => 'required|string|max:50',
            'numero_piece' => 'nullable|string|max:50',
            'libelle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'justificatif_type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE_VIREMENT,NOTE_CORRECTION,AUTRE',
            'justificatif_numero' => 'nullable|string|max:100',
            'justificatif_date' => 'nullable|date',
            'justificatif_base64' => 'nullable|string',
            'justificatif_filename' => 'nullable|string|max:255',
            'justificatif_mime_type' => 'nullable|string|max:100',
            'reference_client' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $modele = OdModele::with('lignes')->findOrFail($request->modele_id);
            
            if (!$modele->est_actif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce modèle n\'est pas actif'
                ], 400);
            }

            // Préparer les comptes selon le modèle
            $comptesDebits = [];
            $comptesCredits = [];
            $compteDebitPrincipal = null;
            $compteCreditPrincipal = null;
            
            foreach ($modele->lignes as $ligne) {
                $montant = $ligne->calculerMontant($request->montant_total);
                
                if ($ligne->sens === 'D') {
                    $compteDebitPrincipal = $ligne->compte_id;
                    $comptesDebits[] = [
                        'compte_id' => $ligne->compte_id,
                        'montant' => $montant
                    ];
                } else {
                    $compteCreditPrincipal = $ligne->compte_id;
                    $comptesCredits[] = [
                        'compte_id' => $ligne->compte_id,
                        'montant' => $montant
                    ];
                }
            }
            
            // Déterminer le sens (si plus de débits que de crédits, c'est un mode CREDIT)
            $sens = count($comptesDebits) > count($comptesCredits) ? 'CREDIT' : 'DEBIT';
            
            // Créer l'OD avec le modèle adapté
            $comptesDebitsJson = $sens === 'CREDIT' ? json_encode($comptesDebits) : null;
            $comptesCreditsJson = $sens === 'DEBIT' ? json_encode($comptesCredits) : null;

            $od = OperationDiverse::create([
                'agence_id' => $request->agence_id,
                'date_operation' => $request->date_operation,
                'date_valeur' => $request->date_valeur ?? $request->date_operation,
                'date_comptable' => $request->date_operation,
                'type_operation' => $modele->type_operation,
                'type_collecte' => OperationDiverse::TYPE_AUTRE,
                'code_operation' => $modele->code_operation,
                'libelle' => $request->libelle ?? $modele->nom,
                'description' => $request->description ?? $modele->description,
                'montant' => $request->montant_total,
                'montant_total' => $request->montant_total,
                'devise' => $request->devise,
                'compte_debit_id' => $compteDebitPrincipal,
                'compte_credit_id' => $compteCreditPrincipal,
                'compte_debit_principal_id' => $compteDebitPrincipal,
                'compte_credit_principal_id' => $compteCreditPrincipal,
                'comptes_debits_json' => $comptesDebitsJson,
                'comptes_credits_json' => $comptesCreditsJson,
                'sens_operation' => $sens,
                'numero_guichet' => $request->numero_guichet,
                'numero_piece' => $request->numero_piece ?? OperationDiverse::generateNumeroPiece(),
                'modele_id' => $modele->id,
                'saisi_par' => Auth::id(),
                'justificatif_type' => $request->justificatif_type,
                'justificatif_numero' => $request->justificatif_numero,
                'justificatif_date' => $request->justificatif_date,
                'reference_client' => $request->reference_client,
                'statut' => OperationDiverse::STATUT_SAISI
            ]);

            // Sauvegarder le justificatif en base64
            if ($request->justificatif_base64 && $request->justificatif_filename) {
                $fileName = uniqid() . '_' . $request->justificatif_filename;
                $path = "justificatifs/od/{$od->id}/{$fileName}";
                
                $fileContent = base64_decode($request->justificatif_base64);
                Storage::disk('public')->put($path, $fileContent);
                
                $od->update(['justificatif_path' => $path]);
            }

            $od->enregistrerHistorique('CREATION_PAR_MODELE', null, OperationDiverse::STATUT_SAISI);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD créée avec modèle avec succès',
                'data' => $od->fresh()->load(['agence', 'modele', 'compteDebit', 'compteCredit', 'saisiPar'])
            ], 201);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'OD avec modèle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Créer une OD pour MATA BOOST avec répartition par comptes clients (API)
 */
public function creerMataBoostAvecClients(Request $request)
{
    $validator = Validator::make($request->all(), [
        'agence_id' => 'required|exists:agencies,id',
        'date_operation' => 'required|date',
        'montant' => 'required|numeric|min:0.01',
        'comptes_collecteurs' => 'required|array|min:1',
        'comptes_collecteurs.*.compte_id' => 'required|exists:plan_comptable,id',
        'comptes_collecteurs.*.montant' => 'required|numeric|min:0.01',
        'compte_mata_boost_id' => 'required|exists:plan_comptable,id',
        'comptes_clients' => 'required|array|min:1',
        'comptes_clients.*.compte_client_id' => 'required|exists:comptes,id',
        'comptes_clients.*.montant' => 'required|numeric|min:0.01',
        'est_bloque' => 'boolean',
        'numero_guichet' => 'required|string|max:50',
        'numero_bordereau' => 'required|string|max:50',
        'nom_agent' => 'required|string|max:255',
        'justificatif_base64' => 'nullable|string',
        'justificatif_filename' => 'nullable|string|max:255',
        'justificatif_mime_type' => 'nullable|string|max:100',
        'reference_client' => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'devise' => 'nullable|string|max:10',
        'libelle' => 'nullable|string|max:255',
        'date_valeur' => 'nullable|date',
        'date_comptable' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation échouée',
            'errors' => $validator->errors()
        ], 422);
    }

    // Vérifier l'équilibre des totaux
    $totalCollecteurs = collect($request->comptes_collecteurs)->sum('montant');
    $totalClients = collect($request->comptes_clients)->sum('montant');

    if (abs($totalCollecteurs - $totalClients) > 0.01) {
        return response()->json([
            'success' => false,
            'message' => 'Les totaux collecteurs et crédits clients ne sont pas équilibrés',
            'total_collecteurs' => $totalCollecteurs,
            'total_clients' => $totalClients
        ], 422);
    }

    DB::beginTransaction();
    
    try {
        $donnees = $request->all();
        $donnees['saisi_par'] = Auth::id();
        
        // Gérer le fichier base64 si présent
        if ($request->has('justificatif_base64') && !empty($request->justificatif_base64)) {
            $base64 = $request->justificatif_base64;
            $filename = $request->justificatif_filename ?? 'justificatif_' . time();
            $mimeType = $request->justificatif_mime_type ?? 'application/octet-stream';
            
            $base64_str = substr($base64, strpos($base64, ",") + 1);
            $fileData = base64_decode($base64_str);
            
            if ($fileData === false) {
                throw new \Exception('Erreur lors du décodage base64');
            }
            
            $extension = $this->getExtensionFromMime($mimeType);
            $uniqueFilename = Str::slug($filename) . '_' . time() . '.' . $extension;
            
            $path = "justificatifs/od/mata-boost-clients/" . date('Y/m');
            $fullPath = $path . '/' . $uniqueFilename;
            
            $directoryPath = "justificatifs/od/mata-boost-clients/" . date('Y/m');
            if (!Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->makeDirectory($directoryPath, 0755, true);
            }
            
            Storage::disk('public')->put($fullPath, $fileData);
            
            $donnees['justificatif_path'] = $fullPath;
            $donnees['justificatif_type'] = 'BON';
            $donnees['justificatif_numero'] = $request->numero_bordereau;
            $donnees['justificatif_date'] = $request->date_operation;
        }
        
        $od = OperationDiverse::creerMataBoostAvecClients($donnees, Auth::user());

        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'OD MATA BOOST avec répartition clients créée avec succès',
            'data' => $od->load(['agence', 'compteDebit', 'compteCredit', 'saisiPar'])
        ], 201);
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Erreur création OD MATA BOOST clients:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de l\'OD MATA BOOST avec répartition clients',
            'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
        ], 500);
    }
}

/**
 * Récupérer les comptes clients rattachés à un compte MATA BOOST
 */
public function getClientsParCompteMataBoost($compteMataBoostId)
{
    try {
        // Récupérer tous les comptes clients dont le plan_comptable_id correspond
        // à un compte MATA BOOST spécifique ou ses sous-comptes
        $comptesClients = Compte::with('client')
            ->where('plan_comptable_id', $compteMataBoostId)
            ->orWhereHas('planComptable', function($query) use ($compteMataBoostId) {
                $plan = PlanComptable::find($compteMataBoostId);
                if ($plan) {
                    // Si c'est un compte générique, chercher tous les comptes qui commencent par ce code
                    $query->where('code', 'like', $plan->code . '%');
                }
            })
            ->where('statut', 'actif')
            ->orderBy('numero_compte')
            ->get()
            ->map(function($compte) {
                return [
                    'id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'client_id' => $compte->client_id,
                    'client_nom' => $compte->client ? $compte->client->nom_complet : 'Client inconnu',
                    'client_code' => $compte->client ? $compte->client->code_client : '',
                    'label' => ($compte->client ? $compte->client->nom_complet . ' - ' : '') . 
                              $compte->numero_compte . ' - ' . $compte->libelle,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $comptesClients,
            'message' => 'Comptes clients récupérés avec succès.',
            'count' => $comptesClients->count()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des comptes clients',
            'error' => $e->getMessage()
        ], 500);
    }
}

//end ajout MATA BOOST avec répartition clients

    private function getExtensionFromMime($mimeType)
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];
        
        return $mimeToExt[$mimeType] ?? 'bin';
    }


/**
 * Créer une OD pour Épargne Journalière avec répartition par comptes clients (API)
 */
public function creerEpargneJournaliereAvecClients(Request $request)
{
    $validator = Validator::make($request->all(), [
        'agence_id' => 'required|exists:agencies,id',
        'date_operation' => 'required|date',
        'montant' => 'required|numeric|min:0.01',
        'comptes_collecteurs' => 'required|array|min:1',
        'comptes_collecteurs.*.compte_id' => 'required|exists:plan_comptable,id',
        'comptes_collecteurs.*.montant' => 'required|numeric|min:0.01',
        'compte_epargne_id' => 'required|exists:plan_comptable,id',
        'comptes_clients' => 'required|array|min:1',
        'comptes_clients.*.compte_client_id' => 'required|exists:comptes,id',
        'comptes_clients.*.montant' => 'required|numeric|min:0.01',
        'est_bloque' => 'boolean',
        'numero_guichet' => 'required|string|max:50',
        'numero_bordereau' => 'required|string|max:50',
        'nom_agent' => 'required|string|max:255',
        'justificatif_base64' => 'nullable|string',
        'justificatif_filename' => 'nullable|string|max:255',
        'justificatif_mime_type' => 'nullable|string|max:100',
        'reference_client' => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'devise' => 'nullable|string|max:10',
        'libelle' => 'nullable|string|max:255',
        'date_valeur' => 'nullable|date',
        'date_comptable' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation échouée',
            'errors' => $validator->errors()
        ], 422);
    }

    // Vérifier l'équilibre des totaux
    $totalCollecteurs = collect($request->comptes_collecteurs)->sum('montant');
    $totalClients = collect($request->comptes_clients)->sum('montant');

    if (abs($totalCollecteurs - $totalClients) > 0.01) {
        return response()->json([
            'success' => false,
            'message' => 'Les totaux collecteurs et crédits clients ne sont pas équilibrés',
            'total_collecteurs' => $totalCollecteurs,
            'total_clients' => $totalClients
        ], 422);
    }

    DB::beginTransaction();
    
    try {
        $donnees = $request->all();
        $donnees['saisi_par'] = Auth::id();
        
        // Gérer le fichier base64 si présent
        if ($request->has('justificatif_base64') && !empty($request->justificatif_base64)) {
            $base64 = $request->justificatif_base64;
            $filename = $request->justificatif_filename ?? 'justificatif_' . time();
            $mimeType = $request->justificatif_mime_type ?? 'application/octet-stream';
            
            $base64_str = substr($base64, strpos($base64, ",") + 1);
            $fileData = base64_decode($base64_str);
            
            if ($fileData === false) {
                throw new \Exception('Erreur lors du décodage base64');
            }
            
            $extension = $this->getExtensionFromMime($mimeType);
            $uniqueFilename = Str::slug($filename) . '_' . time() . '.' . $extension;
            
            $path = "justificatifs/od/epargne-clients/" . date('Y/m');
            $fullPath = $path . '/' . $uniqueFilename;
            
            $directoryPath = "justificatifs/od/epargne-clients/" . date('Y/m');
            if (!Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->makeDirectory($directoryPath, 0755, true);
            }
            
            Storage::disk('public')->put($fullPath, $fileData);
            
            $donnees['justificatif_path'] = $fullPath;
            $donnees['justificatif_type'] = 'BON';
            $donnees['justificatif_numero'] = $request->numero_bordereau;
            $donnees['justificatif_date'] = $request->date_operation;
        }
        
        $od = OperationDiverse::creerEpargneJournaliereAvecClients($donnees, Auth::user());

        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'OD Épargne Journalière avec répartition clients créée avec succès',
            'data' => $od->load(['agence', 'compteDebit', 'compteCredit', 'saisiPar'])
        ], 201);
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Erreur création OD Épargne Journalière clients:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de l\'OD Épargne Journalière avec répartition clients',
            'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
        ], 500);
    }
}

    /**
     * Récupérer les comptes clients rattachés à un compte Épargne Journalière avec filtre
     */
    public function getClientsParCompteEpargne($compteEpargneId)
    {
        try {
            // Récupérer tous les comptes clients dont le plan_comptable_id correspond
            $comptesClients = Compte::with('client')
                ->where('plan_comptable_id', $compteEpargneId)
                ->orWhereHas('planComptable', function($query) use ($compteEpargneId) {
                    $plan = PlanComptable::find($compteEpargneId);
                    if ($plan) {
                        // Si c'est un compte générique (37224), chercher tous les sous-comptes
                        $query->where('code', 'like', $plan->code . '%');
                    }
                })
                ->where('statut', 'actif')
                ->orderBy('numero_compte')
                ->get()
                ->map(function($compte) {
                    return [
                        'id' => $compte->id,
                        'numero_compte' => $compte->numero_compte,
                        'libelle' => $compte->libelle,
                        'client_id' => $compte->client_id,
                        'client_nom' => $compte->client ? $compte->client->nom_complet : 'Client inconnu',
                        'client_code' => $compte->client ? $compte->client->code_client : '',
                        'label' => ($compte->client ? $compte->client->nom_complet . ' - ' : '') . 
                                $compte->numero_compte . ' - ' . $compte->libelle,
                        'solde' => $compte->solde,
                        'devise' => $compte->devise
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $comptesClients,
                'message' => 'Comptes clients Épargne Journalière récupérés avec succès.',
                'count' => $comptesClients->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer tous les comptes clients ayant un compte Épargne Journalière sans filtre
     */
    public function getComptesClientsEpargneJournaliere()
    {
        try {
            // Récupérer tous les comptes clients dont le plan comptable correspond à l'épargne journalière
            $comptesClients = Compte::with('client')
                ->where('statut', 'actif')
                ->whereHas('planComptable', function($query) {
                    $query->where('code', 'like', '37224%')
                        ->orWhere('libelle', 'like', '%épargne%')
                        ->orWhere('libelle', 'like', '%epargne%')
                        ->orWhere('libelle', 'like', '%journalier%')
                        ->orWhere('libelle', 'like', '%journalière%');
                })
                ->orderBy('numero_compte')
                ->get()
                ->map(function($compte) {
                    return [
                        'id' => $compte->id,
                        'numero_compte' => $compte->numero_compte,
                        'libelle' => $compte->libelle,
                        'type_compte' => 'Épargne Journalière',
                        'client_id' => $compte->client_id,
                        'client_nom' => $compte->client ? $compte->client->nom_complet : 'Client inconnu',
                        'client_code' => $compte->client ? $compte->client->code_client : '',
                        'plan_comptable_id' => $compte->plan_comptable_id,
                        'plan_comptable_code' => $compte->planComptable ? $compte->planComptable->code : '',
                        'plan_comptable_libelle' => $compte->planComptable ? $compte->planComptable->libelle : '',
                        'label' => ($compte->client ? $compte->client->nom_complet . ' - ' : '') . 
                                $compte->numero_compte . ' - ' . $compte->libelle,
                        'solde' => $compte->solde,
                        'devise' => $compte->devise
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $comptesClients,
                'message' => 'Comptes clients Épargne Journalière récupérés avec succès.',
                'count' => $comptesClients->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur getComptesClientsEpargneJournaliere:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes clients Épargne Journalière',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Créer une OD pour Charges (API)
     */
    public function creerCharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agence_id' => 'required|exists:agencies,id',
            'date_operation' => 'required|date',
            'montant' => 'required|numeric|min:0.01',
            'compte_charge_id' => 'required|exists:plan_comptable,id',
            'compte_passage_id' => 'required|exists:plan_comptable,id',
            'numero_guichet' => 'required|string|max:50',
            'numero_piece' => 'required|string|max:50',
            'justificatif_type' => 'required|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE_VIREMENT,NOTE_CORRECTION,AUTRE',
            'justificatif_numero' => 'required|string|max:100',
            'justificatif_date' => 'required|date',
            'justificatif' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'nom_fournisseur' => 'required|string|max:255',
            'est_urgence' => 'boolean',
            'description' => 'nullable|string',
            'reference_client' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $donnees = $request->all();
            $donnees['saisi_par'] = Auth::id();
            
            $od = OperationDiverse::creerCharge($donnees, Auth::user());

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD Charge créée avec succès',
                'data' => $od->load(['agence', 'compteDebit', 'compteCredit', 'saisiPar'])
            ], 201);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'OD Charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Afficher une OD (API)
 */
public function show($id)
{
    try {
        $operationDiverse = OperationDiverse::with([
            'agence',
            'saisiPar',
            'validePar',
            'comptabilisePar',
            'compteDebit',
            'compteCredit',
            'compteDebitPrincipal',
            'compteCreditPrincipal',
            'modele.lignes.compte',
            'historique.user',
            'signatures.validateur',
            'workflow.validateur',
        ])->findOrFail($id);
        
        // Ajouter les comptes détaillés (clients + plan)
        $operationDiverse->comptes_debits_details = $operationDiverse->comptes_debits_details;
        $operationDiverse->comptes_credits_details = $operationDiverse->comptes_credits_details;
        
        // Ajouter l'aperçu de l'écriture comptable
        $operationDiverse->apercu_ecriture = $operationDiverse->getApercuEcriture();
        
        // Ajouter l'état des validations
        $operationDiverse->etat_validations = $operationDiverse->getEtatValidations();
        
        // Ajouter les permissions de validation
        $user = Auth::user();
        $operationDiverse->permissions_validation = [
            'peut_valider_agence' => $user->hasRole('Chef d\'Agence (CA)') && $operationDiverse->peutEtreValideeParAgence(),
            'peut_valider_comptable' => $user->hasRole('Chef Comptable') && $operationDiverse->peutEtreValideeParComptable(),
            'peut_valider_dg' => $user->hasRole('DG') && $operationDiverse->peutEtreValideeParDG(),
            'peut_rejeter' => $user->hasAnyRole(['Chef d\'Agence (CA)', 'Chef Comptable', 'DG']) 
                && !in_array($operationDiverse->statut, ['VALIDE', 'ANNULE', 'REJETE']),
            'peut_comptabiliser' => $user->hasRole('Chef Comptable') && $operationDiverse->estPretePourComptabilisation(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $operationDiverse,
            'message' => 'OD récupérée avec succès.'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'OD non trouvée.',
            'error' => $e->getMessage()
        ], 404);
    }
}

    /**
     * Mettre à jour une OD (API)
     */
    public function update(Request $request, $id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Vérifier si l'OD peut être modifiée
            if (!$operationDiverse->peutEtreModifiee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette OD ne peut pas être modifiée dans son état actuel.'
                ], 400);
            }
            
            $validator = Validator::make($request->all(), [
                'date_operation' => 'sometimes|date',
                'date_valeur' => 'nullable|date|after_or_equal:date_operation',
                'date_comptable' => 'nullable|date',
                'libelle' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'montant_total' => 'sometimes|numeric|min:0.01',
                'sens_operation' => 'sometimes|in:DEBIT,CREDIT',
                
                // Champs pour le mode DEBIT
                'compte_debit_id' => 'nullable|exists:plan_comptable,id|required_if:sens_operation,DEBIT',
                'comptes_credits' => 'nullable|array|required_if:sens_operation,DEBIT|min:1',
                'comptes_credits.*.compte_id' => 'required_if:sens_operation,DEBIT|exists:plan_comptable,id',
                'comptes_credits.*.montant' => 'required_if:sens_operation,DEBIT|numeric|min:0.01',
                
                // Champs pour le mode CREDIT
                'compte_credit_id' => 'nullable|exists:plan_comptable,id|required_if:sens_operation,CREDIT',
                'comptes_debits' => 'nullable|array|required_if:sens_operation,CREDIT|min:1',
                'comptes_debits.*.compte_id' => 'required_if:sens_operation,CREDIT|exists:plan_comptable,id',
                'comptes_debits.*.montant' => 'required_if:sens_operation,CREDIT|numeric|min:0.01',
                
                'numero_guichet' => 'nullable|string|max:50',
                'numero_piece' => 'nullable|string|max:50',
                'numero_bordereau' => 'nullable|string|max:50',
                'ref_lettrage' => 'nullable|string|max:100',
                'justificatif_type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE_VIREMENT,NOTE_CORRECTION,AUTRE',
                'justificatif_numero' => 'nullable|string|max:100',
                'justificatif_date' => 'nullable|date',
                'reference_client' => 'nullable|string|max:100',
                'nom_tiers' => 'nullable|string|max:255',
                'est_urgence' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            
            $data = $validator->validated();
            
            // Vérifier l'équilibre des montants
            if (isset($data['sens_operation'])) {
                if ($data['sens_operation'] === 'DEBIT' && isset($data['comptes_credits'])) {
                    $totalCredits = collect($data['comptes_credits'])->sum('montant');
                    if (isset($data['montant_total']) && abs($totalCredits - $data['montant_total']) > 0.01) {
                        throw new \Exception('Le total des crédits ne correspond pas au montant total');
                    }
                } elseif ($data['sens_operation'] === 'CREDIT' && isset($data['comptes_debits'])) {
                    $totalDebits = collect($data['comptes_debits'])->sum('montant');
                    if (isset($data['montant_total']) && abs($totalDebits - $data['montant_total']) > 0.01) {
                        throw new \Exception('Le total des débits ne correspond pas au montant total');
                    }
                }
            }
            
            // Mettre à jour les champs JSON
            if (isset($data['sens_operation'])) {
                if ($data['sens_operation'] === 'DEBIT' && isset($data['comptes_credits'])) {
                    $data['comptes_credits_json'] = json_encode($data['comptes_credits']);
                    $data['compte_debit_principal_id'] = $data['compte_debit_id'];
                    $data['compte_credit_principal_id'] = null;
                    unset($data['comptes_credits']);
                } elseif ($data['sens_operation'] === 'CREDIT' && isset($data['comptes_debits'])) {
                    $data['comptes_debits_json'] = json_encode($data['comptes_debits']);
                    $data['compte_credit_principal_id'] = $data['compte_credit_id'];
                    $data['compte_debit_principal_id'] = null;
                    unset($data['comptes_debits']);
                }
            }
            
            // Mettre à jour le montant principal
            if (isset($data['montant_total'])) {
                $data['montant'] = $data['montant_total'];
            }
            
            $operationDiverse->update($data);
            
            $operationDiverse->enregistrerHistorique('MODIFICATION');

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD mise à jour avec succès',
                'data' => $operationDiverse->fresh()->load(['agence', 'compteDebit', 'compteCredit'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une OD (API)
     */
    public function destroy($id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Vérifier si l'OD peut être supprimée
            if (!$operationDiverse->peutEtreModifiee() || $operationDiverse->est_comptabilise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette OD.'
                ], 400);
            }

            DB::beginTransaction();
            
            // Supprimer les fichiers justificatifs
            if ($operationDiverse->justificatif_path && Storage::disk('public')->exists($operationDiverse->justificatif_path)) {
                Storage::disk('public')->delete($operationDiverse->justificatif_path);
            }

            // Supprimer l'historique et les signatures
            $operationDiverse->historique()->delete();
            $operationDiverse->signatures()->delete();
            $operationDiverse->workflow()->delete();
            
            // Supprimer l'OD
            $operationDiverse->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD supprimée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider une OD par le chef d'agence (API)
     */
    public function validerAgence(Request $request, $id)
    {
        try {
            $request->validate([
                'commentaire' => 'nullable|string|max:500',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->validerParAgence(Auth::user(), $request->commentaire)) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD validée par le chef d\'agence avec succès.',
                    'data' => $operationDiverse->fresh()->load(['validePar', 'workflow.validateur'])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de valider cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider une OD par le chef comptable (API)
     */
    public function validerComptable(Request $request, $id)
    {
        try {
            $request->validate([
                'commentaire' => 'nullable|string|max:500',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->validerParComptable(Auth::user(), $request->commentaire)) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD validée par le chef comptable avec succès.',
                    'data' => $operationDiverse->fresh()->load(['workflow.validateur'])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de valider cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider une OD par le DG (API) - MODIFIÉ pour retourner le code
     */
    public function validerDG(Request $request, $id)
    {
        try {
            $request->validate([
                'commentaire' => 'nullable|string|max:500',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Valider l'OD
            if ($operationDiverse->validerParDG(Auth::user(), $request->commentaire)) {
                
                // Générer un code pour cette validation
                $codeGenere = $this->genererCodeCaisse(); // Utilisez votre méthode de génération de code
                
                return response()->json([
                    'success' => true,
                    'message' => 'OD validée par le DG avec succès. Un code a été généré.',
                    'data' => [
                        'od' => $operationDiverse->fresh()->load(['workflow.validateur']),
                        'code' => $codeGenere,
                        'message' => 'Veuillez enregistrer ce code pour finaliser la validation.'
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de valider cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthode pour générer un code alphanumérique (déjà présente dans votre contrôleur)
     */
    private function genererCodeCaisse(): string
    {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        return $code;
    }

    /**
     * Rejeter une OD (API)
     */
    public function rejeter(Request $request, $id)
    {
        try {
            $request->validate([
                'motif' => 'required|string|max:1000',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->rejeter(Auth::user(), $request->motif)) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD rejetée avec succès.',
                    'data' => $operationDiverse->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de rejeter cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Comptabiliser une OD (API)
 */
public function comptabiliser(Request $request, $id)
{
    try {
        $operationDiverse = OperationDiverse::findOrFail($id);
        
        // Utiliser le service au lieu d'une méthode du modèle
        $operationDiverseService = app(OperationDiverseService::class);
        $resultat = $operationDiverseService->comptabiliserOD($operationDiverse, Auth::user());
        
        return response()->json([
            'success' => true,
            'message' => 'OD comptabilisée avec succès.',
            'data' => $operationDiverse->fresh()->load(['comptabilisePar'])
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la comptabilisation de l\'OD',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Annuler une OD (API)
     */
    public function annuler(Request $request, $id)
    {
        try {
            $request->validate(['motif' => 'required|string|max:255']);
            
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Vérifier si l'OD peut être annulée
            if ($operationDiverse->est_comptabilise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une OD déjà comptabilisée.'
                ], 400);
            }

            DB::beginTransaction();
            
            $ancienStatut = $operationDiverse->statut;
            
            $operationDiverse->update([
                'statut' => OperationDiverse::STATUT_ANNULE,
                'motif_rejet' => $request->motif
            ]);

            $operationDiverse->enregistrerHistorique('ANNULATION', $ancienStatut, OperationDiverse::STATUT_ANNULE);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD annulée avec succès',
                'data' => $operationDiverse->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uploader un justificatif (API)
     */
    public function uploadJustificatif(Request $request, $id)
    {
        try {
            $request->validate([
                'justificatif' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE_VIREMENT,NOTE_CORRECTION,AUTRE',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Vérifier si l'OD peut être modifiée
            if (!$operationDiverse->peutEtreModifiee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de modifier le justificatif de cette OD.'
                ], 400);
            }

            $path = $request->file('justificatif')->store(
                "justificatifs/od/{$operationDiverse->id}",
                'public'
            );
            
            $operationDiverse->update([
                'justificatif_path' => $path,
                'justificatif_type' => $request->type ?? $operationDiverse->justificatif_type,
            ]);

            $operationDiverse->enregistrerHistorique('UPLOAD_JUSTIFICATIF');

            return response()->json([
                'success' => true,
                'message' => 'Justificatif uploadé avec succès.',
                'data' => [
                    'path' => $path,
                    'od' => $operationDiverse->fresh()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du justificatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le justificatif (API)
     */
    public function telechargerJustificatif($id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);

            if (!$operationDiverse->justificatif_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun justificatif disponible.'
                ], 404);
            }

            $path = storage_path('app/public/' . $operationDiverse->justificatif_path);
            
            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier justificatif introuvable.'
                ], 404);
            }

            return response()->download($path);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du justificatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD en attente de validation par l'agence (API)
     */
    public function enAttenteValidationAgence(Request $request)
    {
        try {
            $query = OperationDiverse::enAttenteValidationAgence()
                ->with(['agence', 'saisiPar', 'compteDebit', 'compteCredit']);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            if ($request->filled('type_collecte')) {
                $query->where('type_collecte', $request->type_collecte);
            }
            
            $operationDiverses = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD en attente de validation par l\'agence récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD en attente de validation par la comptabilité (API)
     */
    public function enAttenteValidationComptable(Request $request)
    {
        try {
            $query = OperationDiverse::enAttenteValidationComptable()
                ->with(['agence', 'saisiPar', 'compteDebit', 'compteCredit', 'workflow' => function($q) {
                    $q->where('niveau', OperationDiverse::NIVEAU_AGENCE);
                }]);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD en attente de validation par la comptabilité récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD en attente de validation par le DG (API)
     */
    public function enAttenteValidationDG(Request $request)
    {
        try {
            $query = OperationDiverse::enAttenteValidationDG()
                ->with(['agence', 'saisiPar', 'compteDebit', 'compteCredit', 'workflow' => function($q) {
                    $q->whereIn('niveau', [OperationDiverse::NIVEAU_AGENCE, OperationDiverse::NIVEAU_COMPTABLE]);
                }]);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD en attente de validation par le DG récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD à comptabiliser (API)
     */
    public function aComptabiliser(Request $request)
    {
        try {
            $query = OperationDiverse::validees()
                ->where('est_comptabilise', false)
                ->with(['agence', 'validePar', 'compteDebit', 'compteCredit']);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->latest('date_validation')->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD à comptabiliser récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD à comptabiliser',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche avancée des OD (API)
     */
    public function rechercheAvancee(Request $request)
    {
        try {
            $query = OperationDiverse::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_od', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('nom_tiers', 'like', "%{$search}%")
                      ->orWhere('reference_client', 'like', "%{$search}%")
                      ->orWhere('numero_piece', 'like', "%{$search}%")
                      ->orWhere('numero_bordereau', 'like', "%{$search}%");
                });
            }

            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }

            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }

            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }

            if ($request->filled('type_collecte')) {
                $query->where('type_collecte', $request->type_collecte);
            }

            if ($request->filled('sens_operation')) {
                $query->where('sens_operation', $request->sens_operation);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            if ($request->filled('est_urgence')) {
                $query->where('est_urgence', $request->boolean('est_urgence'));
            }

            if ($request->filled('est_collecte')) {
                $query->where('est_collecte', $request->boolean('est_collecte'));
            }

            if ($request->filled('est_bloque')) {
                $query->where('est_bloque', $request->boolean('est_bloque'));
            }

            if ($request->filled('code_operation')) {
                $query->where('code_operation', $request->code_operation);
            }

            $operationDiverses = $query->with(['agence', 'saisiPar', 'validePar'])
                ->latest()
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'message' => 'Résultats de recherche récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Journal des OD (API)
     */
    public function journal(Request $request)
    {
        try {
            $query = OperationDiverse::with(['agence', 'saisiPar', 'compteDebit', 'compteCredit', 'workflow.validateur'])
                ->where('est_comptabilise', true);
            
            if ($request->filled('date')) {
                $query->where('date_operation', $request->date);
            } elseif ($request->filled(['date_debut', 'date_fin'])) {
                $query->whereBetween('date_operation', [$request->date_debut, $request->date_fin]);
            } else {
                $query->where('date_operation', today());
            }
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            if ($request->filled('type_collecte')) {
                $query->where('type_collecte', $request->type_collecte);
            }
            
            if ($request->filled('code_operation')) {
                $query->where('code_operation', $request->code_operation);
            }
            
            if ($request->filled('numero_guichet')) {
                $query->where('numero_guichet', $request->numero_guichet);
            }
            
            $operationDiverses = $query->orderBy('date_operation')->get();
            
            // Calcul des totaux
            $total = $operationDiverses->sum('montant');
            $totalParType = $operationDiverses->groupBy('type_collecte')->map->sum('montant');
            $totalParCode = $operationDiverses->groupBy('code_operation')->map->sum('montant');
            $totalParSens = $operationDiverses->groupBy('sens_operation')->map->sum('montant');

            return response()->json([
                'success' => true,
                'data' => [
                    'operationDiverses' => $operationDiverses,
                    'total' => $total,
                    'total_par_type' => $totalParType,
                    'total_par_code' => $totalParCode,
                    'total_par_sens' => $totalParSens,
                    'count' => $operationDiverses->count()
                ],
                'message' => 'Journal des OD généré avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des OD (API)
     */
    public function statistiques(Request $request)
    {
        try {
            $query = OperationDiverse::query();
            
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }
            
            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            // Statistiques par statut
            $parStatut = $query->clone()
                ->selectRaw('statut, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('statut')
                ->get()
                ->keyBy('statut');

            // Statistiques par type de collecte
            $parTypeCollecte = $query->clone()
                ->selectRaw('type_collecte, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('type_collecte')
                ->get()
                ->keyBy('type_collecte');

            // Statistiques par type d'opération
            $parTypeOperation = $query->clone()
                ->selectRaw('type_operation, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('type_operation')
                ->get()
                ->keyBy('type_operation');

            // Statistiques par sens d'opération
            $parSensOperation = $query->clone()
                ->selectRaw('sens_operation, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('sens_operation')
                ->get()
                ->keyBy('sens_operation');

            // Total général
            $totalGeneral = $query->clone()->sum('montant');
            $countGeneral = $query->clone()->count();

            // OD en urgence
            $urgences = $query->clone()->where('est_urgence', true)->count();

            // OD collectées
            $collectes = $query->clone()->where('est_collecte', true)->count();

            // OD bloquées
            $bloquees = $query->clone()->where('est_bloque', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_general' => $totalGeneral,
                    'nombre_total' => $countGeneral,
                    'par_statut' => $parStatut,
                    'par_type_collecte' => $parTypeCollecte,
                    'par_type_operation' => $parTypeOperation,
                    'par_sens_operation' => $parSensOperation,
                    'urgences' => $urgences,
                    'collectes' => $collectes,
                    'bloquees' => $bloquees
                ],
                'message' => 'Statistiques récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les données des OD (API)
     */
    public function export(Request $request)
    {
        try {
            $query = OperationDiverse::query();

            // Appliquer les filtres
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }

            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }

            if ($request->filled('type_collecte')) {
                $query->where('type_collecte', $request->type_collecte);
            }

            if ($request->filled('sens_operation')) {
                $query->where('sens_operation', $request->sens_operation);
            }

            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            if ($request->filled('est_collecte')) {
                $query->where('est_collecte', $request->boolean('est_collecte'));
            }

            $operationDiverses = $query->with(['agence', 'saisiPar', 'validePar', 'compteDebit', 'compteCredit'])
                ->get()
                ->map(function ($od) {
                    return [
                        'Numéro OD' => $od->numero_od,
                        'Date opération' => Carbon::parse($od->date_operation)->format('d/m/Y'),
                        'Date valeur' => $od->date_valeur ? Carbon::parse($od->date_valeur)->format('d/m/Y') : '',
                        'Date comptable' => $od->date_comptable ? Carbon::parse($od->date_comptable)->format('d/m/Y') : '',
                        'Libellé' => $od->libelle,
                        'Type opération' => $od->type_operation,
                        'Type collecte' => $od->type_collecte,
                        'Sens opération' => $od->sens_operation,
                        'Code opération' => $od->code_operation,
                        'Montant' => $od->montant ? number_format((float)$od->montant, 2, ',', ' ') . ' ' . $od->devise : '0,00 ' . $od->devise,
                        'Compte débit principal' => $od->compteDebitPrincipal ? $od->compteDebitPrincipal->code . ' - ' . $od->compteDebitPrincipal->libelle : '',
                        'Compte crédit principal' => $od->compteCreditPrincipal ? $od->compteCreditPrincipal->code . ' - ' . $od->compteCreditPrincipal->libelle : '',
                        'Statut' => $this->traduireStatut($od->statut),
                        'Collecte' => $od->est_collecte ? 'Oui' : 'Non',
                        'Bloqué' => $od->est_bloque ? 'Oui' : 'Non',
                        'Urgence' => $od->est_urgence ? 'Oui' : 'Non',
                        'Saisi par' => $od->saisiPar ? $od->saisiPar->name : '',
                        'Validé par' => $od->validePar ? $od->validePar->name : '',
                        'Date validation' => $od->date_validation ? Carbon::parse($od->date_validation)->format('d/m/Y H:i') : '',
                        'Comptabilisé' => $od->est_comptabilise ? 'Oui' : 'Non',
                        'Agence' => $od->agence ? $od->agence->nom : '',
                        'Guichet' => $od->numero_guichet,
                        'Numéro pièce' => $od->numero_piece,
                        'Numéro bordereau' => $od->numero_bordereau,
                        'Référence client' => $od->reference_client,
                        'Nom tiers' => $od->nom_tiers,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'total' => $operationDiverses->count(),
                'total_montant' => number_format($query->sum('montant'), 2, ',', ' ') . ' FCFA',
                'message' => 'Données exportées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique d'une OD (API)
     */
    public function historique($id)
    {
        try {
            $operationDiverse = OperationDiverse::with(['historique.user'])->findOrFail($id);
            
            $historique = $operationDiverse->historique->map(function ($item) {
                return [
                    'action' => $item->action,
                    'description' => $item->description,
                    'ancien_statut' => $item->ancien_statut,
                    'nouveau_statut' => $item->nouveau_statut,
                    'donnees_modifiees' => $item->donnees_modifiees_formatees ?? 'Aucune donnée modifiée',
                    'user' => $item->user->name ?? 'Système',
                    'date' => $item->created_at->format('d/m/Y H:i'),
                    'ip_address' => $item->ip_address
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'od' => [
                        'id' => $operationDiverse->id,
                        'numero_od' => $operationDiverse->numero_od,
                        'libelle' => $operationDiverse->libelle,
                        'montant' => $operationDiverse->montant,
                        'statut' => $operationDiverse->statut
                    ],
                    'historique' => $historique,
                    'workflow' => $operationDiverse->workflow->map(function ($item) {
                        return [
                            'niveau' => $item->niveau,
                            'libelle_niveau' => $item->libelle_niveau,
                            'decision' => $item->decision,
                            'commentaire' => $item->commentaire,
                            'validateur' => $item->validateur ? $item->validateur->name : null,
                            'date_decision' => $item->date_decision ? $item->date_decision->format('d/m/Y H:i') : null
                        ];
                    }),
                    'signatures' => $operationDiverse->signatures->map(function ($item) {
                        return [
                            'niveau_validation' => $item->niveau_validation,
                            'role_validation' => $item->role_validation,
                            'decision' => $item->decision,
                            'commentaire' => $item->commentaire,
                            'validateur' => $item->validateur ? $item->validateur->name : null,
                            'signature_date' => $item->signature_date ? $item->signature_date->format('d/m/Y H:i') : null
                        ];
                    })
                ],
                'message' => 'Historique récupéré avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion des modèles de saisie OD
     */
    
    /**
     * Créer un modèle de saisie (API)
     */
    public function creerModele(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:od_modeles,code',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_operation' => 'required|in:VIREMENT,FRAIS,COMMISSION,REGULARISATION,AUTRE',
            'code_operation' => 'nullable|string|max:50',
            'lignes' => 'required|array|min:2',
            'lignes.*.compte_id' => 'required|exists:plan_comptable,id',
            'lignes.*.sens' => 'required|in:D,C',
            'lignes.*.libelle' => 'required|string|max:255',
            'lignes.*.montant_fixe' => 'nullable|numeric|min:0',
            'lignes.*.taux' => 'nullable|numeric|min:0|max:100',
            'lignes.*.ordre' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $modele = OdModele::create([
                'code' => $request->code,
                'nom' => $request->nom,
                'description' => $request->description,
                'type_operation' => $request->type_operation,
                'code_operation' => $request->code_operation,
                'est_actif' => true,
                'created_by' => Auth::id(),
            ]);

            // Créer les lignes du modèle
            foreach ($request->lignes as $index => $ligne) {
                OdModeleLigne::create([
                    'modele_id' => $modele->id,
                    'compte_id' => $ligne['compte_id'],
                    'sens' => $ligne['sens'],
                    'libelle' => $ligne['libelle'],
                    'montant_fixe' => $ligne['montant_fixe'] ?? null,
                    'taux' => $ligne['taux'] ?? null,
                    'ordre' => $ligne['ordre'] ?? $index,
                ]);
            }

            // Vérifier l'équilibre du modèle
            if (!$modele->estEquilibre()) {
                throw new \Exception('Le modèle n\'est pas équilibré (total débit ≠ total crédit)');
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Modèle créé avec succès',
                'data' => $modele->load(['lignes.compte', 'createur'])
            ], 201);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du modèle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les modèles de saisie (API)
     */
    public function listerModeles(Request $request)
    {
        try {
            $query = OdModele::with(['lignes.compte', 'createur', 'modificateur']);
            
            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }
            
            if ($request->filled('est_actif')) {
                $query->where('est_actif', $request->boolean('est_actif'));
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('nom', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $modeles = $query->orderBy('nom')->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $modeles,
                'message' => 'Modèles récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des modèles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un modèle de saisie (API)
     */
    public function afficherModele($id)
    {
        try {
            $modele = OdModele::with(['lignes.compte', 'createur', 'modificateur'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $modele,
                'message' => 'Modèle récupéré avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Modèle non trouvé.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Modifier un modèle de saisie (API)
     */
    public function modifierModele(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'est_actif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $modele = OdModele::findOrFail($id);
            
            DB::beginTransaction();
            
            $modele->update(array_merge(
                $validator->validated(),
                ['updated_by' => Auth::id()]
            ));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Modèle modifié avec succès',
                'data' => $modele->fresh()->load(['lignes.compte', 'modificateur'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du modèle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un modèle de saisie (API)
     */
    public function supprimerModele($id)
    {
        try {
            $modele = OdModele::findOrFail($id);
            
            // Vérifier si le modèle est utilisé
            $utilisation = OperationDiverse::where('modele_id', $id)->count();
            if ($utilisation > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce modèle est utilisé par ' . $utilisation . ' OD(s) et ne peut pas être supprimé.'
                ], 400);
            }

            DB::beginTransaction();
            
            // Supprimer les lignes du modèle
            $modele->lignes()->delete();
            
            // Supprimer le modèle
            $modele->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Modèle supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du modèle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver un modèle (API)
     */
    public function activerDesactiverModele($id)
    {
        try {
            $modele = OdModele::findOrFail($id);
            
            $modele->update([
                'est_actif' => !$modele->est_actif,
                'updated_by' => Auth::id()
            ]);
            
            $statut = $modele->est_actif ? 'activé' : 'désactivé';
            
            return response()->json([
                'success' => true,
                'message' => "Modèle $statut avec succès.",
                'data' => $modele
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du statut du modèle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupération des données de référence
     */
    
    /**
     * Obtenir les agences (API)
     */
    public function getAgences()
    {
        try {
            $agences = Agency::all();
            
            return response()->json([
                'success' => true,
                'data' => $agences,
                'message' => 'Agences récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des agences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les comptes plan comptable (API)
     */
    public function getComptesPlan()
    {
        try {
            $comptes = PlanComptable::where('est_actif', true)->orderBy('code')->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes plan récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les comptes clients (API)
     */
    public function getComptesClients()
    {
        try {
            $comptes = Compte::where('statut', 'actif')->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes clients récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les comptes collecteurs (468) (API)
     */
    public function getComptesCollecteurs()
    {
        try {
            // Rechercher les comptes collecteurs (468 selon le document)
            $comptes = PlanComptable::where(function($query) {
                $query->where('code', 'like', '468%')
                      ->orWhere('libelle', 'like', '%collecteur%')
                      ->orWhere('libelle', 'like', '%collecte%');
            })
            ->where('est_actif', true)
            ->orderBy('code')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes collecteurs récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes collecteurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les comptes MATA BOOST (37225000 et 37226000) (API)
     */
    public function getComptesMataBoost()
    {
        try {
            // Rechercher les comptes MATA BOOST (37225000 et 37226000 selon le document)
            $comptes = PlanComptable::where(function($query) {
                $query->where('code', 'like', '37225%')
                      ->orWhere('code', 'like', '37226%')
                      ->orWhere('libelle', 'like', '%mata%')
                      ->orWhere('libelle', 'like', '%boost%');
            })
            ->where('est_actif', true)
            ->orderBy('code')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes MATA BOOST récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes MATA BOOST',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les comptes Épargne Journalière (37224000 à 37224012) (API)
     */
    public function getComptesEpargneJournaliere()
    {
        try {
            \Log::info('Début de la récupération des comptes Épargne Journalière');
            // Rechercher les comptes Épargne Journalière (37224000 à 37224012 selon le document)
            $comptes = PlanComptable::where(function($query) {
                $query->where('code', 'like', '37224%')
                      ->orWhere('libelle', 'like', '%épargne%')
                      ->orWhere('libelle', 'like', '%epargne%')
                      ->orWhere('libelle', 'like', '%journalier%')
                      ->orWhere('libelle', 'like', '%journalière%');
            })
            ->where('est_actif', true)
            ->orderBy('code')
            ->get();
            
            \Log::info('Comptes Épargne Journalière récupérés', [
                'nombre' => $comptes->count(),
                'codes' => $comptes->pluck('code')->toArray()
            ]);
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes Épargne Journalière récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes Épargne Journalière',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les comptes de charges (classe 6) (API)
     */
    public function getComptesCharges()
    {
        try {
            // Rechercher les comptes de charges (classe 6)
            $comptes = PlanComptable::where('code', 'like', '6%')
                ->where('est_actif', true)
                ->orderBy('code')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes de charges récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes de charges',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les comptes de passage (47) (API)
     */
    public function getComptesPassage()
    {
        try {
            // Rechercher les comptes de passage (47)
            $comptes = PlanComptable::where('code', 'like', '47%')
                ->where('est_actif', true)
                ->orderBy('code')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes de passage récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes de passage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les caisses (57) (API)
     */
    public function getCaisses(Request $request)
    {
        try {
            $query = Caisse::with(['guichet.agence', 'compteComptable'])
                ->where('est_active', true);
            
            if ($request->filled('agence_id')) {
                $query->whereHas('guichet', function($q) use ($request) {
                    $q->where('agency_id', $request->agence_id);
                });
            }
            
            if ($request->filled('guichet_id')) {
                $query->where('guichet_id', $request->guichet_id);
            }
            
            $caisses = $query->orderBy('code_caisse')->get();
            
            return response()->json([
                'success' => true,
                'data' => $caisses,
                'message' => 'Caisses récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des caisses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les guichets (API)
     */
    public function getGuichets(Request $request)
    {
        try {
            $query = Guichet::with('agence')
                ->where('est_actif', true);
            
            if ($request->filled('agence_id')) {
                $query->where('agency_id', $request->agence_id);
            }
            
            $guichets = $query->orderBy('code_guichet')->get();
            
            return response()->json([
                'success' => true,
                'data' => $guichets,
                'message' => 'Guichets récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des guichets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier l'unicité du numéro de pièce (API)
     */
    public function verifierNumeroPiece(Request $request)
    {
        try {
            $request->validate([
                'numero_piece' => 'required|string|max:50',
            ]);

            $existe = OperationDiverse::where('numero_piece', $request->numero_piece)->exists();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'existe' => $existe,
                    'numero_piece' => $request->numero_piece
                ],
                'message' => $existe ? 'Numéro de pièce déjà utilisé' : 'Numéro de pièce disponible'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du numéro de pièce',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper pour traduire les statuts
     */
    private function traduireStatut(string $statut): string
    {
        $traductions = [
            'BROUILLON' => 'Brouillon',
            'SAISI' => 'Saisi',
            'VALIDE_AGENCE' => 'Validé par agence',
            'VALIDE_COMPTABLE' => 'Validé par comptable',
            'VALIDE_DG' => 'Validé par DG',
            'VALIDE' => 'Validé',
            'REJETE' => 'Rejeté',
            'ANNULE' => 'Annulé',
        ];

        return $traductions[$statut] ?? $statut;
    }



/**
 * Générer un PDF du journal des OD
 */
/**
 * Générer un PDF du journal des OD (VERSION DÉTAILLÉE AVEC ÉCRITURES COMPTABLES)
 */
public function journalPDF(Request $request)
{
    try {
        \Log::info('Début journalPDF détaillé - Filtres:', $request->all());
        
        $query = OperationDiverse::with([
            'agence', 
            'compteDebit', 
            'compteCredit',
            'compteDebitPrincipal',
            'compteCreditPrincipal',
            'saisiPar'
        ])->where(function($query) {
            $query->where('est_comptabilise', true)
                ->orWhere('statut', 'VALIDE');
        });

        // Appliquer les filtres
        if ($request->filled('date')) {
            $query->where('date_operation', $request->date);
        } elseif ($request->filled(['date_debut', 'date_fin'])) {
            $query->whereBetween('date_operation', [$request->date_debut, $request->date_fin]);
        } else {
            // Par défaut, dernier mois
            $query->where('date_operation', '>=', now()->subMonth());
        }
        
        if ($request->filled('agence_id')) {
            $query->where('agence_id', $request->agence_id);
        }
        
        if ($request->filled('type_collecte')) {
            $query->where('type_collecte', $request->type_collecte);
        }
        
        if ($request->filled('code_operation')) {
            $query->where('code_operation', $request->code_operation);
        }
        
        if ($request->filled('numero_guichet')) {
            $query->where('numero_guichet', $request->numero_guichet);
        }

        $operationDiverses = $query->orderBy('date_operation')->orderBy('numero_piece')->get();
        
        \Log::info('Nombre d\'OD trouvées:', ['count' => $operationDiverses->count()]);

        // Préparer les écritures comptables détaillées
        $ecritures = [];
        $totalGeneralDebit = 0;
        $totalGeneralCredit = 0;
        
        foreach ($operationDiverses as $od) {
            // Récupérer toutes les lignes d'écriture pour cette OD
            $lignesEcriture = $this->getLignesEcrituresOD($od);
            
            foreach ($lignesEcriture as $ligne) {
                $ecritures[] = $ligne;
                
                if ($ligne['montant_debit'] > 0) {
                    $totalGeneralDebit += $ligne['montant_debit'];
                }
                if ($ligne['montant_credit'] > 0) {
                    $totalGeneralCredit += $ligne['montant_credit'];
                }
            }
        }

        // Grouper les écritures par numéro de pièce
        $ecrituresParPiece = collect($ecritures)->groupBy('numero_piece')->map(function ($group) {
            return [
                'numero_piece' => $group->first()['numero_piece'],
                'date_operation' => $group->first()['date_operation'],
                'date_comptable' => $group->first()['date_comptable'],
                'libelle_global' => $group->first()['libelle_global'],
                'type_collecte' => $group->first()['type_collecte'],
                'code_operation' => $group->first()['code_operation'],
                'numero_guichet' => $group->first()['numero_guichet'],
                'numero_bordereau' => $group->first()['numero_bordereau'],
                'nom_tiers' => $group->first()['nom_tiers'],
                'reference_client' => $group->first()['reference_client'],
                'ecritures' => $group->sortBy(function ($item) {
                    // Trier pour avoir d'abord les débits puis les crédits
                    return $item['montant_credit'] > 0 ? 1 : 0;
                })->values()->all(),
                'total_debit' => $group->sum('montant_debit'),
                'total_credit' => $group->sum('montant_credit'),
                'nombre_lignes' => $group->count()
            ];
        })->values()->all();

        // Récupérer l'agence si filtrée
        $agence = null;
        if ($request->filled('agence_id')) {
            $agence = Agency::find($request->agence_id);
        }
        
        // Préparer les données des filtres
        $filters = [
            'date' => $request->filled('date') ? $request->date : null,
            'date_debut' => $request->filled('date') 
                ? $request->date 
                : ($request->date_debut ?? now()->subMonth()->format('Y-m-d')),
            'date_fin' => $request->filled('date') 
                ? $request->date 
                : ($request->date_fin ?? now()->format('Y-m-d')),
            'agence' => $agence ? $agence->nom : 'TOUTES LES AGENCES',
            'type_collecte' => $request->type_collecte ?: 'TOUS LES TYPES',
            'code_operation' => $request->code_operation ?: 'TOUS LES CODES',
        ];

        \Log::info('Génération PDF détaillé avec paramètres:', [
            'pieces_count' => count($ecrituresParPiece),
            'ecritures_count' => count($ecritures),
            'total_debit' => $totalGeneralDebit,
            'total_credit' => $totalGeneralCredit,
            'filters' => $filters
        ]);

        // Vérifier si la vue existe
        if (!view()->exists('pdf.journalOD-pdf')) {
            \Log::error('Vue pdf.journalOD-detail-pdf non trouvée');
            throw new \Exception('Vue PDF détaillée non trouvée');
        }
        
        // Générer le PDF avec DomPDF
        $pdf = \PDF::loadView('pdf.journalOD-pdf', [
            'ecrituresParPiece' => $ecrituresParPiece,
            'filters' => $filters,
            'totalGeneralDebit' => $totalGeneralDebit,
            'totalGeneralCredit' => $totalGeneralCredit,
            'nombrePieces' => count($ecrituresParPiece),
            'nombreEcritures' => count($ecritures),
            'generated_at' => now()->format('d/m/Y H:i'),
            'generated_by' => Auth::user() ? Auth::user()->name : 'Système',
        ]);
        
        // Nom du fichier
        $filename = 'journal-od-detaille-' . now()->format('Y-m-d-His') . '.pdf';
        
        \Log::info('PDF détaillé généré avec succès', ['filename' => $filename]);
        
        // Retourner le PDF en téléchargement
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        \Log::error('Erreur journalPDF détaillé:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération du PDF détaillé: ' . $e->getMessage()
        ], 500);
    }
}



    /**
     * Décode un champ JSON en tableau
     */
    private function decodeJsonField($field)
    {
        if (is_string($field) && !empty($field)) {
            return json_decode($field, true) ?? [];
        }
        return $field ?? [];
    }

    /**
     * Extrait les informations d'un compte depuis les données JSON
     */
    private function extraireInfosCompte($compteData, $sens, $comptesClients = [])
    {
        $resultat = [
            'compte_id' => null,
            'montant' => $compteData['montant'] ?? 0,
            'client_nom' => null,
            'client_compte' => null,
            'nom_tiers' => null,
            'reference_client' => null,
            'libelle' => null
        ];
        
        // Déterminer le type de compte
        $type = $compteData['type'] ?? null;
        
        if ($type === 'client') {
            // CAS: Compte client
            $compteClientId = $compteData['compte_client_id'] ?? null;
            
            if ($compteClientId) {
                $compteClient = Compte::with('client', 'planComptable')->find($compteClientId);
                
                if ($compteClient) {
                    // Le compte_id pour l'écriture est le plan_comptable_id
                    $resultat['compte_id'] = $compteClient->plan_comptable_id;
                    
                    if ($compteClient->client) {
                        $resultat['client_nom'] = $compteClient->client->nom_complet;
                        $resultat['client_compte'] = $compteClient->numero_compte;
                        $resultat['nom_tiers'] = $compteClient->client->nom_complet;
                        $resultat['reference_client'] = $compteClient->client->code_client;
                        $resultat['libelle'] = 'Client: ' . $compteClient->client->nom_complet;
                    }
                }
            }
        } elseif ($type === 'plan') {
            // CAS: Compte plan comptable direct
            $resultat['compte_id'] = $compteData['compte_id'] ?? null;
            
            // Vérifier si ce plan comptable est lié à un client
            if ($resultat['compte_id']) {
                $clientInfo = $this->getClientInfoFromPlanCompte($resultat['compte_id'], $comptesClients, $resultat['montant']);
                $resultat = array_merge($resultat, $clientInfo);
            }
        } else {
            // CAS: Ancien format (sans type) - on suppose que c'est un compte plan
            $resultat['compte_id'] = $compteData['compte_id'] ?? null;
            
            // Vérifier dans les comptes clients JSON si présent
            if (!empty($comptesClients)) {
                foreach ($comptesClients as $clientData) {
                    if (isset($clientData['compte_client_id']) && 
                        isset($clientData['montant']) && 
                        abs($clientData['montant'] - $resultat['montant']) < 0.01) {
                        
                        $compteClient = Compte::with('client')->find($clientData['compte_client_id']);
                        if ($compteClient && $compteClient->client) {
                            $resultat['client_nom'] = $compteClient->client->nom_complet;
                            $resultat['client_compte'] = $compteClient->numero_compte;
                            $resultat['nom_tiers'] = $compteClient->client->nom_complet;
                            $resultat['reference_client'] = $compteClient->client->code_client;
                            $resultat['libelle'] = 'Client: ' . $compteClient->client->nom_complet;
                        }
                        break;
                    }
                }
            }
        }
        
        return $resultat;
    }

    /**
     * Récupère les infos client à partir d'un ID de plan comptable
     */
    private function getClientInfoFromPlanCompte($planCompteId, $comptesClients = [], $montant = null)
    {
        $resultat = [
            'client_nom' => null,
            'client_compte' => null,
            'nom_tiers' => null,
            'reference_client' => null,
            'libelle' => null
        ];
        
        // Chercher si ce plan comptable est directement lié à un compte client
        $compteClient = Compte::with('client')
            ->where('plan_comptable_id', $planCompteId)
            ->first();
        
        if ($compteClient && $compteClient->client) {
            $resultat['client_nom'] = $compteClient->client->nom_complet;
            $resultat['client_compte'] = $compteClient->numero_compte;
            $resultat['nom_tiers'] = $compteClient->client->nom_complet;
            $resultat['reference_client'] = $compteClient->client->code_client;
            $resultat['libelle'] = $compteClient->client->nom_complet . ' - ' . $compteClient->libelle;
        }
        
        // Si on a des comptes clients JSON, vérifier aussi
        if (!empty($comptesClients) && $montant) {
            foreach ($comptesClients as $clientData) {
                if (isset($clientData['compte_client_id']) && 
                    isset($clientData['montant']) && 
                    abs($clientData['montant'] - $montant) < 0.01) {
                    
                    $compteClient = Compte::with('client')->find($clientData['compte_client_id']);
                    if ($compteClient && $compteClient->client) {
                        $resultat['client_nom'] = $compteClient->client->nom_complet;
                        $resultat['client_compte'] = $compteClient->numero_compte;
                        $resultat['nom_tiers'] = $compteClient->client->nom_complet;
                        $resultat['reference_client'] = $compteClient->client->code_client;
                        $resultat['libelle'] = 'Client: ' . $compteClient->client->nom_complet;
                    }
                    break;
                }
            }
        }
        
        return $resultat;
    }

    /**
     * Récupérer toutes les lignes d'écritures comptables pour une OD
     */
    private function getLignesEcrituresOD($od)
    {
        $lignes = [];
        
        // Récupérer les informations de base
        $numeroPiece = $od->numero_piece ?? 'PIECE-' . $od->id;
        $dateOperation = $od->date_operation ? Carbon::parse($od->date_operation)->format('d/m/Y') : '';
        $dateComptable = $od->date_comptable ? Carbon::parse($od->date_comptable)->format('d/m/Y') : $dateOperation;
        $libelleGlobal = $od->libelle ?? 'Sans libellé';
        $typeCollecte = $od->type_collecte ?? 'AUTRE';
        $codeOperation = $od->code_operation ?? '-';
        $numeroGuichet = $od->numero_guichet ?? '-';
        $numeroBordereau = $od->numero_bordereau ?? '-';
        $nomTiers = $od->nom_tiers ?? '-';
        $referenceClient = $od->reference_client ?? '-';
        
        // Décoder les JSON si nécessaire
        $comptesClients = $this->decodeJsonField($od->comptes_clients_json);
        $comptesDebits = $this->decodeJsonField($od->comptes_debits_json);
        $comptesCredits = $this->decodeJsonField($od->comptes_credits_json);
        
        // 1. TRAITER LES COMPTES DÉBITS
        if (!empty($comptesDebits)) {
            // CAS 1: Multi-comptes en débit (JSON)
            foreach ($comptesDebits as $index => $compteDebit) {
                $resultat = $this->extraireInfosCompte($compteDebit, 'DEBIT', $comptesClients);
                
                if (!$resultat['compte_id']) {
                    \Log::warning('Impossible de déterminer le compte_id pour débit', ['compteDebit' => $compteDebit]);
                    continue;
                }
                
                $planCompte = PlanComptable::find($resultat['compte_id']);
                
                $lignes[] = [
                    'numero_piece' => $numeroPiece,
                    'date_operation' => $dateOperation,
                    'date_comptable' => $dateComptable,
                    'libelle_global' => $libelleGlobal,
                    'libelle_ligne' => $resultat['libelle'] ?? $libelleGlobal . ($index > 0 ? ' (suite)' : ''),
                    'type_collecte' => $typeCollecte,
                    'code_operation' => $codeOperation,
                    'numero_guichet' => $numeroGuichet,
                    'numero_bordereau' => $numeroBordereau,
                    'nom_tiers' => $resultat['nom_tiers'] ?? $nomTiers,
                    'reference_client' => $resultat['reference_client'] ?? $referenceClient,
                    'compte_code' => $planCompte ? $planCompte->code : 'N/A',
                    'compte_libelle' => $planCompte ? $planCompte->libelle : 'Compte inconnu',
                    'compte_id' => $resultat['compte_id'],
                    'montant_debit' => (float) $resultat['montant'],
                    'montant_credit' => 0,
                    'ordre' => $index,
                    'type_ligne' => 'DEBIT',
                    'client_nom' => $resultat['client_nom'] ?? null,
                    'client_compte' => $resultat['client_compte'] ?? null
                ];
            }
        } elseif ($od->compte_debit_id) {
            // CAS 2: Compte débit unique (champ direct)
            $montant = $od->montant_total ?: $od->montant;
            $planCompte = PlanComptable::find($od->compte_debit_id);
            
            // Chercher si c'est un compte client
            $clientInfo = $this->getClientInfoFromPlanCompte($od->compte_debit_id);
            
            $lignes[] = [
                'numero_piece' => $numeroPiece,
                'date_operation' => $dateOperation,
                'date_comptable' => $dateComptable,
                'libelle_global' => $libelleGlobal,
                'libelle_ligne' => $clientInfo['libelle'] ?? $libelleGlobal,
                'type_collecte' => $typeCollecte,
                'code_operation' => $codeOperation,
                'numero_guichet' => $numeroGuichet,
                'numero_bordereau' => $numeroBordereau,
                'nom_tiers' => $clientInfo['nom_tiers'] ?? $nomTiers,
                'reference_client' => $clientInfo['reference_client'] ?? $referenceClient,
                'compte_code' => $planCompte ? $planCompte->code : 'N/A',
                'compte_libelle' => $planCompte ? $planCompte->libelle : 'Compte inconnu',
                'compte_id' => $od->compte_debit_id,
                'montant_debit' => (float) $montant,
                'montant_credit' => 0,
                'ordre' => 0,
                'type_ligne' => 'DEBIT',
                'client_nom' => $clientInfo['client_nom'] ?? null,
                'client_compte' => $clientInfo['client_compte'] ?? null
            ];
        }
        
        // 2. TRAITER LES COMPTES CRÉDITS
        if (!empty($comptesCredits)) {
            // CAS 1: Multi-comptes en crédit (JSON)
            foreach ($comptesCredits as $index => $compteCredit) {
                $resultat = $this->extraireInfosCompte($compteCredit, 'CREDIT', $comptesClients);
                
                if (!$resultat['compte_id']) {
                    \Log::warning('Impossible de déterminer le compte_id pour crédit', ['compteCredit' => $compteCredit]);
                    continue;
                }
                
                $planCompte = PlanComptable::find($resultat['compte_id']);
                
                $lignes[] = [
                    'numero_piece' => $numeroPiece,
                    'date_operation' => $dateOperation,
                    'date_comptable' => $dateComptable,
                    'libelle_global' => $libelleGlobal,
                    'libelle_ligne' => $resultat['libelle'] ?? $libelleGlobal . ($index > 0 ? ' (suite)' : ''),
                    'type_collecte' => $typeCollecte,
                    'code_operation' => $codeOperation,
                    'numero_guichet' => $numeroGuichet,
                    'numero_bordereau' => $numeroBordereau,
                    'nom_tiers' => $resultat['nom_tiers'] ?? $nomTiers,
                    'reference_client' => $resultat['reference_client'] ?? $referenceClient,
                    'compte_code' => $planCompte ? $planCompte->code : 'N/A',
                    'compte_libelle' => $planCompte ? $planCompte->libelle : 'Compte inconnu',
                    'compte_id' => $resultat['compte_id'],
                    'montant_debit' => 0,
                    'montant_credit' => (float) $resultat['montant'],
                    'ordre' => $index + 100, // Pour mettre les crédits après les débits
                    'type_ligne' => 'CREDIT',
                    'client_nom' => $resultat['client_nom'] ?? null,
                    'client_compte' => $resultat['client_compte'] ?? null
                ];
            }
        } elseif ($od->compte_credit_id) {
            // CAS 2: Compte crédit unique (champ direct)
            $montant = $od->montant_total ?: $od->montant;
            $planCompte = PlanComptable::find($od->compte_credit_id);
            
            // Chercher si c'est un compte client
            $clientInfo = $this->getClientInfoFromPlanCompte($od->compte_credit_id);
            
            $lignes[] = [
                'numero_piece' => $numeroPiece,
                'date_operation' => $dateOperation,
                'date_comptable' => $dateComptable,
                'libelle_global' => $libelleGlobal,
                'libelle_ligne' => $clientInfo['libelle'] ?? $libelleGlobal,
                'type_collecte' => $typeCollecte,
                'code_operation' => $codeOperation,
                'numero_guichet' => $numeroGuichet,
                'numero_bordereau' => $numeroBordereau,
                'nom_tiers' => $clientInfo['nom_tiers'] ?? $nomTiers,
                'reference_client' => $clientInfo['reference_client'] ?? $referenceClient,
                'compte_code' => $planCompte ? $planCompte->code : 'N/A',
                'compte_libelle' => $planCompte ? $planCompte->libelle : 'Compte inconnu',
                'compte_id' => $od->compte_credit_id,
                'montant_debit' => 0,
                'montant_credit' => (float) $montant,
                'ordre' => 100,
                'type_ligne' => 'CREDIT',
                'client_nom' => $clientInfo['client_nom'] ?? null,
                'client_compte' => $clientInfo['client_compte'] ?? null
            ];
        }
        
        // Trier les lignes par ordre
        usort($lignes, function($a, $b) {
            return $a['ordre'] <=> $b['ordre'];
        });
        
        return $lignes;
    }
    /**
     * Obtenir les informations client pour un compte plan comptable
     */
    private function getClientInfoForCompte($comptePlanId, $comptesClients, $montant)
    {
        $result = [
            'client_nom' => null,
            'client_compte' => null,
            'nom_tiers' => null,
            'reference_client' => null,
            'libelle' => null
        ];
        
        // Chercher si ce compte plan correspond à un compte client
        $compteClient = Compte::with('client')
            ->where('plan_comptable_id', $comptePlanId)
            ->first();
        
        if ($compteClient && $compteClient->client) {
            $result['client_nom'] = $compteClient->client->nom_complet;
            $result['client_compte'] = $compteClient->numero_compte;
            $result['nom_tiers'] = $compteClient->client->nom_complet;
            $result['reference_client'] = $compteClient->client->code_client;
            $result['libelle'] = $compteClient->client->nom_complet . ' - ' . $compteClient->libelle;
        }
        
        // Vérifier dans les comptes clients JSON (pour MATA BOOST avec répartition)
        if (!empty($comptesClients) && is_array($comptesClients)) {
            foreach ($comptesClients as $clientData) {
                if (isset($clientData['compte_client_id']) && isset($clientData['montant']) && abs($clientData['montant'] - $montant) < 0.01) {
                    $compteClient = Compte::with('client')->find($clientData['compte_client_id']);
                    if ($compteClient && $compteClient->client) {
                        $result['client_nom'] = $compteClient->client->nom_complet;
                        $result['client_compte'] = $compteClient->numero_compte;
                        $result['nom_tiers'] = $compteClient->client->nom_complet;
                        $result['reference_client'] = $compteClient->client->code_client;
                        $result['libelle'] = 'Client: ' . $compteClient->client->nom_complet;
                    }
                    break;
                }
            }
        }
        
        return $result;
    }


    /**
     * Enregistrer le code de validation DG (API)
     */
    public function enregistrerCodeValidationDG(Request $request, $id)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:255',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien DG
            if (!$user->hasRole('DG')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le DG peut enregistrer un code de validation.'
                ], 403);
            }

            // Vérifier que l'OD a été validée par le DG
            $workflowDG = $operationDiverse->workflow()
                ->where('niveau', OperationDiverse::NIVEAU_DG)
                ->where('decision', 'APPROUVE')
                ->where('user_id', $user->id)
                ->first();

            if (!$workflowDG) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez d\'abord valider cette OD en tant que DG.'
                ], 400);
            }

            // Enregistrer le code
            if ($operationDiverse->enregistrerCodeValidationDG($request->code, $user)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Code de validation enregistré avec succès.',
                    'data' => [
                        'od' => $operationDiverse,
                        'code' => $request->code,
                        'workflow' => $workflowDG->fresh()
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du code.'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du code.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les codes de validation DG (API)
     */
public function getCodesValidationDG(Request $request)
{
    try {
        // Récupérer les codes selon les conditions spécifiques
        $codes = OdWorkflow::where('role_requis', 'DG')
            ->where('decision', 'APPROUVE')
            ->whereNotNull('code_a_verifier')
            ->where('code_a_verifier', '!=', '')
            ->select('id', 'operation_diverse_id', 'code_a_verifier', 'date_decision')
            ->orderBy('date_decision', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $codes,
            'message' => 'Codes de validation DG récupérés avec succès.',
            'count' => $codes->count(),
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des codes.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Vérifier un code de validation DG (API)
     */
    public function verifierCodeDG(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:255',
                'od_id' => 'required|exists:operation_diverses,id',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($request->od_id);
            
            // Vérifier le code
            $codeCorrect = $operationDiverse->workflow()
                ->where('niveau', OperationDiverse::NIVEAU_DG)
                ->where('decision', 'APPROUVE')
                ->where('code_a_verifier', $request->code)
                ->exists();

            if ($codeCorrect) {
                // Optionnel: Marquer le code comme vérifié
                $workflowDG = $operationDiverse->workflow()
                    ->where('niveau', OperationDiverse::NIVEAU_DG)
                    ->where('decision', 'APPROUVE')
                    ->where('code_a_verifier', $request->code)
                    ->first();

                if ($workflowDG) {
                    $workflowDG->marquerCodeVerifie();
                    
                    // Enregistrer dans l'historique
                    $operationDiverse->enregistrerHistorique(
                        'CODE_VALIDATION_DG_VERIFIE',
                        $operationDiverse->statut,
                        $operationDiverse->statut,
                        Auth::user()
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Code de validation correct.',
                    'data' => [
                        'valide' => true,
                        'od' => $operationDiverse->fresh(),
                        'message' => 'Le code a été vérifié avec succès.'
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Code de validation incorrect.',
                'data' => [
                    'valide' => false,
                    'message' => 'Le code saisi ne correspond pas.'
                ]
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du code.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Models\Compte\ContratDat;
use App\Models\Compte\DatType;
use App\Services\Compte\DATService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class DatContratController extends Controller
{
    protected $datService;

    /**
     * Constructeur unique
     * Gestion des injections et des middlewares (Sécurité)
     */
    public function __construct(DATService $datService)
    {
        $this->datService = $datService;

        // Note: Middleware 'check.agence.ouverte' est appliqué au niveau des routes (api.php)

        // Protection des routes par permissions
        $this->middleware('can:saisir dat')->only(['store', 'simulate']);
        $this->middleware('can:valider dat')->only(['valider']);
        $this->middleware('can:cloturer dat')->only(['cloturer']);
    }

    /**
     * Liste tous les contrats (avec filtrage par agence pour les agents)
     */
    public function index(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $query = ContratDat::with([
                'type', 
                'clientSourceAccount.client', 
                'destinationInteret', 
                'destinationCapital',
                'compte.client'
            ]);

            // Filtrage multi-agences
            if (!$user->hasAnyRole(['Admin', 'DG'])) {
                if (!$user->agency_id) {
                    return response()->json([
                        'success' => false, 
                        'message' => "Accès restreint : Votre profil n'est rattaché à aucune agence."
                    ], 403);
                }

                $query->whereHas('compte.client', function($q) use ($user) {
                    $q->where('agency_id', $user->agency_id);
                });
            }

            $contracts = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'donnees'  => $contracts
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ÉTAPE 1 : Souscription (Saisie initiale)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'client_source_account_id' => 'required|exists:comptes,id',
                'account_id'               => 'required|exists:comptes,id', 
                'dat_type_id'              => 'required|exists:dat_types,id',
                'montant'                  => 'required|numeric|min:1000',
                'mode_versement'           => 'nullable|in:CAPITALISATION,VERSEMENT_PERIODIQUE',
                'periodicite'              => 'nullable|in:M,T,S,A,E',
                'destination_interet_id'   => 'nullable|exists:comptes,id',
                'destination_capital_id'   => 'nullable|exists:comptes,id',
                'is_precompte'             => 'nullable|boolean',
                'is_jours_reels'           => 'nullable|boolean',
                'date_execution'           => 'required|date',
                'date_maturite'            => 'required|date',
                'date_valeur'              => 'nullable|date',
            ]);

            // ✅ Transaction atomique : tout ou rien
            $contrat = DB::transaction(function () use ($validated) {
                return $this->datService->creerContratEnAttente($validated);
            });

            return response()->json([
                'success' => true,
                'message' => 'Contrat saisi avec succès. En attente de validation.',
                'donnees' => [
                    'id' => $contrat->id,
                    'numero_ordre' => $contrat->numero_ordre,
                    'statut' => $contrat->statut,
                    // ✅ date_comptable et jour_comptable_id remplis par le trait
                    'date_comptable' => $contrat->date_comptable ?? null,
                    'jour_comptable_id' => $contrat->jour_comptable_id ?? null,
                ]
            ], 201);

        } catch (Exception $e) {
            // ✅ Rollback automatique en cas d'erreur
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * ÉTAPE 2 : Validation (Activation et flux comptables)
     */
    public function valider($id): JsonResponse
    {
        try {
            $contrat = ContratDat::findOrFail($id);

            if ($contrat->statut !== 'EN_ATTENTE') {
                return response()->json([
                    'success' => false, 
                    'message' => "Statut invalide pour validation : {$contrat->statut}."
                ], 400);
            }

            // ✅ Transaction atomique : tout ou rien
            $resultat = DB::transaction(function () use ($contrat) {
                return $this->datService->validerEtActiver($contrat);
            });

            return response()->json([
                'success' => true,
                'message' => 'Contrat activé et fonds transférés avec succès.',
                'donnees' => [
                    'numero_ordre' => $resultat->numero_ordre,
                    'statut' => $resultat->statut,
                    // ✅ date_comptable et jour_comptable_id remplis par le trait
                    'date_comptable' => $resultat->date_comptable ?? null,
                    'jour_comptable_id' => $resultat->jour_comptable_id ?? null,
                ]
            ]);
        } catch (Exception $e) {
            // ✅ Rollback automatique en cas d'erreur
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simulation rapide avant souscription
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'montant'     => 'required|numeric|min:0',
            'dat_type_id' => 'required|exists:dat_types,id'
        ]);

        try {
            $type = DatType::findOrFail($request->dat_type_id);
            // Calcul simplifié : (Capital * Taux * Mois) / 12
            $gain_brut = ($request->montant * $type->taux_interet * $type->duree_mois) / 12;

            return response()->json([
                'success'    => true,
                'simulation' => [
                    'gain_net'       => round($gain_brut, 0),
                    'total_echeance' => round($request->montant + $gain_brut, 0),
                    'date_fin'       => now()->addMonths($type->duree_mois)->format('d/m/Y'),
                    'taux'           => $type->taux_interet
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Clôture du contrat (À maturité ou anticipée)
     */
    public function cloturer($id): JsonResponse
    {
        try {
            $contrat = ContratDat::findOrFail($id);
            
            if ($contrat->statut !== 'ACTIF') {
                return response()->json(['success' => false, 'message' => 'Seul un contrat ACTIF peut être clôturé.'], 400);
            }

            // ✅ Transaction atomique : tout ou rien
            $details = DB::transaction(function () use ($contrat) {
                return $this->datService->cloturerContrat($contrat);
            });

            return response()->json([
                'success' => true,
                'message' => 'Contrat clôturé avec succès.',
                'donnees' => $details
            ]);
        } catch (Exception $e) {
            // ✅ Rollback automatique en cas d'erreur
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Détails et calculs de sortie en temps réel
     */
    public function show($id): JsonResponse
    {
        try {
            $contrat = ContratDat::with(['compte', 'type', 'clientSourceAccount.client'])->findOrFail($id);
            $simulation = $this->datService->calculerDetailsSortie($contrat);

            return response()->json([
                'success' => true,
                'donnees' => [
                    'numero_ordre'    => $contrat->numero_ordre,
                    'statut'          => $contrat->statut,
                    'capital_actuel'  => $simulation['capital_actuel'],
                    'interets_courus' => $simulation['interets_courus'],
                    'penalites'       => $simulation['penalite'],
                    'montant_final'   => $simulation['net_a_payer'],
                    'est_anticipe'    => $simulation['est_anticipe'],
                    'date_maturite'   => $contrat->date_maturite
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Contrat introuvable.'], 404);
        }
    }
}
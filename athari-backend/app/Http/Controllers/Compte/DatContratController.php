<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Models\Compte\ContratDat;
use App\Models\Compte\DatType;
use App\Services\Compte\DATService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;

// Importations obligatoires pour les middlewares dans Laravel 12
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DatContratController extends Controller implements HasMiddleware
{
    protected $datService;

    public function __construct(DATService $datService)
    {
        $this->datService = $datService;
    }

    /**
     * Définition des middlewares pour Laravel 12
     */
    public static function middleware(): array
    {
        return [
            // Seuls ceux qui ont 'saisir dat' accèdent à store et simulate
            new Middleware('can:saisir dat', only: ['store', 'simulate']),
            
            // Seuls ceux qui ont 'valider dat' accèdent à la validation
            new Middleware('can:valider dat', only: ['valider']),
            
            // Seuls ceux qui ont 'cloturer dat' accèdent à la clôture
            new Middleware('can:cloturer dat', only: ['cloturer']),
        ];
    }

    /**
     * Liste tous les contrats (avec filtrage par agence)
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

        // 1. On vérifie si l'utilisateur est un simple agent (ni Admin, ni DG)
        if (!$user->hasAnyRole(['Admin', 'DG'])) {
            
            // 2. Si c'est un agent, il DOIT avoir une agence pour filtrer
            if (!$user->agency_id) {
                return response()->json([
                    'success' => false, 
                    'message' => "Accès restreint : Votre profil agent n'est rattaché à aucune agence."
                ], 403);
            }

            // 3. On applique le filtre par agence
            $query->whereHas('compte.client', function($q) use ($user) {
                $q->where('clients.agency_id', $user->agency_id);
            });
        }
        
        // Si c'est un Admin ou DG, le code saute le bloc IF et récupère TOUT.

        $contracts = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'donnees' => $contracts
        ]);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    /**
     * ÉTAPE 1 : Souscription (Saisie - Statut EN_ATTENTE)
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
                  'date_valeur'            => 'nullable|date',

            ]);

            $contrat = $this->datService->creerContratEnAttente($validated);

            return response()->json([
                'success' => true,
                'message' => 'Contrat saisi avec succès. En attente de validation comptable.',
                'donnees' => $contrat
            ], 201);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * ÉTAPE 2 : Validation (Activation et mouvements comptables)
     */
    public function valider($id): JsonResponse
    {
        try {
            $contrat = ContratDat::findOrFail($id);

            if ($contrat->statut !== 'EN_ATTENTE') {
                return response()->json(['success' => false, 'message' => 'Ce contrat ne peut plus être validé (statut actuel: '.$contrat->statut.').'], 400);
            }

            $resultat = $this->datService->validerEtActiver($contrat);

            return response()->json([
                'success' => true,
                'message' => 'Le contrat a été validé et activé avec succès.',
                'donnees' => $resultat
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simulation rapide avant création
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'montant' => 'required|numeric|min:0',
            'dat_type_id' => 'required|exists:dat_types,id'
        ]);

        try {
            $type = DatType::findOrFail($request->dat_type_id);
            $gain_brut = ($request->montant * ($type->taux_interet ) * $type->duree_mois) / 12;

            return response()->json([
                'success' => true,
                'simulation' => [
                    'gain_net' => round($gain_brut, 0),
                    'total_echeance' => round($request->montant + $gain_brut, 0),
                    'date_fin' => now()->addMonths($type->duree_mois)->format('d/m/Y'),
                    'taux' => $type->taux_interet
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Clôture définitive du contrat
     */
    public function cloturer($id): JsonResponse
    {
        try {
            $contrat = ContratDat::findOrFail($id);
            
            if ($contrat->statut !== 'ACTIF') {
                return response()->json(['success' => false, 'message' => 'Seul un contrat ACTIF peut être clôturé.'], 400);
            }

            $details = $this->datService->cloturerContrat($contrat);

            return response()->json([
                'success' => true,
                'message' => 'Contrat clôturé avec succès.',
                'donnees' => $details
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Affichage des détails d'un contrat spécifique
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
<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CaisseTransaction;
use App\Models\Caisse\TransactionTier;
use App\Services\ComptabiliteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Exemple : Contrôleur de Versement avec injection automatique de date_comptable
 * 
 * ✅ Points clés :
 * - Middleware 'check.agence.ouverte' valide l'agence avant l'action
 * - DB::transaction() assure l'atomicité (tout ou rien)
 * - Le trait UsesDateComptable remplit automatiquement date_comptable & jour_comptable_id
 * - Gestion d'erreur robuste avec rollback automatique
 */
class VersementExampleController extends Controller
{
    /**
     * Appliquer le middleware sur toutes les routes
     */
    public function __construct()
    {
        $this->middleware('check.agence.ouverte');
    }

    /**
     * Créer un versement
     * 
     * POST /api/versements
     * Body:
     * {
     *   "montant": 10000,
     *   "compte_id": 5,
     *   "tiers": {
     *     "nom_complet": "Jean Dupont",
     *     "type_piece": "CIN",
     *     "numero_piece": "12345678"
     *   }
     * }
     */
    public function store(Request $request)
    {
        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'montant'                   => 'required|numeric|min:1',
            'compte_id'                 => 'required|exists:comptes,id',
            'tiers.nom_complet'         => 'required|string|max:255',
            'tiers.type_piece'          => 'required|string',
            'tiers.numero_piece'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // ✅ Récupérer l'agence depuis l'utilisateur connecté
            $agenceId = auth()->user()->agence_id;

            // ✅ Vérifier que la journée est ouverte (optionnel, le middleware l'a déjà fait)
            $session = ComptabiliteService::getActiveSessionOrFail($agenceId);

            // ✅ Transaction DB pour atomicité
            $transaction = DB::transaction(function () use ($request, $session, $agenceId) {
                
                // 1. Créer la transaction de caisse
                // Le trait UsesDateComptable remplira automatiquement :
                // - date_comptable = $session->jourComptable->date_du_jour
                // - jour_comptable_id = $session->jourComptable->id
                
                $caisseTransaction = CaisseTransaction::create([
                    'montant_brut'      => $request->montant,
                    'compte_id'         => $request->compte_id,
                    'type_flux'         => 'VERSEMENT',
                    'type_versement'    => 'ESPECE',
                    'code_agence'       => auth()->user()->agence_id,
                    'reference_unique'  => 'VRS-' . now()->format('YmdHis') . '-' . uniqid(),
                    'agence_id'         => $agenceId,
                    // date_comptable et jour_comptable_id seront remplis par le trait !
                ]);

                // 2. Créer l'enregistrement du tiers (optionnel, si vous le gardez)
                TransactionTier::create([
                    'transaction_id'    => $caisseTransaction->id,
                    'nom_complet'       => $request->tiers['nom_complet'],
                    'type_piece'        => $request->tiers['type_piece'],
                    'numero_piece'      => $request->tiers['numero_piece'],
                    'agence_id'         => $agenceId,
                    // date_comptable et jour_comptable_id seront remplis par le trait !
                ]);

                // 3. Autres écritures comptables, mouvements, etc.
                // (vos appels CaisseService, MouvementComptable, etc.)

                return $caisseTransaction;
            });

            // ✅ Réponse réussie
            return response()->json([
                'success'   => true,
                'message'   => 'Versement créé avec succès',
                'data'      => [
                    'reference'         => $transaction->reference_unique,
                    'montant'           => $transaction->montant_brut,
                    'date_comptable'    => $transaction->date_comptable, // ✅ Rempli auto
                    'jour_comptable_id' => $transaction->jour_comptable_id, // ✅ Rempli auto
                ]
            ], 201);

        } catch (Exception $e) {
            // ✅ Rollback automatique + message d'erreur
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

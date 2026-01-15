<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CaisseController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        // On injecte le service et on applique le middleware de sécurité
        $this->caisseService = $caisseService;
    }

    /**
     * Traite la validation finale d'une opération (Bouton OK / F4)
     */
    public function validerOperation(Request $request)
    {
        // 1. Validation des données entrantes
        $validator = Validator::make($request->all(), [
            'type_flux' => 'required|in:VERSEMENT,RETRAIT,ENTREE_CAISSE,SORTIE_CAISSE',
            'compte_id' => 'required_if:type_flux,VERSEMENT,RETRAIT|exists:comptes,id',
            'montant_brut' => 'required|numeric|min:1',
            'net_a_percevoir_payer' => 'required|numeric',
            'billetage' => 'required|array|min:1', // Doit contenir les coupures
            'billetage.*.valeur' => 'required|integer',
            'billetage.*.quantite' => 'required|integer|min:0',
            
            // IDs du plan comptable envoyés par le formulaire (ou chargés par défaut)
            'pc_caisse_id' => 'required|exists:plan_comptable,id',
            'pc_client_id' => 'required_if:type_flux,VERSEMENT,RETRAIT|exists:plan_comptable,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // 2. Vérification de cohérence Billetage vs Montant Net
            $totalBilletage = collect($request->billetage)->sum(function ($item) {
                return $item['valeur'] * $item['quantite'];
            });

            if ($totalBilletage != $request->net_a_percevoir_payer) {
                throw new Exception("Le total du billetage ($totalBilletage) ne correspond pas au montant net de l'opération.");
            }

            // 3. Appel au Service pour le traitement lourd (DB Transaction)
            $transaction = $this->caisseService->traiterOperation(
                $request->type_flux,
                $request->all(),
                $request->billetage
            );

            // 4. Réponse de succès (Prêt pour l'impression du reçu)
            return response()->json([
                'success' => true,
                'message' => 'Opération validée avec succès.',
                'reference' => $transaction->reference_unique,
                'transaction_id' => $transaction->id
            ]);

        } catch (Exception $e) {
            // Gestion des erreurs bancaires (SPRV, FRME, etc.)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class VersementController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    public function store(Request $request)
    {
        // 1. Validation dynamique
        $validator = Validator::make($request->all(), [
            'compte_id'             => 'required|exists:comptes,id',
            'montant_brut'          => 'required|numeric|min:1',
            
            // On accepte les types que vous avez définis (OM, MOMO ou ESPECE)
            'type_versement'        => 'required|in:ESPECE,OM,MOMO,ORANGE_MONEY,MOBILE_MONEY',
              // 'net_a_percevoir_payer' => 'required|numeric',

            // BILLETAGE : Obligatoire SEULEMENT si type_versement est ESPECE
            'billetage'             => 'required_if:type_versement,ESPECE|array',
            'billetage.*.valeur'    => 'required_with:billetage|numeric',
            'billetage.*.quantite'  => 'required_with:billetage|integer|min:1',
            
            // Tiers (Remettant)
            'tiers'                 => 'required|array',
            'tiers.nom_complet'     => 'required|string|max:255',
            'tiers.type_piece'      => 'required|string|max:50',
            'tiers.numero_piece'    => 'required|string|max:50',
            
            // Optionnel pour OM/MOMO
            'reference_externe'     => 'nullable|string',
            'commissions'           => 'nullable|numeric',
            'numero_bordereau' => 'required|string' ,
            'type_bordereau'   => 'required|string' ,
            'origine_fonds'    => 'nullable|string|max:100',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Vérification arithmétique conditionnelle du billetage
            if ($request->type_versement === 'ESPECE') {
                $totalBilletage = collect($request->billetage)->sum(function($b) {
                    return $b['valeur'] * $b['quantite'];
                });

                if (round($totalBilletage, 2) != round($request->net_a_percevoir_payer, 2)) {
                    throw new Exception("Erreur de billetage : Le total calculé ($totalBilletage) ne correspond pas au montant net saisi.");
                }
            } else {
                // Pour OM/MOMO, on s'assure que le billetage passé au service est un tableau vide
                $request->merge(['billetage' => []]);
            }

            // 3. Appel du Service
            $transaction = $this->caisseService->traiterOperation(
                'VERSEMENT', 
                $request->all(), 
                $request->billetage ?? []
            );

            // 4. Réponse de succès
            return response()->json([
                'success' => true,
                'message' => 'Versement effectué avec succès.',
                'data'    => [
                    'reference'      => $transaction->reference_unique,
                    'montant_verse'  => $transaction->montant_brut,
                    'frais_appliqués'=> ($transaction->commissions ?? 0) + ($transaction->taxes ?? 0),
                    'date_operation' => $transaction->date_operation,
                    'type_versement' => $transaction->type_versement,
                    'caissier'       => auth()->user()->name ?? 'Système',
                    'agence_code'    => $transaction->code_agence
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => "Échec de l'opération : " . $e->getMessage()
            ], 400);
        }
    }

    public function imprimerRecu($id)
    {
        try {
            // Appelle la méthode du service que vous avez montrée précédemment
            return $this->caisseService->genererRecu($id);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Impossible de générer le reçu : ' . $e->getMessage()
            ], 404);
        }
    }
}
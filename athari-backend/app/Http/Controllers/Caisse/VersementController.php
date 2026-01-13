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
        
        // SÉCURITÉ : Réactivez ce middleware dès que vos tables Guichets/Caisses sont prêtes
        // $this->middleware('auth:sanctum'); 
    }

    public function store(Request $request)
    {
        // 1. Validation stricte des données d'entrée
        $validator = Validator::make($request->all(), [
            'compte_id'             => 'required|exists:comptes,id',
            'montant_brut'          => 'required|numeric|min:1',
            'remettant_nom'         => 'required|string|max:255',
            
            // Validation des comptes du Plan Comptable (IDs issus de votre fichier SQL)
            'pc_caisse_id'          => 'required|exists:plan_comptable,id',
            'pc_client_id'          => 'required|exists:plan_comptable,id',
            
            // Billetage
            'billetage'             => 'required|array|min:1',
            'billetage.*.valeur'    => 'required|numeric',
            'billetage.*.quantite'  => 'required|integer|min:1',
            
            'net_a_percevoir_payer' => 'required|numeric',
            
            // Informations sur le porteur/tiers
            'tiers'                 => 'required|array',
            'tiers.nom_complet'     => 'required|string|max:255',
            'tiers.type_piece'      => 'required|string|max:50',
            'tiers.numero_piece'    => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Vérification arithmétique : Billetage vs Montant déclaré
            $totalBilletage = collect($request->billetage)->sum(function($b) {
                return $b['valeur'] * $b['quantite'];
            });

            if (round($totalBilletage, 2) != round($request->net_a_percevoir_payer, 2)) {
                return response()->json([
                    'success' => false, 
                    'message' => "Erreur de billetage : Le total calculé ($totalBilletage) ne correspond pas au montant net saisi."
                ], 400);
            }

            // 3. Appel du Service (qui gère la transaction DB, le solde caisse, et la comptabilité)
            $transaction = $this->caisseService->traiterOperation(
                'VERSEMENT', 
                $request->all(), 
                $request->billetage
            );

            // 4. Réponse de succès
            return response()->json([
                'success' => true,
                'message' => 'Versement effectué avec succès.',
                'data'    => [
                    'reference'      => $transaction->reference_unique,
                    'montant_verse'  => $transaction->montant_brut,
                    'frais_appliqués'=> $transaction->commissions + $transaction->taxes,
                    'date_operation' => $transaction->date_operation,
                    'caissier'       => auth()->user()->name ?? 'Système',
                    'agence_code'    => $transaction->code_agence
                ]
            ]);

        } catch (Exception $e) {
            // Capture des erreurs métier (Compte bloqué, Provision, Jour fermé, etc.)
            return response()->json([
                'success' => false, 
                'message' => "Échec de l'opération : " . $e->getMessage()
            ], 400);
        }
    }
}
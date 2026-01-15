<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class RetraitController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'compte_id'             => 'required|exists:comptes,id',
        'montant_brut'          => 'required|numeric|min:1',
        //'pc_caisse_id'          => 'required|exists:plan_comptable,id',
        //'pc_client_id'          => 'required|exists:plan_comptable,id',
        'billetage'             => 'required|array',
       // 'net_a_percevoir_payer' => 'required|numeric',
        
        // On utilise uniquement l'objet tiers pour l'identité du porteur
        'tiers.nom_complet'     => 'required|string|max:255',
        'tiers.type_piece'      => 'required|string',
        'tiers.numero_piece'    => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

 try {
        $resultat = $this->caisseService->traiterOperation('RETRAIT', $request->all(), $request->billetage);

        // --- AJOUT : Vérifier si c'est une demande de validation ---
        if (is_array($resultat) && isset($resultat['requires_validation'])) {
            return response()->json($resultat, 202); // 202 = Accepted (en attente)
        }

        // --- Sinon, on traite comme une transaction réussie (Objet) ---
        return response()->json([
            'success' => true,
            'message' => 'Retrait effectué avec succès',
            'data' => [
                'reference' => $resultat->reference_unique,
                'montant'   => $resultat->montant_brut,
                'guichet'   => $resultat->code_guichet
            ]
        ]);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
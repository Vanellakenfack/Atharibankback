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
        'net_a_percevoir_payer' => 'required|numeric',
        
        // On utilise uniquement l'objet tiers pour l'identité du porteur
        'tiers.nom_complet'     => 'required|string|max:255',
        'tiers.type_piece'      => 'required|string',
        'tiers.numero_piece'    => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    try {
        // On passe directement le tableau à traiterOperation
        $transaction = $this->caisseService->traiterOperation('RETRAIT', $request->all(), $request->billetage);

        return response()->json([
            'success' => true,
            'message' => 'Retrait effectué avec succès',
            'data' => [
                'reference' => $transaction->reference_unique,
                'montant'   => $transaction->montant_brut,
                'guichet'   => $transaction->code_guichet
            ]
        ]);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}
}
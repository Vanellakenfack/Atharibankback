<?php
namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CaisseOperationController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    public function store(Request $request)
    {
        // 1. Validation rigoureuse
        $validator = Validator::make($request->all(), [
            'type_flux'          => 'required|in:VERSEMENT,RETRAIT',
            'type_versement'     => 'required|in:ESPECE,ORANGE_MONEY,MOBILE_MONEY',
            'montant_brut'       => 'required|numeric|min:50',
            'compte_id'          => 'nullable|exists:comptes,id', 
            'commissions'        => 'nullable|numeric|min:0',
            'reference_externe'  => 'nullable|string', // Obligatoire si digital
            'telephone_client'   => 'nullable|string', // Obligatoire si digital
            'billets'            => 'nullable|array',  // Obligatoire si cash manipulé
        ]);

        // Validation logique pour le digital
        $validator->after(function ($validator) use ($request) {
            if (in_array($request->type_versement, ['ORANGE_MONEY', 'MOBILE_MONEY'])) {
                if (empty($request->reference_externe)) {
                    $validator->errors()->add('reference_externe', 'La référence de l\'opérateur est obligatoire pour le digital.');
                }
                if (empty($request->telephone_client)) {
                    $validator->errors()->add('telephone_client', 'Le numéro de téléphone client est obligatoire.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 2. Appel du service (Logique des 8 chapitres et argent à part)
            $transaction = $this->caisseService->traiterOperation(
                $request->type_flux,
                $request->all(),
                $request->input('billets', [])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction validée avec succès',
                'reference_bancaire' => $transaction->reference_unique,
                'details' => [
                    'montant' => $transaction->montant_brut,
                    'mode' => $transaction->type_versement,
                    'client' => $request->telephone_client ?? 'Client Banque'
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
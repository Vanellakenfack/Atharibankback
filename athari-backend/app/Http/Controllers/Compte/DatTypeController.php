<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Models\Compte\DatType;
use App\Services\Compte\DATService;
use App\Http\Requests\Compte\OuvrirContratRequest; // Import de ta nouvelle request
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;


class DatTypeController extends Controller
{
    protected $datService;

    public function __construct(DATService $datService)
    {
        $this->datService = $datService;
    }

    /**
     * Liste les produits (9 mois, 15 mois, etc.)
     */
         /**
     * Liste les produits (9 mois, 15 mois, etc.)
     * CORRIGÉ : Ajout du Eager Loading avec with()
     */
            public function index(): JsonResponse
            {
                try {
                    // Utilisation de with() pour charger les relations définies dans le modèle
                    $types = DatType::with(['compteInteret', 'comptePenalite'])
                        ->get();

                    return response()->json([
                        'statut' => 'success',
                        'donnees' => $types
                    ]);
                } catch (Exception $e) {
                    return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 500);
                }
            }

    /**
     * Ouvre un contrat en utilisant la validation OuvrirContratRequest
     */
  public function ouvrirContrat(OuvrirContratRequest $request): JsonResponse
{
    try {
        // On appelle le nom exact défini dans le Service
        $contrat = $this->datService->initialiserEtActiver(
            $request->validated('account_id'), 
            $request->validated('dat_type_id'),
            $request->validated('montant'), // Assurez-vous que 'montant' est dans votre Request
            $request->validated('mode_versement', 'CAPITALISATION') 
        );

        return response()->json([
            'statut' => 'success',
            'message' => 'Contrat DAT initialisé et activé avec succès',
            'details' => $contrat
        ], 201);

    } catch (Exception $e) {
        return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 500);
    }
}


public function store(Request $request): JsonResponse
{
    try {
        // Validation simple
        $data = $request->validate([
            'libelle' => 'required|string',
            'taux_interet' => 'required|numeric',
            'taux_penalite' => 'nullable|numeric',
            'duree_mois' => 'required|integer',
            'plan_comptable_interet_id' => 'nullable|exists:plan_comptable,id',
            'plan_comptable_penalite_id' => 'nullable|exists:plan_comptable,id',
            'is_active' => 'boolean'
        ]);

        $type = DatType::create($data);

        return response()->json([
            'statut' => 'success',
            'donnees' => $type
        ], 201);
    } catch (Exception $e) {
        return response()->json([
            'statut' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}
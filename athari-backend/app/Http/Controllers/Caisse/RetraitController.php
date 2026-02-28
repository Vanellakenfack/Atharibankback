<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class RetraitController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
        // Note: Middleware 'check.agence.ouverte' est appliqué au niveau des routes (api.php)
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'compte_id'             => 'required|exists:comptes,id',
        'montant_brut'          => 'required|numeric|min:1',
        'billetage'             => 'required|array',
        'tiers.nom_complet'     => 'required|string|max:255',
        'tiers.type_piece'      => 'required|string',
        'tiers.numero_piece'    => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    //  Transaction atomique : tout ou rien
    try {
    $resultat = DB::transaction(function () use ($request) {
        return $this->caisseService->traiterOperation('RETRAIT', $request->all(), $request->billetage);
    });

    // --- 1. SÉCURITÉ : Si c'est un tableau, on renvoie direct le JSON ---
           // Dans RetraitController.php
               if (is_array($resultat) && data_get($resultat, 'requires_validation') === true) {
                return response()->json([
                    'success' => false,
                    'requires_validation' => true,
                    'demande_id' => data_get($resultat, 'id'),
                    'message' => data_get($resultat, 'message') ?? "Validation requise par un supérieur",
                    'data' => $resultat
                ], 202); // 202 Accepted : La requête est acceptée mais sera traitée plus tard
            }
    // --- 2. SÉCURITÉ : On vérifie que c'est bien un OBJET avant d'utiliser "->" ---
    if (is_object($resultat)) {
        return response()->json([
            'success' => true,
            'message' => 'Retrait effectué avec succès',
            'data' => [
                'reference'         => $resultat->reference_unique ?? 'N/A',
                'montant'           => $resultat->montant_brut ?? 0,
                'guichet'           => $resultat->code_guichet ?? 'N/A',
                'date_comptable'    => $resultat->date_comptable ?? null,
                'jour_comptable_id' => $resultat->jour_comptable_id ?? null,
            ]
        ]);
    }

    // --- 3. CAS IMPRÉVU ---
    throw new Exception("Le service a renvoyé un format de donnée invalide.");

} catch (Exception $e) {
    return response()->json([
        'success' => false, 
        'message' => $e->getMessage(),
        // Ces deux lignes vous diront EXACTEMENT où se trouve l'erreur dans votre code
        'debug_file' => $e->getFile(),
        'debug_line' => $e->getLine(),
        'trace'   => $e->getTraceAsString() // Ceci va nous dire TOUT le chemin de l'erreur
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
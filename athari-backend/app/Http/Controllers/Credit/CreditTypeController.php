<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Credit\CreditType;
use Illuminate\Http\JsonResponse;

class CreditTypeController extends Controller
{
    /**
     * Liste des types de crédit
     */
    public function index(): JsonResponse
    {
        // Récupérer tous les types de crédit triés par credit_characteristics
        $creditTypes = CreditType::orderBy('credit_characteristics', 'asc')->get();

        // Convertir les champs JSON en tableau pour details_supplementaires et chapitre_comptable
        $creditTypes->transform(function ($item) {
            $item->details_supplementaires = $item->details_supplementaires ? json_decode($item->details_supplementaires, true) : null;
            $item->chapitre_comptable = $item->chapitre_comptable ? json_decode($item->chapitre_comptable, true) : null;
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'count'  => $creditTypes->count(),
            'data'   => $creditTypes
        ]);
    }

    /**
     * Détails d’un type de crédit
     */
    public function show(int $id): JsonResponse
    {
        try {
            $type = CreditType::findOrFail($id);

            // Convertir les champs JSON
            $type->details_supplementaires = $type->details_supplementaires ? json_decode($type->details_supplementaires, true) : null;
            $type->chapitre_comptable = $type->chapitre_comptable ? json_decode($type->chapitre_comptable, true) : null;

            return response()->json([
                'status' => 'success',
                'data'   => $type
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => "Type de crédit introuvable pour l'id $id"
            ], 404);
        }
    }
}

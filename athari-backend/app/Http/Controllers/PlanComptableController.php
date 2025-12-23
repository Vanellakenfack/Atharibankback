<?php

namespace App\Http\Controllers;

use App\Models\chapitre\CategorieComptable;
use App\Models\chapitre\PlanComptable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanComptableController extends Controller
{
    /**
     * Récupère toutes les catégories comptables
     *
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        $categories = CategorieComptable::select('id', 'code', 'libelle')
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Récupère les chapitres, optionnellement filtrés par catégorie et/ou terme de recherche
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChapitres(Request $request): JsonResponse
    {
        $request->validate([
            'categorie_id' => 'sometimes|exists:categories_comptables,id',
            'search' => 'sometimes|string|max:255'
        ]);

        $query = PlanComptable::query()
            ->where('est_actif', true)
            ->select('id', 'code', 'libelle', 'categorie_id');

        // Filtrage par catégorie si spécifié
        if ($request->has('categorie_id') && !empty($request->categorie_id)) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtrage par terme de recherche si spécifié
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        $chapitres = $query->with('categorie:id,code,libelle')
                         ->orderBy('categorie_id')
                         ->orderBy('code')
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $chapitres
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\compte\TypeCompte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API pour la gestion des types de comptes
 *
 * Permet de gérer les différents types de comptes bancaires
 * disponibles dans le système (Compte courant, Épargne, DAT, MATA, etc.)
 */
class TypeCompteController extends Controller
{
    /**
     * GET /api/types-comptes
     * Lister tous les types de comptes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = TypeCompte::query();

        // Filtre: Types actifs uniquement (par défaut)
        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->actif(); // Par défaut, seulement les actifs
        }

        // Filtre: Comptes MATA uniquement
        if ($request->has('mata') && filter_var($request->mata, FILTER_VALIDATE_BOOLEAN)) {
            $query->mata();
        }

        // Filtre: Comptes islamiques uniquement
        if ($request->has('islamique') && filter_var($request->islamique, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('est_islamique', true);
        }

        // Filtre: Comptes nécessitant une durée
        if ($request->has('necessite_duree') && filter_var($request->necessite_duree, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('necessite_duree', true);
        }

        // Recherche par code ou libellé
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'code');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination ou tout
        if ($request->has('per_page')) {
            $typesComptes = $query->paginate($request->per_page);
        } else {
            $typesComptes = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $typesComptes,
        ]);
    }

    /**
     * GET /api/types-comptes/{id}
     * Afficher un type de compte spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $typeCompte = TypeCompte::findOrFail($id);

        // Ajouter des informations supplémentaires
        $data = $typeCompte->toArray();

        // Si c'est un compte MATA, inclure les rubriques disponibles
        if ($typeCompte->est_mata) {
            $data['rubriques_disponibles'] = TypeCompte::getRubriquesMata();
        }

        // Si nécessite une durée, inclure les durées disponibles
        if ($typeCompte->necessite_duree) {
            $data['durees_disponibles'] = TypeCompte::getDureesBlocage();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/types-comptes/code/{code}
     * Obtenir un type de compte par son code
     *
     * @param string $code Code à 2 chiffres
     * @return JsonResponse
     */
    public function showByCode(string $code): JsonResponse
    {
        $typeCompte = TypeCompte::where('code', $code)->firstOrFail();

        $data = $typeCompte->toArray();

        if ($typeCompte->est_mata) {
            $data['rubriques_disponibles'] = TypeCompte::getRubriquesMata();
        }

        if ($typeCompte->necessite_duree) {
            $data['durees_disponibles'] = TypeCompte::getDureesBlocage();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/types-comptes
     * Créer un nouveau type de compte (Administration)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|size:2|unique:types_comptes,code',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'est_mata' => 'boolean',
                'necessite_duree' => 'boolean',
                'est_islamique' => 'boolean',
                'actif' => 'boolean',
            ]);

            $typeCompte = TypeCompte::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Type de compte créé avec succès',
                'data' => $typeCompte,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * PUT /api/types-comptes/{id}
     * Mettre à jour un type de compte (Administration)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $typeCompte = TypeCompte::findOrFail($id);

            $validated = $request->validate([
                'code' => 'sometimes|string|size:2|unique:types_comptes,code,' . $id,
                'libelle' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'est_mata' => 'sometimes|boolean',
                'necessite_duree' => 'sometimes|boolean',
                'est_islamique' => 'sometimes|boolean',
                'actif' => 'sometimes|boolean',
            ]);

            $typeCompte->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Type de compte mis à jour avec succès',
                'data' => $typeCompte->fresh(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * DELETE /api/types-comptes/{id}
     * Supprimer un type de compte (Administration)
     *
     * ATTENTION: Vérifier qu'aucun compte n'utilise ce type avant suppression
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $typeCompte = TypeCompte::findOrFail($id);

            // Vérifier si des comptes utilisent ce type
            $nombreComptes = $typeCompte->comptes()->count();

            if ($nombreComptes > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer ce type de compte. {$nombreComptes} compte(s) l'utilisent actuellement.",
                ], 400);
            }

            $typeCompte->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type de compte supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/types-comptes/{id}/toggle-actif
     * Activer/Désactiver un type de compte
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleActif(int $id): JsonResponse
    {
        $typeCompte = TypeCompte::findOrFail($id);

        $typeCompte->actif = !$typeCompte->actif;
        $typeCompte->save();

        $statut = $typeCompte->actif ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Type de compte {$statut} avec succès",
            'data' => $typeCompte,
        ]);
    }

    /**
     * GET /api/types-comptes/rubriques-mata
     * Obtenir toutes les rubriques MATA disponibles
     *
     * @return JsonResponse
     */
    public function getRubriquesMata(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TypeCompte::getRubriquesMata(),
        ]);
    }

    /**
     * GET /api/types-comptes/durees-blocage
     * Obtenir toutes les durées de blocage disponibles
     *
     * @return JsonResponse
     */
    public function getDureesBlocage(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TypeCompte::getDureesBlocage(),
        ]);
    }

    /**
     * GET /api/types-comptes/statistiques
     * Obtenir des statistiques sur les types de comptes
     *
     * @return JsonResponse
     */
    public function statistiques(): JsonResponse
    {
        $stats = [
            'total' => TypeCompte::count(),
            'actifs' => TypeCompte::actif()->count(),
            'inactifs' => TypeCompte::where('actif', false)->count(),
            'mata' => TypeCompte::mata()->count(),
            'islamiques' => TypeCompte::where('est_islamique', true)->count(),
            'avec_duree' => TypeCompte::where('necessite_duree', true)->count(),
            'par_type' => [
                'collecte_journaliere' => TypeCompte::where('libelle', 'like', '%collecte journalière%')->count(),
                'epargne' => TypeCompte::where('libelle', 'like', '%épargne%')->count(),
                'courant' => TypeCompte::where('libelle', 'like', '%courant%')->count(),
                'dat' => TypeCompte::where('libelle', 'like', '%DAT%')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}

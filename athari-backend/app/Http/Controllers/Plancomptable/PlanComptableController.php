<?php
namespace App\Http\Controllers\Plancomptable;

use App\Http\Controllers\Controller;
use App\Models\chapitre\PlanComptable;
use App\Http\Requests\Plancomptable\StorePlanComptableRequest;
use App\Http\Resources\Plancomptable\PlanComptableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanComptableController extends Controller
{
    /**
     * Liste paginée des comptes avec leur catégorie parente.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Pagination avec 50 éléments par page (ajustable)
        $perPage = $request->get('per_page', 50);
        
        $comptes = PlanComptable::with('categorie')
            ->orderBy('code')
            ->paginate($perPage);
        
        return PlanComptableResource::collection($comptes);
    }

    /**
     * Liste légère pour les selects (sans pagination, mais limitée)
     */
    public function options(Request $request): JsonResponse
    {
        // Limiter à 200 résultats max pour les selects
        $search = $request->get('search', '');
        $limit = $request->get('limit', 200);
        
        $query = PlanComptable::select('id', 'code', 'libelle', 'categorie_id')
            ->with('categorie:id,code,libelle') // CORRECTION: libelle au lieu de nom
            ->where('est_actif', true)
            ->orderBy('code');
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('libelle', 'LIKE', "%{$search}%");
            });
        }
        
        $comptes = $query->limit($limit)->get();
        
        return response()->json([
            'data' => $comptes->map(function($compte) {
                return [
                    'id' => $compte->id,
                    'code' => $compte->code,
                    'libelle' => $compte->libelle,
                    'categorie' => $compte->categorie ? [
                        'id' => $compte->categorie->id,
                        'code' => $compte->categorie->code,
                        'libelle' => $compte->categorie->libelle // CORRECTION: libelle au lieu de nom
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Recherche avancée pour autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $limit = $request->get('limit', 50);
        
        if (empty($search)) {
            return response()->json(['data' => []]);
        }
        
        $comptes = PlanComptable::select('id', 'code', 'libelle', 'categorie_id')
            ->with('categorie:id,code,libelle') // CORRECTION: libelle au lieu de nom
            ->where('est_actif', true)
            ->where(function($query) use ($search) {
                $query->where('code', 'LIKE', "%{$search}%")
                      ->orWhere('libelle', 'LIKE', "%{$search}%");
            })
            ->orderBy('code')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'data' => $comptes->map(function($compte) {
                return [
                    'id' => $compte->id,
                    'code' => $compte->code,
                    'libelle' => $compte->libelle,
                    'categorie' => $compte->categorie,
                    'label' => "{$compte->code} - {$compte->libelle}" . 
                               ($compte->categorie ? " ({$compte->categorie->code})" : "")
                ];
            })
        ]);
    }

    /**
     * Enregistre un nouveau chapitre comptable.
     */
    public function store(StorePlanComptableRequest $request): PlanComptableResource
    {
        $compte = PlanComptable::create($request->validated());
        return new PlanComptableResource($compte->load('categorie'));
    }

    /**
     * Met à jour un compte existant.
     */
    public function update(StorePlanComptableRequest $request, $id)
    {
        $planComptable = PlanComptable::find($id);
        
        if (!$planComptable) {
            return response()->json(['error' => 'Compte non trouvé pour l\'ID ' . $id], 404);
        }

        $planComptable->update($request->validated());
        return new PlanComptableResource($planComptable->load('categorie'));
    }

    /**
     * Affiche les détails d'un compte spécifique.
     */
    public function show(PlanComptable $planComptable): PlanComptableResource
    {
        return new PlanComptableResource($planComptable->load('categorie'));
    }

    /**
     * Suppression définitive
     */
    public function destroy(PlanComptable $planComptable): JsonResponse
    {
        $planComptable->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Compte supprimé avec succès.'
        ]);
    }

    /**
     * Désactiver un compte (au lieu de le supprimer).
     */
    public function archive(PlanComptable $planComptable): JsonResponse
    {
        $planComptable->update(['est_actif' => false]);

        return response()->json([
            'message' => 'Le compte a été désactivé avec succès.'
        ]);
    }
}
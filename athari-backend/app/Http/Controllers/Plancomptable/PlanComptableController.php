<?php
namespace App\Http\Controllers\Plancomptable;

use App\Http\Controllers\Controller;
use App\Models\chapitre\PlanComptable;
use App\Http\Requests\Plancomptable\StorePlanComptableRequest;
use App\Http\Resources\Plancomptable\PlanComptableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanComptableController extends Controller
{
    /**
     * Liste tous les comptes avec leur catégorie parente.
     */
    public function index(): AnonymousResourceCollection
    {
        // On utilise 'with' pour éviter le problème N+1 (performance)
        $comptes = PlanComptable::with('categorie')->get();
        
        return PlanComptableResource::collection($comptes);
    }

    /**
     * Enregistre un nouveau chapitre comptable.
     */
    public function store(StorePlanComptableRequest $request): PlanComptableResource
    {
        // Les données sont déjà validées par StorePlanComptableRequest
        $compte = PlanComptable::create($request->validated());

        // On recharge la catégorie pour que la Resource puisse afficher le type_compte
        return new PlanComptableResource($compte->load('categorie'));
    }

    /**
     * Affiche les détails d'un compte spécifique.
     */

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
    
    // Debug: vérifiez si l'objet a des données avant de l'envoyer à la ressource
    // return $planComptable; 

    return new PlanComptableResource($planComptable->load('categorie'));
}

/**
 * Suppression définitive (à utiliser avec prudence)
 */
public function destroy(PlanComptable $planComptable): JsonResponse
{
    $planComptable->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Compte supprimé avec succès.'
    ]);
}
    public function show(PlanComptable $planComptable): PlanComptableResource
    {
        return new PlanComptableResource($planComptable->load('categorie'));
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
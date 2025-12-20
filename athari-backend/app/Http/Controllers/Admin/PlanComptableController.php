<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\chapitre\PlanComptable;
use App\Http\Requests\Admin\StorePlanComptableRequest;
use App\Http\Resources\Admin\PlanComptableResource;
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
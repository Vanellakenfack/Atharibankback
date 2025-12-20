<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TypesCompteResource;
use App\Models\TypesCompte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TypesCompteController extends Controller
{
    /**
     * Liste des types de comptes
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TypesCompte::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return TypesCompteResource::collection(
            $query->orderBy('code')->get()
        );
    }

    /**
     * Affichage d'un type de compte
     */
    public function show(TypesCompte $accountType): TypesCompteResource
    {
        return new TypesCompteResource($accountType);
    }

    /**
     * Création d'un type de compte
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('parametage plan comptable');

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:2', 'unique:account_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:account_types,slug'],
            'category' => ['required', 'in:courant,epargne,mata_boost,collecte,dat,autre'],
            'sub_category' => ['nullable', 'string'],
            'frais_ouverture' => ['numeric', 'min:0'],
            'frais_tenue_compte' => ['numeric', 'min:0'],
            'frais_carnet' => ['numeric', 'min:0'],
            'frais_retrait' => ['numeric', 'min:0'],
            'frais_sms' => ['numeric', 'min:0'],
            'frais_deblocage' => ['numeric', 'min:0'],
            'penalite_retrait_anticipe' => ['numeric', 'min:0', 'max:100'],
            'commission_mensuelle_seuil' => ['nullable', 'numeric', 'min:0'],
            'commission_mensuelle_basse' => ['numeric', 'min:0'],
            'commission_mensuelle_haute' => ['numeric', 'min:0'],
            'minimum_compte' => ['numeric', 'min:0'],
            'remunere' => ['boolean'],
            'taux_interet_annuel' => ['numeric', 'min:0', 'max:100'],
            'est_bloque' => ['boolean'],
            'duree_blocage_mois' => ['nullable', 'integer', 'min:1'],
            'autorise_decouvert' => ['boolean'],
            'periodicite_arrete' => ['in:journalier,mensuel,trimestriel,annuel'],
            'periodicite_extrait' => ['in:journalier,mensuel,trimestriel,annuel'],
        ]);

        $accountType = TypesCompte::create($validated);

        return response()->json([
            'message' => 'Type de compte créé avec succès.',
            'data' => new TypesCompteResource($accountType),
        ], 201);
    }

    /**
     * Mise à jour d'un type de compte
     */
    public function update(Request $request, TypesCompte $accountType): JsonResponse
    {
        $this->authorize('parametage plan comptable');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'frais_ouverture' => ['sometimes', 'numeric', 'min:0'],
            'frais_tenue_compte' => ['sometimes', 'numeric', 'min:0'],
            'frais_carnet' => ['sometimes', 'numeric', 'min:0'],
            'frais_retrait' => ['sometimes', 'numeric', 'min:0'],
            'frais_sms' => ['sometimes', 'numeric', 'min:0'],
            'frais_deblocage' => ['sometimes', 'numeric', 'min:0'],
            'penalite_retrait_anticipe' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'commission_mensuelle_seuil' => ['nullable', 'numeric', 'min:0'],
            'commission_mensuelle_basse' => ['sometimes', 'numeric', 'min:0'],
            'commission_mensuelle_haute' => ['sometimes', 'numeric', 'min:0'],
            'minimum_compte' => ['sometimes', 'numeric', 'min:0'],
            'remunere' => ['sometimes', 'boolean'],
            'taux_interet_annuel' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $accountType->update($validated);

        return response()->json([
            'message' => 'Type de compte mis à jour avec succès.',
            'data' => new TypesCompteResource($accountType->fresh()),
        ]);
    }
}
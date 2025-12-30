<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Models\Compte\DatType;
use App\Services\Compte\DATService;
use App\Http\Requests\Compte\OuvrirContratRequest;
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
     * Liste les produits avec leurs relations comptables
     */
    public function index(): JsonResponse
    {
        try {
            // Ajout de 'chapitre' dans le eager loading si la relation existe dans le modèle
            $types = DatType::with(['compteInteret', 'comptePenalite'])->get();

            return response()->json([
                'statut' => 'success',
                'donnees' => $types
            ]);
        } catch (Exception $e) {
            return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crée un nouveau type de DAT (Configuration admin)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'libelle' => 'required|string|unique:dat_types,libelle',
                'taux_interet' => 'required|numeric|min:0',
                'duree_mois' => 'required|integer|min:1',
                'periodicite_defaut' => 'nullable|in:M,T,S,A,E',
                'plan_comptable_chapitre_id' => 'required|exists:plan_comptable,id',
                'plan_comptable_interet_id' => 'required|exists:plan_comptable,id',
                'plan_comptable_penalite_id' => 'required|exists:plan_comptable,id',
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
            ], 422); // 422 est plus approprié pour les erreurs de validation/logique
        }
    }

    /**
     * Ouvre un contrat pour un client
     */
    public function ouvrirContrat(OuvrirContratRequest $request): JsonResponse
    {
        try {
            $contrat = $this->datService->initialiserEtActiver(
                $request->validated('account_id'), 
                $request->validated('dat_type_id'),
                $request->validated('montant'),
                $request->validated('mode_versement', 'CAPITALISATION') 
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'Contrat DAT initialisé et activé avec succès',
                'donnees' => $contrat
            ], 201);

        } catch (Exception $e) {
            return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour une offre DAT existante
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $type = DatType::findOrFail($id);

            $data = $request->validate([
                'libelle' => 'required|string|unique:dat_types,libelle,' . $id,
                'taux_interet' => 'required|numeric|min:0',
                'taux_penalite' => 'required|numeric|min:0', // Ajouté pour la gestion des ruptures
                'duree_mois' => 'required|integer|min:1',
                'periodicite_defaut' => 'nullable|in:M,T,S,A,E',
                'plan_comptable_chapitre_id' => 'required|exists:plan_comptable,id',
                'plan_comptable_interet_id' => 'required|exists:plan_comptable,id',
                'plan_comptable_penalite_id' => 'required|exists:plan_comptable,id',
                'is_active' => 'boolean'
            ]);

            $type->update($data);

            return response()->json([
                'statut' => 'success',
                'message' => 'Offre DAT mise à jour avec succès',
                'donnees' => $type
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 422);
        }
    }
}
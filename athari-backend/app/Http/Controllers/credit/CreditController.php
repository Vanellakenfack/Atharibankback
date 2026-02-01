<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CreditService;
use App\Models\Credit\DossierCredit;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreditController extends Controller
{
    protected CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Créer un nouveau dossier de crédit
     */
    public function creerDossier(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'compte_id' => 'required|exists:comptes,id',
            'agence_id' => 'required|exists:agences,id',
            'montant_demande' => 'required|numeric|min:0.01',
            'duree_mois' => 'required|integer|min:1',
            'taux_interet' => 'required|numeric|min:0',
            'type_credit' => ['required', Rule::in(['PETIT_CREDIT', 'GROS_CREDIT'])],
            'motif_demande' => 'required|string',
            'analyse_financiere' => 'nullable|string',
            'analyse_juridique' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable',
            'garanties' => 'nullable|array',
            'garanties.*' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure authenticated user exists
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $data = $validator->validated();

        // Ensure JSON columns are arrays (casts on model will encode)
        if (isset($data['documents']) && !is_array($data['documents'])) {
            $data['documents'] = (array) $data['documents'];
        }
        if (isset($data['garanties']) && !is_array($data['garanties'])) {
            $data['garanties'] = (array) $data['garanties'];
        }

        try {
            $dossier = $this->creditService->creerDossier($data);

            return response()->json([
                'success' => true,
                'dossier' => $dossier
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du dossier.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soumettre un dossier pour validation
     */
    public function soumettreDossier(int $id): JsonResponse
    {
        try {
            $dossier = $this->creditService->soumettreDossier($id);
            return response()->json([
                'success' => true,
                'dossier' => $dossier
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Valider un dossier par un niveau (CA, DG, Comité)
     */
    public function validerDossier(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'niveau' => 'required|string',
            'statut' => 'required|in:VALIDE,REJETE',
            'commentaires' => 'nullable|string'
        ]);

        try {
            $validation = $this->creditService->validerDossier($id, $data['niveau'], $data);
            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Transformer un dossier approuvé en crédit
     */
    public function transformerEnCredit(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'montant_accorde' => 'required|numeric|min:1',
        ]);

        try {
            $credit = $this->creditService->transformerEnCredit($id, $data);
            return response()->json([
                'success' => true,
                'credit' => $credit
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Générer un PV pour un dossier
     */
    public function genererPV(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type_pv' => 'required|string',
            'contenu' => 'required|string',
            'participants' => 'nullable|array',
            'date_reunion' => 'required|date',
            'heure_reunion' => 'required',
            'lieu_reunion' => 'nullable|string',
            'decisions' => 'nullable|array',
            'observations' => 'nullable|string',
        ]);

        try {
            $pv = $this->creditService->genererPV($id, $data);
            return response()->json([
                'success' => true,
                'pv' => $pv
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtenir le journal des crédits
     */
    public function journal(Request $request): JsonResponse
    {
        $filtres = $request->only([
            'statut', 'type_credit', 'agence_id', 'date_debut', 'date_fin', 'per_page'
        ]);

        try {
            $journal = $this->creditService->obtenirJournal($filtres);
            return response()->json([
                'success' => true,
                'journal' => $journal
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\AvisCredit; // Importé pour le test de compte
use App\Services\Credit\CreditFlashWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AvisController extends Controller
{
    protected $workflowService;

    public function __construct(CreditFlashWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Ajouter un avis pour une demande de crédit
     */
    public function store(Request $request, int $applicationId): JsonResponse
    {
        // 1. Récupérer la demande
        $creditApplication = CreditApplication::findOrFail($applicationId);
        
        // 2. Identification de l'utilisateur (Priorité au connecté, sinon fallback selon l'étape)
        $user = auth()->user();
        
        if (!$user) {
            // Fallback intelligent pour tes tests selon le niveau envoyé
            $roleTarget = ($request->niveau_avis === 'CHEF_AGENCE') ? "Chef d'Agence (CA)" : "Assistant Comptable (AC)";
            $user = \App\Models\User::role($roleTarget)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'Utilisateur de test introuvable pour le rôle requis.'
            ], 401);
        }

        // 3. Récupérer les niveaux autorisés
        $allowedLevels = array_keys(config('credit_flash_workflow.steps'));

        // 4. Validation
        $validated = $request->validate([
            'opinion' => 'required|in:FAVORABLE,DEFAVORABLE,RESERVE',
            'commentaire' => 'required|string',
            'score_risque' => 'nullable|integer|min:0|max:100',
            'niveau_avis' => ['required', Rule::in($allowedLevels)],
        ]);

        // 5. Vérification des permissions via le WorkflowService
        if (!$this->workflowService->canUserGiveOpinion($creditApplication, $validated['niveau_avis'], $user)) {
            return response()->json([
                'success' => false, 
                'roles_detectes' => $user->getRoleNames(),
                'message' => "Action non autorisée. Statut actuel: {$creditApplication->statut}."
            ], 403);
        }
        if ($validated['niveau_avis'] === 'COMITE_AGENCE') {
    $creditApplication->update([
        'montant' => $request->nouveau_montant ?? $creditApplication->montant,
        'duree' => $request->nouvelle_duree ?? $creditApplication->duree,
        'garantie' => $request->nom_garantie // On enregistre la garantie ici
    ]);
}

        try {
            // --- TEST DE DIAGNOSTIC AVANT CRÉATION ---
            $countBefore = AvisCredit::count();
            Log::info("--- DEBUT PROCEDURE AVIS ---");
            Log::info("Nombre d'avis en base AVANT : " . $countBefore);

            // 6. Création de l'avis
            $avis = $creditApplication->avis()->create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?: 'USER',
                'opinion' => $validated['opinion'],
                'commentaire' => $validated['commentaire'],
                'niveau_avis' => $validated['niveau_avis'],
                'score_risque' => $validated['score_risque'] ?? null,
                'statut' => 'VALIDE',
                'date_avis' => now(),
            ]);

            // --- LOG DE CONFIRMATION DE CRÉATION ---
            Log::info("Avis créé avec l'ID : " . $avis->id);
            Log::info("Nombre d'avis en base APRES création immédiate : " . AvisCredit::count());

            // 7. Mise à jour automatique du statut de la demande
            // C'est ici que le DB::transaction se trouve dans ton Service
            $this->workflowService->updateCreditStatusAfterOpinion($creditApplication, $avis);

            Log::info("Mise à jour du statut terminée avec succès.");
            Log::info("Nombre d'avis final après WorkflowService : " . AvisCredit::count());
            Log::info("--- FIN PROCEDURE AVIS ---");

            return response()->json([
                'success' => true, 
                'message' => 'Avis enregistré et statut mis à jour avec succès.', 
                'debug_id' => $avis->id,
                'data' => $avis
            ]);

        } catch (\Exception $e) {
            Log::error("ERREUR CRITIQUE AvisController@store: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
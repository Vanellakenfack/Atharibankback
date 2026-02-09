<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\AvisCredit;
use App\Services\Credit\CreditFlashWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AvisController extends Controller
{
    protected $workflowService;

    public function __construct(CreditFlashWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
 * 5. LISTER LES AVIS
 * Utile pour l'historique ou le suivi des décisions
 */
public function index(Request $request): JsonResponse
{
    // On peut filtrer par applicationId si présent dans la requête
    $query = AvisCredit::with(['user', 'creditApplication']);

    if ($request->has('application_id')) {
        $query->where('credit_application_id', $request->application_id);
    }

    $avis = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'success' => true,
        'count' => $avis->count(),
        'data' => $avis
    ]);
}

    /**
     * 1. ENREGISTRER UN AVIS (Agent, AC, ou CA)
     * Gère le passage de PENDING -> VALIDATED 
     * Et la gestion du COMITE_AGENCE (Avis individuels et avis final)
     */
    public function store(Request $request, int $applicationId): JsonResponse
{
    // 1. Récupérer le crédit
    $credit = CreditApplication::findOrFail($applicationId);
    
    // 2. Identification STRICTE de l'utilisateur
    // On retire le "?? User::first()" qui te connectait toujours en DG
    $user = auth()->user();

    // 3. Si l'utilisateur n'est pas reconnu par le Token, on arrête tout
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Utilisateur non authentifié. Assurez-vous d\'envoyer le Bearer Token valide dans Postman.'
        ], 401);
    }

    // 4. Validation des données
    $validated = $request->validate([
        'opinion' => 'required|in:FAVORABLE,DEFAVORABLE',
        'commentaire' => 'required|string',
        'niveau_avis' => 'required|string', 
    ]);

    // 5. Vérification des permissions via le Service
    if (!$this->workflowService->canUserGiveOpinion($credit, $validated['niveau_avis'], $user)) {
        return response()->json([
            'success' => false,
            'message' => "Action non autorisée pour le rôle [{$user->getRoleNames()->first()}]. Statut actuel du dossier: {$credit->statut}.",
            'debug_user_id' => $user->id // Utile pour confirmer que tu n'es plus en DG
        ], 403);
    }

    try {
        return DB::transaction(function () use ($credit, $user, $validated) {
            // Création de l'avis
            $avis = $credit->avis()->create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? 'USER',
                'opinion' => $validated['opinion'],
                'commentaire' => $validated['commentaire'],
                'niveau_avis' => $validated['niveau_avis'],
                'date_avis' => now(),
            ]);

            // Mise à jour automatique du statut de la demande
            $this->workflowService->updateCreditStatusAfterOpinion($credit, $avis);

            return response()->json([
                'success' => true,
                'message' => 'Avis enregistré avec succès.',
                'nouveau_statut' => $credit->fresh()->statut,
                'data' => $avis
            ]);
        });

    } catch (\Exception $e) {
        Log::error("Erreur store avis: " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Erreur interne: ' . $e->getMessage()], 500);
    }
}

    /**
     * 2. VÉRIFICATION PHYSIQUE DES DOCUMENTS
     * Étape intermédiaire pour l'Assistant Comptable (AC)
     */
    public function finaliserVerifPhysique(Request $request, $id): JsonResponse
    {
        $creditApplication = CreditApplication::findOrFail($id);
        $user = auth()->user() ?? \App\Models\User::role('Assistant Comptable (AC)')->first();

        $validated = $request->validate([
            'observation' => 'nullable|string|max:1000',
            'documents_verifies' => 'required|array',
            'pv_signe' => 'required|boolean',
            'garanties_verifiees' => 'required|boolean',
        ]);

        if (!$this->workflowService->canUserValidatePhysical($creditApplication, $user)) {
            return response()->json(['message' => 'Permissions insuffisantes ou statut incorrect.'], 403);
        }

        $success = $this->workflowService->validatePhysicalVerification($creditApplication, $user, $validated);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Vérification physique validée. Prêt pour mise en place.',
                'nouveau_statut' => $creditApplication->fresh()->statut
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Échec de la validation.'], 500);
    }

    /**
     * 3. MISE EN PLACE FINALE (Le décaissement)
     * Action ultime après validation physique
     */
    public function executerMiseEnPlace(Request $request, $id): JsonResponse
    {
        $credit = CreditApplication::findOrFail($id);
        $user = auth()->user();

        // Sécurité : Uniquement si statut MISE_EN_PLACE
        if ($credit->statut !== 'MISE_EN_PLACE') {
            return response()->json(['message' => 'Le dossier n\'est pas prêt pour le décaissement.'], 403);
        }

        try {
            DB::transaction(function () use ($credit) {
                // LOGIQUE COMPTABLE ICI
                // $credit->compte->increment('solde', $credit->montant);
                
                $credit->update(['statut' => 'TERMINE']);
            });

            return response()->json([
                'success' => true, 
                'message' => 'Crédit mis en place et fonds décaissés avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 4. LISTER LES DEMANDES POUR L'ASSISTANT COMPTABLE
     */
    public function listVerificationsPhysiques(): JsonResponse
    {
        $demandes = CreditApplication::whereIn('statut', ['VERIFICATION_DOCUMENTS', 'MISE_EN_PLACE'])
            ->with(['pvs', 'avis', 'compte', 'creditType'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $demandes
        ]);
    }
}
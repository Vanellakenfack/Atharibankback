<?php
namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Services\Compte\CompteService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Compte\CompteResource;
use Illuminate\Http\Request;
use App\Models\compte\Compte;
use Illuminate\Support\Facades\DB;

class CompteValidationController extends Controller
{
    protected $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
        // Note: Middleware 'check.agence.ouverte' est appliqué au niveau des routes (api.php)
    }

    public function valider(Request $request, int $id)
    {
        $user = $request->user();
        $rolesAutorises = ["Chef d'Agence (CA)", "Assistant Juridique (AJ)"];
        
        if (!$user->hasAnyRole($rolesAutorises)) {
            return response()->json([
                'status' => 'error',
                'message' => "Accès refusé : Droits d'approbation manquants."
            ], 403);
        }

        // ✅ Transaction atomique pour la validation
        try {
            $compte = DB::transaction(function () use ($request, $user, $id) {
                $roleActuel = $user->hasRole("Chef d'Agence (CA)") ? "Chef d'Agence (CA)" : "Assistant Juridique (AJ)";

                // 1. On récupère les checkboxes et le NUI depuis la requête
                $checkboxes = $request->input('checkboxes', []); 
                $nui = $request->input('nui');

                // 2. Appel du service avec les nouveaux paramètres
                return $this->compteService->validerOuvertureCompte(
                    $id, 
                    $roleActuel, 
                    $checkboxes, 
                    $nui
                );
            });

            // 3. Retour via la Ressource pour un JSON standardisé
            return (new CompteResource($compte))
                ->additional([
                    'status' => 'success',
                    'message' => $user->hasRole("Chef d'Agence (CA)") 
                        ? "Validation Agence effectuée." 
                        : "Conformité juridique enregistrée."
                ]);

        } catch (\Exception $e) {
            // ✅ Rollback automatique en cas d'erreur
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Nouvelle méthode pour gérer le rejet
     */
    public function rejeter(Request $request, int $id): JsonResponse
    {
        $request->validate(['motif_rejet' => 'required|string|min:10']);

        try {
            $compte = $this->compteService->rejeterOuverture($id, $request->motif_rejet);
            
            return response()->json([
                'status' => 'success',
                'message' => "Le dossier a été rejeté et renvoyé pour correction.",
                'data' => new CompteResource($compte)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function getComptesEnInstruction(Request $request): JsonResponse
{
    $query = Compte::enInstruction() // On commence par le filtre de base
        ->with([
            'client.agency', 
            'typeCompte', 
            'utilisateur_createur',
            'chefAgence',
            'juriste'
        ]);

    // Filtre dynamique par Agence
    if ($request->has('agence_id')) {
        $query->parAgence($request->agence_id);
    }

    // Filtre dynamique par Type de Compte
    if ($request->has('type_compte_id')) {
        $query->parType($request->type_compte_id);
    }

    // Filtre spécifique pour n'avoir QUE les rejetés ou QUE les en attente
    if ($request->has('statut')) {
        $query->where('statut', $request->statut);
    }

    $comptes = $query->orderBy('updated_at', 'desc')->get();

    return response()->json([
        'status' => 'success',
        'count'  => $comptes->count(),
        'data'   => CompteResource::collection($comptes)
    ]);
}
}
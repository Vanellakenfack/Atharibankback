<?php

namespace App\Services\Credit;

use App\Models\Credit\CreditApplication;
use App\Models\Credit\AvisCredit;
use App\Models\Credit\CreditPV;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditFlashWorkflowService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('credit_flash_workflow');
    }

    /**
     * Vérifie si l'utilisateur a le droit de donner son avis selon le niveau et le statut actuel.
     */
    public function canUserGiveOpinion(CreditApplication $creditApplication, string $level, User $user): bool
{
    if (!isset($this->config['steps'][$level])) {
        Log::error("Workflow: Niveau d'avis '$level' non trouvé dans la config.");
        return false;
    }

    $stepConfig = $this->config['steps'][$level];
    
    // 1. Nettoyage des rôles pour éviter les erreurs d'espaces ou de majuscules
    $userRoles = array_map(fn($r) => trim(strtoupper($r)), $user->getRoleNames()->toArray());
    $requiredRoles = array_map(fn($r) => trim(strtoupper($r)), $stepConfig['required_roles']);
    
    // Log pour voir ce qui se passe dans storage/logs/laravel.log
    Log::info("Vérification Workflow", [
        'user_id' => $user->id,
        'user_roles' => $userRoles,
        'roles_requis' => $requiredRoles,
        'statut_actuel' => $creditApplication->statut,
        'niveau_demande' => $level
    ]);

    $hasRole = !empty(array_intersect($userRoles, $requiredRoles));
    
    if (!$hasRole) {
        return false;
    }

    $statutActuel = trim($creditApplication->statut);
    $statutRequis = trim($stepConfig['required_status']);

    if ($level === 'COMITE_AGENCE') {
        $autorises = [$statutRequis, 'VALIDATED_BY_ASSISTANT', 'VALIDATED_BY_AGENT', 'EN_ANALYSE'];
        return in_array($statutActuel, $autorises);
    }

    return $statutActuel === $statutRequis;
}

    /**
     * Met à jour le statut après un avis et gère la logique de transition.
     */
    public function updateCreditStatusAfterOpinion(CreditApplication $creditApplication, AvisCredit $avis): void
    {
        DB::transaction(function () use ($creditApplication, $avis) {
            $user = $avis->user;

            // --- PHASE 1 : SOUMIS -> EN_ANALYSE ---
            if ($avis->niveau_avis === 'INITIAL') {
                $avisInitialCount = $creditApplication->avis()->where('niveau_avis', 'INITIAL')->count();
                // Si on a les 2 avis requis (CA et AC), on passe en analyse
                if ($avisInitialCount >= 2 && $creditApplication->statut === 'SOUMIS') {
                    $creditApplication->update(['statut' => 'EN_ANALYSE']);
                }
            }

            // --- PHASE 2 : COMITÉ (EN_ANALYSE -> APPROUVE/REJETE) ---
            if ($avis->niveau_avis === 'COMITE_AGENCE') {
                if ($user->hasRole("Chef d'Agence (CA)")) {
                    if ($avis->opinion === 'FAVORABLE') {
                        // Le CA a le dernier mot : passage à APPROUVE (statut de ta migration)
                        $creditApplication->update(['statut' => 'APPROUVE']);
                        $this->generatePV($creditApplication, 'APPROUVE');
                    } else {
                        $creditApplication->update(['statut' => 'REJETE']);
                    }
                } else {
                    // Pour l'Assistant ou l'Agent, on marque juste leur passage pour ne pas bloquer le statut
                    $nouveauStatut = $user->hasRole("Assistant Comptable (AC)") 
                        ? 'VALIDATED_BY_ASSISTANT' 
                        : 'VALIDATED_BY_AGENT';
                    
                    $creditApplication->update(['statut' => $nouveauStatut]);
                }
            }
        });
    }

    /**
     * Finalise la vérification physique pour passer au décaissement.
     */
    public function validatePhysicalVerification(CreditApplication $creditApplication, User $user, array $data = []): bool
    {
        // Seul l'Assistant peut valider quand c'est APPROUVE
        if (!$user->hasRole('Assistant Comptable (AC)') || $creditApplication->statut !== 'APPROUVE') {
            return false;
        }

        return DB::transaction(function () use ($creditApplication, $data) {
            $creditApplication->update([
                'statut' => 'MIS_EN_PLACE', // Statut final de ta migration
                'observation' => $data['observation'] ?? $creditApplication->observation
            ]);
            return true;
        });
    }

    /**
     * Génère le PV de crédit.
     */
    protected function generatePV($creditApplication, $statutFinal = 'GENERE') 
    {
        $tousLesAvis = $creditApplication->avis()->with('user')->get();
        $historiqueAvis = $tousLesAvis->map(fn($a) => [
            'role' => $a->role,
            'opinion' => $a->opinion,
            'user' => $a->user->name ?? 'Anonyme',
            'date' => $a->created_at->format('d/m/Y')
        ])->toJson();

        return CreditPV::create([
            'credit_application_id' => $creditApplication->id,
            'numero_pv' => 'PV-' . date('Ymd') . '-' . $creditApplication->id,
            'date_pv' => now(),
            'montant_approuvee' => $creditApplication->montant,
            'duree_approuvee' => $creditApplication->duree,
            'resume_decision' => "Approuvé par le comité d'agence.",
            'details_avis_membres' => $historiqueAvis,
            'genere_par' => auth()->id() ?? ($tousLesAvis->last()->user_id ?? null),
            'statut' => $statutFinal,
        ]);
    }
}
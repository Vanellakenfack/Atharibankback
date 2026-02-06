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
     * DÉTERMINER L'ÉTAPE ATTENDUE
     */
    public function getExpectedLevel(CreditApplication $creditApplication): ?string
    {
        $statut = trim($creditApplication->statut);

        switch ($statut) {
            case 'SOUMIS':
                return 'CHEF_AGENCE';
            case 'CA_VALIDE':
                return 'ASSISTANT_COMPTABLE';
            case 'ASSISTANT_COMPTABLE_VALIDE':
                return 'COMITE_AGENCE';
            case 'APPROUVE':
                return 'MISE_EN_PLACE';
            default:
                return null;
        }
    }

    /**
     * VÉRIFIER SI L'UTILISATEUR PEUT DONNER SON AVIS
     */
    public function canUserGiveOpinion(CreditApplication $creditApplication, string $level, User $user): bool
    {
        if (!isset($this->config['steps'][$level])) {
            Log::error("Workflow : Niveau [$level] inexistant dans la config.");
            return false;
        }

        $stepConfig = $this->config['steps'][$level];

        $userRoles = array_map(fn($role) => trim(strtoupper($role)), $user->getRoleNames()->toArray());
        $requiredRoles = array_map(fn($role) => trim(strtoupper($role)), $stepConfig['required_roles']);
        
        $hasRole = !empty(array_intersect($userRoles, $requiredRoles));

        if (!$hasRole) {
            Log::warning("Workflow [REFUS ROLE] : User ID {$user->id} n'a pas les droits pour $level.");
            return false;
        }

        $statutActuel = trim($creditApplication->statut);
        $statutRequis = trim($stepConfig['required_status']);

        if ($statutActuel !== $statutRequis) {
            Log::warning("Workflow [REFUS STATUT] : Dossier #{$creditApplication->id}. Actuel: [$statutActuel], Requis: [$statutRequis]");
            return false;
        }

        return true;
    }

    /**
     * METTRE À JOUR LE STATUT APRÈS L'AVIS ET GÉNÉRER LE PV
     */
    public function updateCreditStatusAfterOpinion(CreditApplication $creditApplication, AvisCredit $avis): void
    {
        DB::transaction(function () use ($creditApplication, $avis) {
            $stepConfig = $this->config['steps'][$avis->niveau_avis] ?? null;

            // LOGIQUE SPÉCIFIQUE COMITÉ D'AGENCE
            if ($avis->niveau_avis === 'COMITE_AGENCE') {
                
                // 1. Déterminer le statut final (APPROUVE ou REJETE)
                $nouveauStatut = ($avis->opinion === 'DEFAVORABLE') ? 'REJETE' : 'APPROUVE';

                // 2. GÉNÉRER LE PV SYSTÉMATIQUEMENT (Même si c'est un refus)
                $this->generatePV($creditApplication, $nouveauStatut);

                // 3. Mettre à jour la demande
                $creditApplication->update(['statut' => $nouveauStatut]);
                
                Log::info("Workflow Comité : Dossier #{$creditApplication->id} passé à [$nouveauStatut] avec PV généré.");

            } else {
                // LOGIQUE POUR LES AUTRES ÉTAPES (Chef Agence, Assistant)
                if ($avis->opinion === 'DEFAVORABLE') {
                    $creditApplication->update(['statut' => $stepConfig['reject_status'] ?? 'REJETE']);
                } else {
                    $creditApplication->update(['statut' => $stepConfig['next_status']]);
                }
            }
        });
    }

    /**
     * GÉNÉRATION DU PROCÈS-VERBAL
     */
    protected function generatePV($creditApplication, $statutFinal = 'GENERE') 
    {
        // 1. Récupérer tous les avis (y compris celui en cours de traitement)
        $tousLesAvis = $creditApplication->avis()->with('user')->get();

        // 2. Formater l'historique pour le document PDF
        $historiqueAvis = $tousLesAvis->map(function ($a) {
            return [
                'niveau' => $a->niveau_avis,
                'user' => $a->user->name ?? 'Anonyme',
                'opinion' => $a->opinion,
                'commentaire' => $a->commentaire
            ];
        })->toJson();

        // 3. Déterminer le résumé de décision pour le document
        $resume = ($statutFinal === 'REJETE') 
            ? "Demande de crédit rejetée après délibération du comité d'agence." 
            : "Demande de crédit approuvée après délibération du comité d'agence.";

        // 4. Création de l'enregistrement en base de données
        return CreditPV::create([
            'credit_application_id' => $creditApplication->id,
            'numero_pv' => 'PV-' . date('Ymd') . '-' . str_pad($creditApplication->id, 4, '0', STR_PAD_LEFT),
            'date_pv' => now(),
            'lieu_pv' => 'Siège / Agence',
            'montant_approuvee' => $creditApplication->montant,
            'duree_approuvee' => $creditApplication->duree,
            'nom_garantie' => $creditApplication->garantie ?? 'Non spécifiée',
            'resume_decision' => $resume,
            'details_avis_membres' => $historiqueAvis,
            'genere_par' => auth()->id() ?? $tousLesAvis->last()->user_id,
            'statut' => $statutFinal,
        ]);
    }
}
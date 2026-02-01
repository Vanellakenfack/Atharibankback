<?php

namespace App\Services;

use App\Models\CreditApplication;
use App\Models\CreditApproval;
use Illuminate\Support\Facades\Auth;

class CreditWorkflowService
{
    /**
     * Enregistrer un avis
     */
    public function addApproval(
        CreditApplication $credit,
        string $avis,
        string $role,
        string $niveau,
        ?string $commentaire = null
    ): CreditApproval {
        return CreditApproval::create([
            'credit_application_id' => $credit->id,
            'user_id' => Auth::id(),
            'role' => $role,
            'avis' => $avis,
            'niveau' => $niveau,
            'commentaire' => $commentaire,
        ]);
    }

    /**
     * Passage de statut
     */
    public function updateStatus(CreditApplication $credit, string $newStatus): void
    {
        $credit->update([
            'statut' => $newStatus
        ]);
    }

    /**
     * Vérifier si comité agence est favorable
     */
    public function isAgenceApproved(CreditApplication $credit): bool
    {
        return $credit->approvals()
            ->where('niveau', 'agence')
            ->where('avis', 'favorable')
            ->count() >= 3; // Chef + AAR + Agent
    }

    /**
     * Vérifier validation siège (DG prépondérant)
     */
    public function isSiegeApproved(CreditApplication $credit): bool
    {
        $dgDecision = $credit->approvals()
            ->where('niveau', 'siege')
            ->where('role', 'DG')
            ->latest()
            ->first();

        return $dgDecision && $dgDecision->avis === 'favorable';
    }

    /**
     * Générer code mise en place
     */
    public function generateMiseEnPlaceCode(CreditApplication $credit): string
    {
        return 'MP-' . now()->format('Ymd') . '-' . $credit->id;
    }
}

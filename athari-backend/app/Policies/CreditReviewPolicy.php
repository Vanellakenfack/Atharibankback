<?php

namespace App\Policies;

use App\Models\CreditReview;
use App\Models\User;
use App\Models\CreditApplication;

class CreditReviewPolicy
{
    /**
     * Détermine si l'utilisateur peut voter sur ce dossier.
     */
    public function vote(User $user, CreditApplication $application): bool
    {
        // 1. Liste des rôles autorisés à voter en comité
        $rolesAutorises = ['chef_agence', 'assistant_comptable', 'agent_credit'];

        // 2. L'utilisateur doit avoir un de ces rôles
        if (!in_array($user->role, $rolesAutorises)) {
            return false;
        }

        // 3. On ne peut voter que si le dossier est "EN_COMITE"
        return $application->statut === 'EN_COMITE';
    }
}
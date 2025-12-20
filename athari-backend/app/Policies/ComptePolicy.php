<?php
// app/Policies/AccountPolicy.php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComptePolicy
{
    use HandlesAuthorization;

    /**
     * Accès DG et Admin à tout
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['DG', 'Admin'])) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'ouvrir compte',
            'consulter logs',
            'gestion des clients',
        ]);
    }

    public function view(User $user, Compte $account): bool
    {
        // Vérifier si l'utilisateur appartient à la même agence
        if ($user->agency_id && $user->agency_id !== $account->agency_id) {
            if (!$user->hasRole(['Chef Comptable', 'DG'])) {
                return false;
            }
        }

        return $user->hasAnyPermission([
            'ouvrir compte',
            'consulter logs',
            'gestion des clients',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->can('ouvrir compte');
    }

    public function update(User $user, Compte $account): bool
    {
        // Seul le créateur, le CA de l'agence ou le Chef Comptable peuvent modifier
        if ($account->created_by === $user->id) {
            return true;
        }

        if ($user->hasRole('Chef d\'Agence (CA)') && $user->agency_id === $account->agency_id) {
            return true;
        }

        return $user->hasRole('Chef Comptable');
    }

    public function delete(User $user, Compte $account): bool
    {
        return $user->can('cloturer compte');
    }

    public function validateCa(User $user, Compte $account): bool
    {
        return $user->hasRole('Chef d\'Agence (CA)') 
            && $user->agency_id === $account->agency_id
            && $account->statut_validation === 'en_attente';
    }

    public function validateAj(User $user, Compte $account): bool
    {
        return $user->hasRole('Assistant Juridique (AJ)')
            && $account->statut_validation === 'valide_ca';
    }
}
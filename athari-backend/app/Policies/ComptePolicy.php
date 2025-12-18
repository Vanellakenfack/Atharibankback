<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComptePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'ouvrir compte',
            'gestion des clients',
            'consulter logs',
        ]);
    }

    public function view(User $user, Compte $account): bool
    {
        return $user->hasAnyPermission([
            'ouvrir compte',
            'gestion des clients',
            'consulter logs',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ouvrir compte');
    }

    public function update(User $user, Compte $account): bool
    {
        return $user->hasAnyPermission(['ouvrir compte', 'gestion des clients']);
    }

    public function delete(User $user, Compte $account): bool
    {
        return $user->hasPermissionTo('supprimer compte');
    }

    public function validateAsCA(User $user, Compte $account): bool
    {
        return $user->hasRole('Chef d\'Agence (CA)') || 
               $user->hasAnyRole(['DG', 'Admin']);
    }

    public function validateAsAJ(User $user, Compte $account): bool
    {
        return $user->hasRole('Assistant Juridique (AJ)') || 
               $user->hasAnyRole(['DG', 'Admin']);
    }

    public function close(User $user, Compte $account): bool
    {
        return $user->hasPermissionTo('cloturer compte');
    }

    public function block(User $user, Compte $account): bool
    {
        return $user->hasAnyRole([
            'Chef d\'Agence (CA)',
            'Chef Comptable',
            'DG',
            'Admin',
        ]);
    }

    public function unblock(User $user, Compte $account): bool
    {
        return $user->hasAnyRole([
            'Chef d\'Agence (CA)',
            'Chef Comptable',
            'DG',
            'Admin',
        ]);
    }

    public function viewPending(User $user): bool
    {
        return $user->hasAnyRole([
            'Chef d\'Agence (CA)',
            'Assistant Juridique (AJ)',
            'Chef Comptable',
            'DG',
            'Admin',
        ]);
    }
}
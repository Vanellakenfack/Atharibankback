<?php

namespace App\Policies;

use App\Models\Credit\AvisCredit;
use App\Models\Credit\CreditApplication;
use App\Models\User;
use App\Services\Credit\CreditFlashWorkflowService;

class AvisCreditPolicy
{
    protected $workflowService;

    public function __construct(CreditFlashWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Determine whether the user can create an avis for the credit application
     */
    public function create(User $user, CreditApplication $creditApplication, string $level): bool
{
    // Get required roles for this level from config
    $levelsConfig = config('credit_workflow.levels');
    
    if (!isset($levelsConfig[$level])) {
        return false;
    }
    
    $requiredRoles = $levelsConfig[$level]['required_roles'];
    
    // Check if user has any of the required roles
    foreach ($requiredRoles as $role) {
        if ($user->hasRole($role)) {
            return true;
        }
    }
    
    return false;
}

    /**
     * Determine whether the user can view avis for the credit application
     */
    public function view(User $user, CreditApplication $creditApplication): bool
    {
        // Users involved in the workflow can view avis
        $workflowRoles = ['AC', 'CA', 'ASC'];
        return $user->hasAnyRole($workflowRoles);
    }

    /**
     * Determine whether the user can update their own avis
     */
    public function update(User $user, AvisCredit $avis): bool
    {
        return $avis->user_id === $user->id && $avis->statut === 'BROUILLON';
    }

    /**
     * Determine whether the user can delete their own avis
     */
    public function delete(User $user, AvisCredit $avis): bool
    {
        return $avis->user_id === $user->id && $avis->statut === 'BROUILLON';
    }
}

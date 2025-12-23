<?php

namespace App\Services\Frais;

use App\Models\compte\Compte;
use App\Models\frais\FraisCommission;

class ValidationOperationService
{
    /**
     * Valider un retrait
     */
    public function validerRetrait(Compte $compte, $montant)
    {
        $fraisConfig = $compte->typeCompte->fraisCommission;
        
        if (!$fraisConfig) {
            return [
                'valide' => false,
                'message' => 'Configuration des frais non trouvée'
            ];
        }
        
        // Vérifier le minimum en compte
        if ($fraisConfig->minimum_compte_actif && $fraisConfig->minimum_compte > 0) {
            $soldeApresRetrait = $compte->solde - $montant;
            
            if ($soldeApresRetrait < $fraisConfig->minimum_compte) {
                return [
                    'valide' => false,
                    'message' => 'Le retrait ferait descendre le solde en dessous du minimum requis'
                ];
            }
        }
        
        // Vérifier la commission de retrait
        if ($fraisConfig->commission_retrait_actif) {
            $totalADebiter = $montant + $fraisConfig->commission_retrait;
            
            if ($compte->solde < $totalADebiter) {
                return [
                    'valide' => false,
                    'message' => 'Solde insuffisant pour couvrir le retrait et la commission'
                ];
            }
        } else {
            if ($compte->solde < $montant) {
                return [
                    'valide' => false,
                    'message' => 'Solde insuffisant'
                ];
            }
        }
        
        // Pour les comptes bloqués, vérifier l'autorisation de retrait anticipé
        if ($compte->duree_blocage_mois) {
            $calculFraisService = new CalculFraisService();
            $retraitAutorise = $calculFraisService->verifierRetraitAnticipe($compte);
            
            if (!$retraitAutorise) {
                return [
                    'valide' => false,
                    'message' => 'Retrait non autorisé avant l\'échéance'
                ];
            }
            
            if ($fraisConfig->validation_retrait_anticipe && $fraisConfig->retrait_anticipe_autorise) {
                return [
                    'valide' => true,
                    'message' => 'Retrait nécessite validation préalable',
                    'validation_requise' => true
                ];
            }
        }
        
        return [
            'valide' => true,
            'message' => 'Retrait autorisé',
            'commission_retrait' => $fraisConfig->commission_retrait_actif ? $fraisConfig->commission_retrait : 0
        ];
    }
    
    /**
     * Valider l'ouverture d'un compte
     */
    public function validerOuvertureCompte($typeCompteId, $montantInitial)
    {
        $fraisConfig = FraisCommission::where('type_compte_id', $typeCompteId)->first();
        
        if (!$fraisConfig) {
            return [
                'valide' => true,
                'frais_ouverture' => 0
            ];
        }
        
        $fraisOuverture = 0;
        
        if ($fraisConfig->frais_ouverture_actif) {
            $fraisOuverture = $fraisConfig->frais_ouverture;
        }
        
        // Vérifier le dépôt minimum à l'ouverture
        if ($fraisConfig->minimum_compte_actif && $montantInitial < $fraisConfig->minimum_compte) {
            return [
                'valide' => false,
                'message' => 'Le dépôt initial est inférieur au minimum requis'
            ];
        }
        
        return [
            'valide' => true,
            'frais_ouverture' => $fraisOuverture,
            'solde_apres_frais' => $montantInitial - $fraisOuverture
        ];
    }
}
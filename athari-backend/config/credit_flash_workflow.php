<?php

return [
    'steps' => [
        'CHEF_AGENCE' => [
            'required_roles' => ["Chef d'Agence (CA)"], // Corrigé : CA pour l'étape CA
            'required_status' => 'SOUMIS',
            'next_status' => 'CA_VALIDE', 
            'reject_status' => 'REJETE',
        ],
        'ASSISTANT_COMPTABLE' => [
            'required_roles' => ['Assistant Comptable (AC)'],
            'required_status' => 'CA_VALIDE', 
            'next_status' => 'ASSISTANT_COMPTABLE_VALIDE',
            'reject_status' => 'REJETE',
        ],
        'COMITE_AGENCE' => [
            'required_roles' => ["Chef d'Agence (CA)", "Agent de Crédit (AC)", "Assistant Comptable (AC)"],
            'required_status' => 'ASSISTANT_COMPTABLE_VALIDE',
            'next_status' => 'APPROUVE',
            'reject_status' => 'REJETE',
        ],
        'MISE_EN_PLACE' => [
            'required_roles' => ['Assistant Comptable (AC)'],
            'required_status' => 'APPROUVE',
            'next_status' => 'MIS_EN_PLACE',
        ],
    ],
    'thresholds' => [
        'gros_montant' => 500000,
    ],
    'rules' => [
        'auto_pv_generation' => true,
        'require_all_committee_opinions' => true,
    ],
];
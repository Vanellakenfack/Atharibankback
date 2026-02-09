<?php

return [
    'steps' => [
        'INITIAL' => [
            'required_roles' => ["Chef d'Agence (CA)", "Assistant Comptable (AC)"],
            'required_status' => 'SOUMIS', // Statut par défaut de ta migration
            'next_status' => 'EN_ANALYSE', 
            'reject_status' => 'REJETE',
        ],
        'COMITE_AGENCE' => [
            'required_roles' => ["Chef d'Agence (CA)", "Agent de Crédit (AC)", "Assistant Comptable (AC)"],
            'required_status' => 'EN_ANALYSE',
            'next_status' => 'APPROUVE',
            'reject_status' => 'REJETE',
        ],
        'MISE_EN_PLACE' => [
            'required_roles' => ['Assistant Comptable (AC)'],
            'required_status' => 'APPROUVE',
            'next_status' => 'MIS_EN_PLACE',
        ],
    ],
    'rules' => [
        'auto_pv_generation' => true,
        'chef_agence_final_decision' => true,
    ],
];
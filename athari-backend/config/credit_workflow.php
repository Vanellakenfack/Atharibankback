<?php

return [
    'steps' => [
        'INITIAL' => [
            'required_roles' => ["Chef d'Agence (CA)", "Assistant Comptable (AC)"],
            'required_status' => 'PENDING',
            'next_status' => 'VALIDATED', 
            'reject_status' => 'REJETE',
        ],
        'COMITE_AGENCE' => [
            'required_roles' => ["Chef d'Agence (CA)", "Agent de Crédit (AC)", "Assistant Comptable (AC)"],
            'required_status' => 'VALIDATED',
            'next_status' => 'EN_COMITE',
            'reject_status' => 'REJETE',
        ],
        'MISE_EN_PLACE' => [
            'required_roles' => ['Assistant Comptable (AC)'],
            'required_status' => 'MISE_EN_PLACE',
            'next_status' => 'TERMINE',
        ],
    ],
    'rules' => [
        'auto_pv_generation' => true,
        'chef_agence_final_decision' => true,
    ],
];
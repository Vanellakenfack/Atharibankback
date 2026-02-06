<?php

return [
    'levels' => [
        'CA' => [
            'name' => 'Chef d\'Agence',
            'order' => 1,
            'required_roles' => ['Chef d\'Agence (CA)'],
            'description' => 'Première validation par le Chef d\'Agence'
        ],
        'ASC' => [
            'name' => 'Assistant Comptable',
            'order' => 2,
            'required_roles' => ['Assistant Comptable (AC)'],
            'description' => 'Deuxième validation par l\'Assistant Comptable'
        ],
        'COMITE' => [
            'name' => 'Comité d\'Agence',
            'order' => 3,
            'required_roles' => ['Chef Comptable', 'Chef d\'Agence (CA)', 'Assistant Comptable (AC)'],
            'description' => 'Validation finale par le Comité d\'Agence'
        ]
    ],

    'opinions' => [
        'FAVORABLE' => 'Favorable',
        'DEFAVORABLE' => 'Défavorable',
        'RESERVE' => 'Réserve'
    ],

    'rules' => [
        'no_skip_levels' => true,
        'one_opinion_per_user_per_level' => true,
        'defavorable_rejects_immediately' => true,
        'committee_requires_all_members' => true,
    ]
];
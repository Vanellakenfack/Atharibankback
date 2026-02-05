<?php
// database/seeders/GestionnaireSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gestionnaire;
use Illuminate\Support\Facades\Storage;

class GestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $gestionnaires = [
            [
                'gestionnaire_code' => 'G001',
                'gestionnaire_nom' => 'Mahamat',
                'gestionnaire_prenom' => 'Idris',
                'telephone' => '677889900',
                'email' => 'mahamat.idris@atharibank.com',
                'agence_id' => 3,
                'ville' => 'N\'Djaména',
                'quartier' => 'Moursal',
                'etat' => 'present'
            ],
            [
                'gestionnaire_code' => 'G002',
                'gestionnaire_nom' => 'Steve',
                'gestionnaire_prenom' => 'Stevo',
                'telephone' => '699887766',
                'email' => 'steve.stevo@atharibank.com',
                'agence_id' => 3,
                'ville' => 'N\'Djaména',
                'quartier' => 'Chagoua',
                'etat' => 'present'
            ],
            [
                'gestionnaire_code' => 'G003',
                'gestionnaire_nom' => 'Louis',
                'gestionnaire_prenom' => 'Martin',
                'telephone' => '655443322',
                'email' => 'louis.martin@atharibank.com',
                'agence_id' => 3,
                'ville' => 'N\'Djaména',
                'quartier' => 'Farcha',
                'etat' => 'present'
            ],
            [
                'gestionnaire_code' => 'G004',
                'gestionnaire_nom' => 'Baba',
                'gestionnaire_prenom' => 'Sylmain',
                'telephone' => '690112233',
                'email' => 'baba.sylmain@atharibank.com',
                'agence_id' => 5,
                'ville' => 'Moundou',
                'quartier' => 'Centre-ville',
                'etat' => 'present'
            ],
            [
                'gestionnaire_code' => 'G005',
                'gestionnaire_nom' => 'Bobo',
                'gestionnaire_prenom' => 'Pierre',
                'telephone' => '691223344',
                'email' => 'bobo.pierre@atharibank.com',
                'agence_id' => 5,
                'ville' => 'Moundou',
                'quartier' => 'Madjingaye',
                'etat' => 'present'
            ],
        ];

        foreach ($gestionnaires as $gestionnaire) {
            Gestionnaire::create($gestionnaire);
        }
    }
}
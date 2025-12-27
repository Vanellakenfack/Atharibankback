<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dat_types')->insert([
            [
                'libelle' => 'DAT 9 MOIS',
                'taux_interet' => 0.0450, // 4,50%
                'duree_mois' => 9,
                'taux_penalite' => 0.10, // 10%
                'code_comptable_interet' => '61200001 INTERET DAT 9 MOIS',
                'code_comptable_penalite' => '720610001 PENALITE DE DEBLOCAGE DAT 9 MOIS',
            ],
            [
                'libelle' => 'DAT 15 MOIS',
                'taux_interet' => 0.0500, // 5% (basé sur le minimum de la plage 5%-5,5%)
                'duree_mois' => 15,
                'taux_penalite' => 0.10,
                'code_comptable_interet' => '61200002 INTERET DAT 15 MOIS',
                'code_comptable_penalite' => '720610002 PENALITE DE DEBLOCAGE DAT 15 MOIS',
            ],
            [
                'libelle' => 'DAT 24 MOIS',
                'taux_interet' => 0.0600, // 6%
                'duree_mois' => 24,
                'taux_penalite' => 0.10,
                'code_comptable_interet' => '61200003 INTERET DAT 24 MOIS',
                'code_comptable_penalite' => '720610003 PENALITE DE DEBLOCAGE DAT 24 MOIS',
            ],
            [
                'libelle' => 'DAT TRÉSO+',
                'taux_interet' => 0.0300, // 3%
                'duree_mois' => 3,
                'taux_penalite' => 0.10,
                'code_comptable_interet' => '61200004 INTERET DAT TRESO+ 3MOIS',
                'code_comptable_penalite' => '720610004 PENALITE DE DEBLOCAGE DAT TRESO+ 3 MOIS',
            ],
            [
                'libelle' => 'DAT SOLIDAIRE',
                'taux_interet' => 0.0400, // 4%
                'duree_mois' => 6,
                'taux_penalite' => 0.10,
                'code_comptable_interet' => '61200005 INTERET DAT SOLIDAIRE 6MOIS',
                'code_comptable_penalite' => '720610005 PENALITE DE DEBLOCAGE DAT SOLIDAIRE 6 MOIS',
            ],
            [
                'libelle' => 'DAT ÉCHELONNÉ',
                'taux_interet' => 0.0300, // 3%
                'duree_mois' => 12,
                'taux_penalite' => 0.10,
                'code_comptable_interet' => '61200006 INTERET DAT ECHELONNE 12 MOIS',
                'code_comptable_penalite' => '720610006 PENALITE DE DEBLOCAGE DAT ECHELONNE',
            ],
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. RECHERCHE DES COMPTES RÉELS DANS VOTRE SQL IMPORTÉ
        
        // On cherche le compte de dépôt (Classe 25)
        $compteChapitre = DB::table('plan_comptable')
            ->where('code', 'like', '25%')
            ->first();

        // On cherche le compte de charge d'intérêts (Classe 6)
        $compteInteret = DB::table('plan_comptable')
            ->where('code', 'like', '612%') // Spécifique aux intérêts DAT dans votre SQL
            ->first();

        // On cherche le compte de pénalités/produits (Classe 721)
        $comptePenalite = DB::table('plan_comptable')
            ->where('code', 'like', '721%')
            ->first();

        // SÉCURITÉ : Vérifier si les comptes existent avant de continuer
        if (!$compteChapitre || !$compteInteret || !$comptePenalite) {
            $this->command->error("Erreur : Les comptes nécessaires (25, 612 ou 721) sont introuvables dans la table plan_comptable.");
            return;
        }

        // 2. DÉFINITION DES OFFRES DAT
        $typesDat = [
            [
                'libelle' => 'DAT 6 MOIS',
                'taux_interet' => 0.0450,
                'duree_mois' => 6,
                'periodicite' => 'E',
            ],
            [
                'libelle' => 'DAT 12 MOIS',
                'taux_interet' => 0.0600,
                'duree_mois' => 12,
                'periodicite' => 'E',
            ],
            [
                'libelle' => 'DAT 24 MOIS',
                'taux_interet' => 0.0750,
                'duree_mois' => 24,
                'periodicite' => 'E',
            ],
            [
                'libelle' => 'DAT ÉPARGNE+',
                'taux_interet' => 0.0350,
                'duree_mois' => 3,
                'periodicite' => 'M', // Mensuel
            ]
        ];

        // 3. INSERTION DANS LA TABLE DAT_TYPES
        foreach ($typesDat as $type) {
            DB::table('dat_types')->updateOrInsert(
                ['libelle' => $type['libelle']], // Si le libellé existe déjà, on met à jour
                [
                    'taux_interet' => $type['taux_interet'],
                    'duree_mois' => $type['duree_mois'],
                    'periodicite_defaut' => $type['periodicite'],
                    'plan_comptable_chapitre_id' => $compteChapitre->id,
                    'plan_comptable_interet_id' => $compteInteret->id,
                    'plan_comptable_penalite_id' => $comptePenalite->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Types de DAT créés avec succès en utilisant votre plan comptable !');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\compte\TypeCompte;

/**
 * Seeder pour les types de comptes
 * Basé sur: NOMENCLATURE DES COMPTES AUDACE VRAI.pdf
 */
class TypesComptesSeeder extends Seeder
{
    public function run(): void
    {
        $typesComptes = [
            // Comptes de collecte journalière (Z1-Z12)
            ['code' => '01', 'libelle' => 'Compte collecte journalière Z1', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '02', 'libelle' => 'Compte collecte journalière Z2', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '03', 'libelle' => 'Compte collecte journalière Z3', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '04', 'libelle' => 'Compte collecte journalière Z4', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '05', 'libelle' => 'Compte collecte journalière Z5', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '06', 'libelle' => 'Compte collecte journalière Z6', 'est_mata' => false, 'necessite_duree' => false],

            // Comptes d'épargne journalière bloquée
            ['code' => '07', 'libelle' => 'Épargne journalière bloquée 3 mois', 'est_mata' => false, 'necessite_duree' => true],
            ['code' => '08', 'libelle' => 'Épargne journalière bloquée 6 mois', 'est_mata' => false, 'necessite_duree' => true],
            ['code' => '09', 'libelle' => 'Épargne journalière bloquée 12 mois', 'est_mata' => false, 'necessite_duree' => true],

            // Comptes courants
            ['code' => '10', 'libelle' => 'Compte courant particulier', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '11', 'libelle' => 'Compte courant entreprise', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '12', 'libelle' => 'Compte épargne participative', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '13', 'libelle' => 'Compte courant association', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '14', 'libelle' => 'Compte courant islamique', 'est_mata' => false, 'necessite_duree' => false, 'est_islamique' => true],

            // Comptes d'épargne
            ['code' => '15', 'libelle' => 'Épargne young', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '16', 'libelle' => 'Épargne classique', 'est_mata' => false, 'necessite_duree' => false],

            // DAT
            ['code' => '17', 'libelle' => 'DAT', 'est_mata' => false, 'necessite_duree' => true],
            ['code' => '18', 'libelle' => 'DAT solidaire', 'est_mata' => false, 'necessite_duree' => true],

            // Autres comptes
            ['code' => '19', 'libelle' => 'Compte salaire', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '20', 'libelle' => 'Compte épargne islamique', 'est_mata' => false, 'necessite_duree' => false, 'est_islamique' => true],
            ['code' => '21', 'libelle' => 'Compte épargne association', 'est_mata' => false, 'necessite_duree' => false],

            // Comptes MATA (avec rubriques)
            ['code' => '22', 'libelle' => 'Compte mata boost bloqué', 'est_mata' => true, 'necessite_duree' => true],
            ['code' => '23', 'libelle' => 'Compte mata boost journalier', 'est_mata' => true, 'necessite_duree' => false],

            // Autres
            ['code' => '24', 'libelle' => 'Compte courant crédit', 'est_mata' => false, 'necessite_duree' => false],

            // Collecte journalière Z7-Z12
            ['code' => '25', 'libelle' => 'Compte collecte journalière Z7', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '26', 'libelle' => 'Compte collecte journalière Z8', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '27', 'libelle' => 'Compte collecte journalière Z9', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '28', 'libelle' => 'Compte collecte journalière Z10', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '29', 'libelle' => 'Compte collecte journalière Z11', 'est_mata' => false, 'necessite_duree' => false],
            ['code' => '30', 'libelle' => 'Compte collecte journalière Z12', 'est_mata' => false, 'necessite_duree' => false],
        ];

        foreach ($typesComptes as $type) {
            TypeCompte::create($type);
        }
    }
}

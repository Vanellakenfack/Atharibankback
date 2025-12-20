<?php
// database/seeders/AccountTypeSeeder.php

namespace Database\Seeders;

use App\Models\TypesCompte;
use Illuminate\Database\Seeder;

class TypesCompteSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Collecte journalière Z1-Z12
            ['code' => '01', 'name' => 'Compte collecte journalière Z1', 'slug' => 'collecte-z1', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '02', 'name' => 'Compte collecte journalière Z2', 'slug' => 'collecte-z2', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '03', 'name' => 'Compte collecte journalière Z3', 'slug' => 'collecte-z3', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '04', 'name' => 'Compte collecte journalière Z4', 'slug' => 'collecte-z4', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '05', 'name' => 'Compte collecte journalière Z5', 'slug' => 'collecte-z5', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '06', 'name' => 'Compte collecte journalière Z6', 'slug' => 'collecte-z6', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            
            // Épargne journalière bloquée
            ['code' => '07', 'name' => 'Épargne journalière bloquée 3 mois', 'slug' => 'epargne-bloquee-3m', 'category' => 'epargne', 'sub_category' => 'bloque_3_mois', 'est_bloque' => true, 'duree_blocage_mois' => 3, 'frais_deblocage' => 1000, 'penalite_retrait_anticipe' => 3],
            ['code' => '08', 'name' => 'Épargne journalière bloquée 6 mois', 'slug' => 'epargne-bloquee-6m', 'category' => 'epargne', 'sub_category' => 'bloque_6_mois', 'est_bloque' => true, 'duree_blocage_mois' => 6, 'frais_deblocage' => 1000, 'penalite_retrait_anticipe' => 3],
            ['code' => '09', 'name' => 'Épargne journalière bloquée 12 mois', 'slug' => 'epargne-bloquee-12m', 'category' => 'epargne', 'sub_category' => 'bloque_12_mois', 'est_bloque' => true, 'duree_blocage_mois' => 12, 'frais_deblocage' => 1000, 'penalite_retrait_anticipe' => 3],
            
            // Comptes courants
            ['code' => '10', 'name' => 'Compte courant particulier', 'slug' => 'courant-particulier', 'category' => 'courant', 'sub_category' => 'particulier', 'frais_ouverture' => 3500, 'frais_tenue_compte' => 2000, 'frais_sms' => 200, 'periodicite_arrete' => 'mensuel'],
            ['code' => '11', 'name' => 'Compte courant entreprise', 'slug' => 'courant-entreprise', 'category' => 'courant', 'sub_category' => 'entreprise', 'frais_ouverture' => 10000, 'frais_tenue_compte' => 5000, 'frais_sms' => 200, 'periodicite_arrete' => 'mensuel'],
            
            // Épargne participative et autres
            ['code' => '12', 'name' => 'Compte épargne participative', 'slug' => 'epargne-participative', 'category' => 'epargne'],
            ['code' => '13', 'name' => 'Compte courant association', 'slug' => 'courant-association', 'category' => 'courant', 'sub_category' => 'association'],
            ['code' => '14', 'name' => 'Compte courant islamique', 'slug' => 'courant-islamique', 'category' => 'courant', 'sub_category' => 'islamique'],
            ['code' => '15', 'name' => 'Épargne young', 'slug' => 'epargne-young', 'category' => 'epargne'],
            ['code' => '16', 'name' => 'Épargne classique', 'slug' => 'epargne-classique', 'category' => 'epargne', 'remunere' => true],
            
            // DAT
            ['code' => '17', 'name' => 'DAT', 'slug' => 'dat', 'category' => 'dat', 'est_bloque' => true, 'remunere' => true],
            ['code' => '18', 'name' => 'DAT solidaire', 'slug' => 'dat-solidaire', 'category' => 'dat', 'est_bloque' => true, 'remunere' => true],
            
            // Autres comptes
            ['code' => '19', 'name' => 'Compte salaire', 'slug' => 'compte-salaire', 'category' => 'courant'],
            ['code' => '20', 'name' => 'Compte épargne islamique', 'slug' => 'epargne-islamique', 'category' => 'epargne', 'sub_category' => 'islamique'],
            ['code' => '21', 'name' => 'Compte épargne association', 'slug' => 'epargne-association', 'category' => 'epargne', 'sub_category' => 'association'],
            
            // MATA BOOST
            ['code' => '22', 'name' => 'Compte mata boost bloqué', 'slug' => 'mata-boost-bloque', 'category' => 'mata_boost', 'sub_category' => 'bloque_3_mois', 'frais_carnet' => 500, 'frais_deblocage' => 1500, 'penalite_retrait_anticipe' => 3, 'est_bloque' => true],
            ['code' => '23', 'name' => 'Compte mata boost journalier', 'slug' => 'mata-boost-journalier', 'category' => 'mata_boost', 'sub_category' => 'a_vue', 'frais_carnet' => 500, 'frais_retrait' => 200, 'frais_sms' => 200, 'commission_mensuelle_seuil' => 50000, 'commission_mensuelle_basse' => 300, 'commission_mensuelle_haute' => 1000],
            
            // Compte crédit
            ['code' => '24', 'name' => 'Compte courant crédit', 'slug' => 'courant-credit', 'category' => 'courant', 'autorise_decouvert' => true],
            
            // Collecte Z7-Z12
            ['code' => '25', 'name' => 'Compte collecte journalière Z7', 'slug' => 'collecte-z7', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '26', 'name' => 'Compte collecte journalière Z8', 'slug' => 'collecte-z8', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '27', 'name' => 'Compte collecte journalière Z9', 'slug' => 'collecte-z9', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '28', 'name' => 'Compte collecte journalière Z10', 'slug' => 'collecte-z10', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '29', 'name' => 'Compte collecte journalière Z11', 'slug' => 'collecte-z11', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
            ['code' => '30', 'name' => 'Compte collecte journalière Z12', 'slug' => 'collecte-z12', 'category' => 'collecte', 'commission_mensuelle_basse' => 1000],
        ];

        foreach ($types as $type) {
            TypesCompte::updateOrCreate(
                ['code' => $type['code']],
                array_merge([
                    'frais_ouverture' => 0,
                    'frais_tenue_compte' => 0,
                    'frais_carnet' => 0,
                    'frais_retrait' => 0,
                    'frais_sms' => 0,
                    'frais_deblocage' => 0,
                    'penalite_retrait_anticipe' => 0,
                    'commission_mensuelle_seuil' => null,
                    'commission_mensuelle_basse' => 0,
                    'commission_mensuelle_haute' => 0,
                    'minimum_compte' => 0,
                    'remunere' => false,
                    'taux_interet_annuel' => 0,
                    'est_bloque' => false,
                    'duree_blocage_mois' => null,
                    'autorise_decouvert' => false,
                    'periodicite_arrete' => 'annuel',
                    'periodicite_extrait' => 'annuel',
                    'is_active' => true,
                ], $type)
            );
        }
    }
}
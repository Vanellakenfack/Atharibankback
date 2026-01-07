<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\compte\TypeCompte;
use Illuminate\Support\Facades\DB;

class TypesComptesSeeder extends Seeder
{
    /**
     * Exécuter le seed de la base de données.
     */
    public function run(): void
    {
        // Désactiver les contraintes de clé étrangère temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        TypeCompte::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Récupérer les IDs des chapitres comptables
        $chapitres = DB::table('plan_comptable')->pluck('id', 'code')->toArray();

        $typesComptes = [
            // COMPTES DE COLLECTE JOURNALIÈRE Z1
            [
                'code' => 1,
                'libelle' => 'Compte collecte journalière Z1',
                'description' => 'Compte de collecte journalière zone 1',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224000'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 01)
                'chapitre_commission_sms_id' => $chapitres['72000024'] ?? null, // COMMISSIONS DE SMS COLLECTE JOUR. Z1
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTES DE COLLECTE JOURNALIÈRE Z2
            [
                'code' => 2,
                'libelle' => 'Compte collecte journalière Z2',
                'description' => 'Compte de collecte journalière zone 2',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224001'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 02)
                'chapitre_commission_sms_id' => $chapitres['72000034'] ?? null, // COMMISSIONS DE SMS COLLECTE JOUR. Z2
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTES DE COLLECTE JOURNALIÈRE Z3
            [
                'code' => 3,
                'libelle' => 'Compte collecte journalière Z3',
                'description' => 'Compte de collecte journalière zone 3',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224002'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 03)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTES DE COLLECTE JOURNALIÈRE Z4
            [
                'code' => 4,
                'libelle' => 'Compte collecte journalière Z4',
                'description' => 'Compte de collecte journalière zone 4',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224003'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 04)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTES DE COLLECTE JOURNALIÈRE Z5
            [
                'code' => 5,
                'libelle' => 'Compte collecte journalière Z5',
                'description' => 'Compte de collecte journalière zone 5',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224004'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 05)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTES DE COLLECTE JOURNALIÈRE Z6
            [
                'code' => 6,
                'libelle' => 'Compte collecte journalière Z6',
                'description' => 'Compte de collecte journalière zone 6',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224005'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 06)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // ÉPARGNE JOURNALIÈRE BLOQUÉE 3 MOIS
            [
                'code' => 7,
                'libelle' => 'Épargne journalière bloquée 3 mois',
                'description' => 'Compte d\'épargne journalière bloquée pour 3 mois',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 3,
                'duree_blocage_max' => 3,
                'taux_interet_annuel' => 2.5,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'journalier',
                'minimum_compte' => 1000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 2.0,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37300000'] ?? null, // COMPTES D'ÉPARGNE JOURNALIERE
                'chapitre_penalite_id' => $chapitres['72062001'] ?? null, // PÉNALITÉ DÉBLOCAGE COLLECTE BLOQUÉ 3 MOIS
                'chapitre_frais_deblocage_id' => $chapitres['72030011'] ?? null, // FRAIS BLOCAGE COLLECTE BLOQUE 3 MOIS
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['46810004'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE BLOQUEE X
            ],
            // ÉPARGNE JOURNALIÈRE BLOQUÉE 6 MOIS
            [
                'code' => 8,
                'libelle' => 'Épargne journalière bloquée 6 mois',
                'description' => 'Compte d\'épargne journalière bloquée pour 6 mois',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 6,
                'duree_blocage_max' => 6,
                'taux_interet_annuel' => 3.0,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'journalier',
                'minimum_compte' => 1000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 2.5,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37300000'] ?? null, // COMPTES D'ÉPARGNE JOURNALIERE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['46810004'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE BLOQUEE X
            ],
            // ÉPARGNE JOURNALIÈRE BLOQUÉE 12 MOIS
            [
                'code' => 9,
                'libelle' => 'Épargne journalière bloquée 12 mois',
                'description' => 'Compte d\'épargne journalière bloquée pour 12 mois',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 12,
                'duree_blocage_max' => 12,
                'taux_interet_annuel' => 4.0,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'journalier',
                'capitalisation_interets' => true,
                'minimum_compte' => 1000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 3.0,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37300000'] ?? null, // COMPTES D'ÉPARGNE JOURNALIERE
                'chapitre_frais_deblocage_id' => $chapitres['72030020'] ?? null, // FRAIS DE BLOCAGE COLLECTE BLOQUÉ 12 MOIS
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['46810004'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE BLOQUEE X
            ],
            // COMPTE COURANT PARTICULIER
            [
                'code' => 10,
                'libelle' => 'Compte courant particulier',
                'description' => 'Compte courant pour particuliers',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'frais_ouverture' => 5000,
                'frais_ouverture_actif' => true, // Selon document: "Supprimé les frais d'ouverture de compte"
                'frais_carnet' => 1000,
                'frais_carnet_actif' => true,
                'frais_perte_carnet' => 2000,
                'frais_perte_actif' => true,
                'frais_renouvellement_carnet' => 500,
                'frais_renouvellement_actif' => true,
                'commission_mensuelle_actif' => true,
                'seuil_commission' => 50000,
                'commission_si_superieur' => 1000,
                'commission_si_inferieur' => 500,
                'commission_sms_actif' => true,
                'commission_sms' => 100,
                'minimum_compte' => 10000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_frais_ouverture_id' => $chapitres['72010001'] ?? null, // FRAIS OUVERTURE C/C PARTICULIER
                'chapitre_frais_carnet_id' => $chapitres['72100001'] ?? null, // FRAIS DÉLIVRANCE CARNET ORDINAIRE
                'chapitre_perte_id' => $chapitres['72100003'] ?? null, // FRAIS RENOUV. CARNET APRÈS PERTE
                'chapitre_renouvellement_id' => $chapitres['72100003'] ?? null, // FRAIS RENOUV. CARNET APRÈS PERTE
                'chapitre_commission_sms_id' => $chapitres['72000002'] ?? null, // COM. TENUE COMPTE COURANT PARTICULIER
                'chapitre_interet_credit_id' => $chapitres['71400011'] ?? null, // INTERET SUR DECOUVERT COMPTE COURANT PARTICULIER
                'chapitre_commission_retrait_id' => $chapitres['72052000'] ?? null, // COMMISSION RETRAIT
                'chapitre_frais_deblocage_id' => $chapitres['71540014'] ?? null, // FRAIS DE MISE EN PLACE DECOUVERT C/C PARTICULIER
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE COURANT ENTREPRISE
            [
                'code' => 11,
                'libelle' => 'Compte courant entreprise',
                'description' => 'Compte courant pour entreprises',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'frais_ouverture' => 10000,
                'frais_ouverture_actif' => true,
                'frais_carnet' => 2000,
                'frais_carnet_actif' => true,
                'frais_perte_carnet' => 3000,
                'frais_perte_actif' => true,
                'commission_mensuelle_actif' => true,
                'seuil_commission' => 100000,
                'commission_si_superieur' => 2000,
                'commission_si_inferieur' => 1000,
                'commission_sms_actif' => true,
                'commission_sms' => 150,
                'minimum_compte' => 50000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_frais_ouverture_id' => $chapitres['72000001'] ?? null, // COM. TENUE COMPTE COURANT ENTREPRISE
                'chapitre_frais_carnet_id' => $chapitres['72100001'] ?? null, // FRAIS DÉLIVRANCE CARNET ORDINAIRE
                'chapitre_commission_sms_id' => $chapitres['72000014'] ?? null, // COMMISSIONS DE SMS C/C ENTREPRISE
                'chapitre_interet_credit_id' => $chapitres['71400021'] ?? null, // INTERET SUR DECOUVERT COMPTE COURANT ENTREPRISE
                'chapitre_frais_deblocage_id' => $chapitres['71540021'] ?? null, // FRAIS DE MISE EN PLACE DECOUVERT C/C ENTREPRISE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE ÉPARGNE PARTICIPATIVE
            [
                'code' => 12,
                'libelle' => 'Compte épargne participative',
                'description' => 'Compte d\'épargne participative',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 2.0,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 5000,
                'minimum_compte_actif' => true,
                'frais_deblocage_actif' => true,
                'frais_deblocage' => 500,
                'penalite_retrait_anticipe' => 2.0,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37321000'] ?? null, // COMPTES D'ÉPARGNE PARTICIPATIVE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_penalite_id' => $chapitres['72064001'] ?? null, // PÉNALITÉ BLOCAGE ÉPARGNE PARTICIPATIVE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE COURANT ASSOCIATION
            [
                'code' => 13,
                'libelle' => 'Compte courant association',
                'description' => 'Compte courant pour associations',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'frais_ouverture' => 3000,
                'frais_ouverture_actif' => true,
                'commission_mensuelle_actif' => true,
                'seuil_commission' => 30000,
                'commission_si_superieur' => 500,
                'commission_si_inferieur' => 300,
                'minimum_compte' => 5000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_frais_carnet_id' => $chapitres['72100001'] ?? null, // FRAIS DÉLIVRANCE CARNET ORDINAIRE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE COURANT ISLAMIQUE
            [
                'code' => 14,
                'libelle' => 'Compte courant islamique',
                'description' => 'Compte courant conforme aux principes islamiques',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'commission_mensuelle_actif' => false, // Pas de commission pour compte islamique
                'minimum_compte' => 10000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_interet_credit_id' => $chapitres['71400061'] ?? null, // INTERET SUR DECOUVERT COMPTE COURANT ISLAMIQUE
                'chapitre_frais_deblocage_id' => $chapitres['71540061'] ?? null, // FRAIS ETUDE SUR DECOUVERT C/C ISLAMIQUE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // ÉPARGNE YOUNG
            [
                'code' => 15,
                'libelle' => 'Épargne young',
                'description' => 'Compte d\'épargne pour jeunes',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 1.5,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 1000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37323000'] ?? null, // COMPTES D'ÉPARGNE YOUNG
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_frais_carnet_id' => $chapitres['72100021'] ?? null, // FRAIS DÉLIVRANCE LIVRET ÉPARGNE CLASSIQUE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // ÉPARGNE CLASSIQUE
            [
                'code' => 16,
                'libelle' => 'Épargne classique',
                'description' => 'Compte d\'épargne classique',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 1.8,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 5000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37324000'] ?? null, // COMPTES D'ÉPARGNE CLASSIQUE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_frais_ouverture_id' => $chapitres['72010002'] ?? null, // FRAIS OUVERTURE EPARGNE CLASSIQUE
                'chapitre_frais_carnet_id' => $chapitres['72100021'] ?? null, // FRAIS DÉLIVRANCE LIVRET ÉPARGNE CLASSIQUE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // DAT (DÉPÔT À TERME)
            [
                'code' => 17,
                'libelle' => 'DAT',
                'description' => 'Dépôt à terme',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 1,
                'duree_blocage_max' => 36,
                'taux_interet_annuel' => 5.0,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'capitalisation_interets' => true,
                'minimum_compte' => 100000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 3.0,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['36100000'] ?? null, // DÉPÔTS À TERME
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_penalite_id' => $chapitres['72061001'] ?? null, // PÉNALITÉ DÉBLOCAGE DAT 9 MOIS
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // DAT SOLIDAIRE
            [
                'code' => 18,
                'libelle' => 'DAT solidaire',
                'description' => 'Dépôt à terme solidaire',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 1,
                'duree_blocage_max' => 36,
                'taux_interet_annuel' => 4.5,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'capitalisation_interets' => true,
                'minimum_compte' => 50000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 2.5,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['36100000'] ?? null, // DÉPÔTS À TERME
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE SALAIRE
            [
                'code' => 19,
                'libelle' => 'Compte salaire',
                'description' => 'Compte pour versement de salaire',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'frais_ouverture' => 0,
                'frais_ouverture_actif' => false,
                'commission_mensuelle_actif' => false,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_frais_carnet_id' => $chapitres['72100001'] ?? null, // FRAIS DÉLIVRANCE CARNET ORDINAIRE
                'chapitre_frais_deblocage_id' => $chapitres['71540044'] ?? null, // FRAIS DE MISE EN PLACE DECOUVERT C/C SALAIRE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE ÉPARGNE ISLAMIQUE
            [
                'code' => 20,
                'libelle' => 'Compte épargne islamique',
                'description' => 'Compte d\'épargne conforme aux principes islamiques',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'interets_actifs' => false, // Pas d'intérêts pour compte islamique
                'minimum_compte' => 5000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37322000'] ?? null, // COMPTES D'ÉPARGNE ISLAMIQUE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE ÉPARGNE ASSOCIATION
            [
                'code' => 21,
                'libelle' => 'Compte épargne association',
                'description' => 'Compte d\'épargne pour associations',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 1.2,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 3000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37320000'] ?? null, // COMPTES D'ÉPARGNE ASSOCIATION
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_frais_carnet_id' => $chapitres['72100021'] ?? null, // FRAIS DÉLIVRANCE LIVRET ÉPARGNE CLASSIQUE
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE MATA BOOST BLOQUÉ
            [
                'code' => 22,
                'libelle' => 'Compte mata boost bloqué',
                'description' => 'Compte Mata Boost bloqué',
                'a_vue' => false,
                'est_mata' => true,
                'necessite_duree' => true,
                'actif' => true,
                'duree_blocage_min' => 3,
                'duree_blocage_max' => 12,
                'taux_interet_annuel' => 6.0,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'journalier',
                'capitalisation_interets' => true,
                'minimum_compte' => 50000,
                'minimum_compte_actif' => true,
                'retrait_anticipe_autorise' => true,
                'validation_retrait_anticipe' => true,
                'penalite_retrait_anticipe' => 4.0,
                'penalite_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37225000'] ?? null, // COMPTE MATA BOOST A VUE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_penalite_id' => $chapitres['72063001'] ?? null, // PÉNALITÉ DÉBLOCAGE MATA 3 MOIS
                'chapitre_frais_deblocage_id' => $chapitres['72030001'] ?? null, // FRAIS BLOCAGE COMPTE MATA 3 MOIS
                'chapitre_commission_retrait_id' => $chapitres['72052000'] ?? null, // COMMISSION RETRAIT MATA BOOST À VUE
                'compte_attente_produits_id' => $chapitres['46810002'] ?? null, // COMPTE COLLECTEUR MATA BOOST JOURNALIER X
            ],
            // COMPTE MATA BOOST JOURNALIER
            [
                'code' => 23,
                'libelle' => 'Compte mata boost journalier',
                'description' => 'Compte Mata Boost journalier',
                'a_vue' => true,
                'est_mata' => true,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 3.5,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'journalier',
                'minimum_compte' => 10000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37225000'] ?? null, // COMPTE MATA BOOST A VUE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_frais_carnet_id' => $chapitres['72100011'] ?? null, // FRAIS DÉLIVRANCE CARNET MATA BOOST À VUE
                'chapitre_commission_retrait_id' => $chapitres['72052000'] ?? null, // COMMISSION RETRAIT MATA BOOST À VUE
                'chapitre_commission_sms_id' => $chapitres['47120002'] ?? null, // PRODUITS A RECEVOIR COMMISSION SMS MATA BOOST
                'compte_attente_produits_id' => $chapitres['46810002'] ?? null, // COMPTE COLLECTEUR MATA BOOST JOURNALIER X
            ],
            // COMPTE COURANT CRÉDIT
            [
                'code' => 24,
                'libelle' => 'Compte courant crédit',
                'description' => 'Compte courant avec facilité de crédit',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'commission_mensuelle_actif' => true,
                'seuil_commission' => 0,
                'commission_si_superieur' => 1500,
                'commission_si_inferieur' => 1500,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['48100000'] ?? null, // DÉPÔTS À VUE DE LA CLIENTÈLE
                'chapitre_frais_ouverture_id' => $chapitres['72010008'] ?? null, // FRAIS OUVERTURE COMPTE COURANT LOAN
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTES DE COLLECTE SUPPLÉMENTAIRES Z7 à Z12
            [
                'code' => 25,
                'libelle' => 'Compte collecte journalière Z7',
                'description' => 'Compte de collecte journalière zone 7',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224006'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 07)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            [
                'code' => 26,
                'libelle' => 'Compte collecte journalière Z8',
                'description' => 'Compte de collecte journalière zone 8',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224007'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 08)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            [
                'code' => 27,
                'libelle' => 'Compte collecte journalière Z9',
                'description' => 'Compte de collecte journalière zone 9',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224008'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 09)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            [
                'code' => 28,
                'libelle' => 'Compte collecte journalière Z10',
                'description' => 'Compte de collecte journalière zone 10',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224009'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 10)
                'chapitre_commission_sms_id' => $chapitres['72000114'] ?? null, // COMMISSIONS DE SMS COLLECTE JOUR. Z10
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            [
                'code' => 29,
                'libelle' => 'Compte collecte journalière Z11',
                'description' => 'Compte de collecte journalière zone 11',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224010'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 11)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            [
                'code' => 30,
                'libelle' => 'Compte collecte journalière Z12',
                'description' => 'Compte de collecte journalière zone 12',
                'a_vue' => true,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'minimum_compte' => 0,
                'minimum_compte_actif' => false,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37224011'] ?? null, // COMPTES COLLECTE JOURNALIÈRE (ZONE 12)
                'compte_attente_produits_id' => $chapitres['46810001'] ?? null, // COMPTE COLLECTEUR COLLECTE JOURNALIERE X
            ],
            // COMPTE ÉPARGNE LOGEMENT
            [
                'code' => 31,
                'libelle' => 'Épargne logement',
                'description' => 'Compte d\'épargne pour projet de logement',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 2.5,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 10000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37325000'] ?? null, // COMPTE EPARGNE LOGEMENT
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'chapitre_frais_carnet_id' => $chapitres['72100052'] ?? null, // FRAIS RENOUVELLEMENT LIVRET ÉPARGNE LOGEMENT
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE ÉPARGNE FAMILY
            [
                'code' => 32,
                'libelle' => 'Épargne family',
                'description' => 'Compte d\'épargne familial',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 2.2,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 5000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37326000'] ?? null, // COMPTE EPARGNE FAMILY
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
            // COMPTE ÉPARGNE GARANTIE
            [
                'code' => 33,
                'libelle' => 'Épargne garantie',
                'description' => 'Compte d\'épargne avec garantie',
                'a_vue' => false,
                'est_mata' => false,
                'necessite_duree' => false,
                'actif' => true,
                'taux_interet_annuel' => 1.8,
                'interets_actifs' => true,
                'frequence_calcul_interet' => 'annuel',
                'minimum_compte' => 10000,
                'minimum_compte_actif' => true,
                // Chapitres comptables
                'chapitre_defaut_id' => $chapitres['37327000'] ?? null, // COMPTE EPARGNE GARANTIE
                'chapitre_interet_credit_id' => $chapitres['70200000'] ?? null, // INTÉRÊTS SUR PLACEMENTS ET DÉPÔTS
                'compte_attente_produits_id' => $chapitres['47120001'] ?? null, // PRODUITS A RECEVOIR COMMISSION MATA BOOST
            ],
        ];

        foreach ($typesComptes as $typeCompte) {
            TypeCompte::create($typeCompte);
        }

        $this->command->info('Seeder TypeCompteSeeder exécuté avec succès !');
        $this->command->info(count($typesComptes) . ' types de comptes créés.');
    }
}

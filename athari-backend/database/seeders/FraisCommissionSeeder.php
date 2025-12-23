<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\frais\FraisCommission;
use App\Models\compte\TypeCompte;

class FraisCommissionSeeder extends Seeder
{
    public function run()
    {
        // Configuration pour MATA BOOST JOURNALIER
        $typeMataJournalier = TypeCompte::where('code', '23')->first();
        if ($typeMataJournalier) {
            FraisCommission::create([
                'type_compte_id' => $typeMataJournalier->id,
                'frais_ouverture' => 500,
                'frais_ouverture_actif' => true,
                'commission_mouvement_actif' => true,
                'seuil_commission_mensuelle' => 50000,
                'commission_mensuelle_elevee' => 1000,
                'commission_mensuelle_basse' => 300,
                'commission_retrait' => 200,
                'commission_retrait_actif' => true,
                'commission_sms' => 200,
                'commission_sms_actif' => true,
                'compte_commission_paiement' => '72100000',
                'compte_produit_commission' => '720510000',
                'compte_attente_produits' => '47120',
                'compte_attente_sms' => '47121',
                'minimum_compte_actif' => true,
                'minimum_compte' => 1500,
                'observations' => 'Compte MATA BOOST JOURNALIER - Frais selon spécifications'
            ]);
        }
        
        // Configuration pour MATA BOOST BLOQUÉ
        $typeMataBloque = TypeCompte::where('code', '22')->first();
        if ($typeMataBloque) {
            FraisCommission::create([
                'type_compte_id' => $typeMataBloque->id,
                'frais_ouverture' => 500,
                'frais_ouverture_actif' => true,
                'frais_deblocage' => 1500,
                'frais_deblocage_actif' => true,
                'penalite_retrait_anticipe' => 3,
                'penalite_actif' => true,
                'retrait_anticipe_autorise' => false,
                'validation_retrait_anticipe' => true,
                'compte_commission_paiement' => '72100000',
                'minimum_compte_actif' => true,
                'minimum_compte' => 1500,
                'duree_blocage_min' => 3,
                'duree_blocage_max' => 12,
                'observations' => 'Compte MATA BOOST BLOQUÉ - Retrait anticipé avec pénalité de 3%'
            ]);
        }
        
        // Configuration pour COMPTE COURANT PARTICULIER
        $typeCourantParticulier = TypeCompte::where('code', '10')->first();
        if ($typeCourantParticulier) {
            FraisCommission::create([
                'type_compte_id' => $typeCourantParticulier->id,
                'frais_ouverture' => 3500,
                'frais_ouverture_actif' => true,
                'frais_tenue_compte' => 2000,
                'frais_tenue_actif' => true,
                'commission_sms' => 200,
                'commission_sms_actif' => true,
                'compte_commission_paiement' => '72100000',
                'compte_produit_commission' => '720510000',
                'observations' => 'Compte Courant Particulier - Frais de tenue mensuels'
            ]);
        }
        
        // Configuration pour COMPTE DE COLLECTE JOURNALIÈRE
        $typeCollecte = TypeCompte::where('code', '01')->first();
        if ($typeCollecte) {
            FraisCommission::create([
                'type_compte_id' => $typeCollecte->id,
                'commission_mouvement' => 1000,
                'commission_mouvement_actif' => true,
                'commission_mouvement_type' => 'fixe',
                'compte_produit_commission' => '720510000',
                'observations' => 'Compte Collecte Journalière - Commission fixe mensuelle'
            ]);
        }
        
        // Configuration pour COMPTE DE COLLECTE BLOQUÉ
        $typeCollecteBloque = TypeCompte::where('code', '07')->first();
        if ($typeCollecteBloque) {
            FraisCommission::create([
                'type_compte_id' => $typeCollecteBloque->id,
                'frais_deblocage' => 1000,
                'frais_deblocage_actif' => true,
                'penalite_retrait_anticipe' => 3,
                'penalite_actif' => true,
                'retrait_anticipe_autorise' => false,
                'validation_retrait_anticipe' => true,
                'minimum_compte_actif' => true,
                'minimum_compte' => 500,
                'duree_blocage_min' => 3,
                'duree_blocage_max' => 12,
                'observations' => 'Compte Collecte Bloqué - Retrait anticipé avec pénalité de 3%'
            ]);
        }
        
        $this->command->info('Configurations de frais créées avec succès!');
    }
}
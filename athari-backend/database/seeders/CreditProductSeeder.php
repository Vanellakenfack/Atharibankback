<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditProduct;
use Illuminate\Support\Facades\DB;

class CreditProductSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si la table plan_comptable existe et a des données
        if (!DB::getSchemaBuilder()->hasTable('plan_comptable')) {
            $this->command->error('Table plan_comptable non trouvée!');
            return;
        }

        $chapitresCount = DB::table('plan_comptable')->count();
        $this->command->info("Nombre de chapitres dans plan_comptable: {$chapitresCount}");

        // Récupération des chapitres comptables avec logging
        $chapitres = DB::table('plan_comptable')->pluck('id', 'code')->toArray();
        
        $this->command->info("Nombre de chapitres disponibles: " . count($chapitres));
        
        // Afficher quelques codes pour vérifier le format
        $sampleCodes = array_slice(array_keys($chapitres), 0, 10);
        $this->command->info("Exemples de codes disponibles: " . implode(', ', $sampleCodes));

        // CORRECTION ICI : Utiliser les codes qui existent vraiment (8 chiffres)
        // Vérifier les chapitres requis - Codes corrigés selon ce qui existe
        $codesRequires = [
            // Pour FLASH_24H - utiliser les codes existants
            '20110000',  // Capital (exemple, vérifier le bon code)
            '71400003',  // Intérêts (existe avec 8 chiffres)
            '71540002',  // Frais étude (existe avec 8 chiffres)
            '71310007',  // Pénalité (existe avec 8 chiffres)
            
            // Pour SOLIDAIRE_BEGINING
            '20210000',  // Capital (exemple)
            '713000005', // Intérêts (existe)
            '715300012', // Frais étude (existe)
            '713100005', // Pénalité (tester sans le dernier chiffre)
            '715300006', // Frais mise en place
            
            // Pour BOOST_BEGINING
            '20310000',  // Capital (exemple)
            '713000004', // Intérêts
            '715300011', // Frais étude
            '713100006', // Pénalité (existe)
            '715300005', // Frais mise en place
        ];
        
        // Créer un mapping des codes corrigés
        $mappingCodes = [];
        foreach ($codesRequires as $code) {
            // Essayer le code tel quel
            if (isset($chapitres[$code])) {
                $mappingCodes[$code] = $chapitres[$code];
                $this->command->info("✓ Chapitre {$code} trouvé avec ID: {$chapitres[$code]}");
            } else {
                // Essayer avec 8 chiffres si le code en a 9
                if (strlen($code) === 9) {
                    $code8 = substr($code, 0, 8);
                    if (isset($chapitres[$code8])) {
                        $mappingCodes[$code] = $chapitres[$code8];
                        $this->command->info("✓ Chapitre {$code} mappé à {$code8} avec ID: {$chapitres[$code8]}");
                    } else {
                        $this->command->warn("✗ Chapitre {$code} (ou {$code8}) non trouvé!");
                        
                        // Chercher des codes similaires
                        $similar = array_filter(array_keys($chapitres), function($c) use ($code) {
                            return strpos($c, substr($code, 0, 6)) === 0;
                        });
                        
                        if (!empty($similar)) {
                            $this->command->info("  Codes similaires trouvés: " . implode(', ', array_slice($similar, 0, 5)));
                        }
                    }
                } else {
                    $this->command->warn("✗ Chapitre {$code} non trouvé!");
                }
            }
        }

        // Grille pour le Flash 24H avec la logique proportionnelle
        $grilleFlash24 = json_encode([
            [
                'palier' => 1,
                'min' => 0,
                'max' => 25000,
                'premier_jour' => 1500,
                'journalier' => 500,
                'penalite_jour' => 1000,
                'frais_etude' => 500,
                'description' => '≤ 25.000 FCFA'
            ],
            [
                'palier' => 2,
                'min' => 25001,
                'max' => 50000,
                'premier_jour' => 2000,
                'journalier' => 500,
                'penalite_jour' => 1500,
                'frais_etude' => 1000,
                'description' => '25.001 - 50.000 FCFA'
            ],
            [
                'palier' => 3,
                'min' => 50001,
                'max' => 250000,
                'premier_jour' => 5000,
                'journalier' => 1000,
                'penalite_jour' => 2000,
                'frais_etude' => 2000,
                'description' => '50.001 - 250.000 FCFA'
            ],
            [
                'palier' => 4,
                'min' => 250001,
                'max' => 500000,
                'premier_jour' => 10000,
                'journalier' => 1000,
                'penalite_jour' => 3000,
                'frais_etude' => 3000,
                'description' => '250.001 - 500.000 FCFA'
            ],
            [
                'palier' => 5,
                'min' => 500001,
                'max' => 2000000,
                'premier_jour' => -1, // -1 indique proportionnel
                'journalier' => 1000,
                'penalite_jour' => -1,
                'frais_etude' => -1,
                'description' => '> 500.000 FCFA (Proportionnel)'
            ]
        ]);

        // Liste des produits avec les codes CORRIGÉS
        $products = [
            // --- CRÉDIT FLASH 24H ---
            [
                'code' => 'FLASH_24H',
                'nom' => 'Crédit Flash 24H',
                'type' => 'credit_flash',
                'description' => 'Crédit express - Décision sous 24h',
                'grille_tarification' => $grilleFlash24,
                'montant_min' => 5000.00,
                'montant_max' => 2000000.00,
                'duree_min' => 1,
                'duree_max' => 14,
                'taux_interet' => 0.00,
                'penalite_retard' => 0.00,
                'frais_etude' => 0.00,
                'frais_mise_en_place' => 0.00,
                'temps_obtention' => 24,
                // CHAPITRES COMPTABLES POUR CRÉDIT FLASH - CODES CORRIGÉS
                'chapitre_capital_id' => $mappingCodes['20110000'] ?? $chapitres['20110000'] ?? null,
                'chapitre_interet_id' => $mappingCodes['71400003'] ?? $chapitres['71400003'] ?? $chapitres['714000002'] ?? null,
                'chapitre_frais_etude_id' => $mappingCodes['71540002'] ?? $chapitres['71540002'] ?? null,
                'chapitre_penalite_id' => $mappingCodes['71310007'] ?? $chapitres['71310007'] ?? null,
                'chapitre_frais_de_mise_en_place' => null,
                'is_active' => true,
                'logique_calcul' => 'flash_nouvelle_logique',
                'formule_calcul' => 'J1 fixe + ((duree-1) × journalier)',
                'exemple_calcul' => '100.000 FCFA/14j: 5.000 + (13×1.000) = 18.000'
            ],

            // --- CRÉDIT SOLIDAIRE BEGINING ---
            [
                'code' => 'SOLIDAIRE_BEGINING',
                'nom' => 'Crédit Solidaire BEGINING',
                'type' => 'credit_solidaire',
                'description' => 'Crédit solidaire pour petits projets',
                'montant_min' => 10000.00,
                'montant_max' => 5000000.00,
                'duree_min' => 3,
                'duree_max' => 36,
                'taux_interet' => 3.00,
                'penalite_retard' => 10.00,
                'frais_etude' => 3.00,
                'frais_mise_en_place' => 0.50,
                'temps_obtention' => 72,
                // CHAPITRES COMPTABLES POUR CRÉDIT SOLIDAIRE - CODES CORRIGÉS
                'chapitre_capital_id' => $mappingCodes['20210000'] ?? $chapitres['20210000'] ?? null,
                'chapitre_interet_id' => $mappingCodes['713000005'] ?? $chapitres['713000005'] ?? null,
                'chapitre_frais_etude_id' => $mappingCodes['715300012'] ?? $chapitres['715300012'] ?? null,
                'chapitre_penalite_id' => $mappingCodes['713100005'] ?? $chapitres['713100005'] ?? null,
                'chapitre_frais_de_mise_en_place' => $mappingCodes['715300006'] ?? $chapitres['715300006'] ?? null,
                'is_active' => true,
                'logique_calcul' => 'taux_fixe',
                'formule_calcul' => 'Montant × Taux × Durée / 12'
            ],

            // --- CRÉDIT BOOST BEGINING ---
            [
                'code' => 'BOOST_BEGINING',
                'nom' => 'Crédit BOOST BEGINING',
                'type' => 'credit_boost',
                'description' => 'Crédit boost développement',
                'montant_min' => 50000.00,
                'montant_max' => 10000000.00,
                'duree_min' => 6,
                'duree_max' => 60,
                'taux_interet' => 3.00,
                'penalite_retard' => 10.00,
                'frais_etude' => 3.00,
                'frais_mise_en_place' => 0.50,
                'temps_obtention' => 72,
                // CHAPITRES COMPTABLES POUR CRÉDIT BOOST - CODES CORRIGÉS
                'chapitre_capital_id' => $mappingCodes['20310000'] ?? $chapitres['20310000'] ?? null,
                'chapitre_interet_id' => $mappingCodes['713000004'] ?? $chapitres['713000004'] ?? null,
                'chapitre_frais_etude_id' => $mappingCodes['715300011'] ?? $chapitres['715300011'] ?? null,
                'chapitre_penalite_id' => $mappingCodes['713100006'] ?? $chapitres['713100006'] ?? null,
                'chapitre_frais_de_mise_en_place' => $mappingCodes['715300005'] ?? $chapitres['715300005'] ?? null,
                'is_active' => true,
                'logique_calcul' => 'taux_fixe'
            ]
        ];

        $this->command->info('=== CRÉATION/MISE À JOUR DES PRODUITS ===');
        
        foreach ($products as $data) {
            $product = CreditProduct::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
            
            // Vérifier les chapitres assignés
            $this->command->info("Produit {$data['code']} traité.");
            
            // Afficher les IDs assignés
            $chaptersInfo = [];
            foreach (['capital', 'interet', 'frais_etude', 'penalite', 'frais_mise_en_place'] as $type) {
                $column = 'chapitre_' . ($type === 'frais_mise_en_place' ? 'frais_de_mise_en_place' : $type) . '_id';
                $id = $data[$column] ?? null;
                if ($id) {
                    $chapitre = DB::table('plan_comptable')->find($id);
                    $chaptersInfo[] = "{$type}: " . ($chapitre ? "{$chapitre->code} - {$chapitre->libelle}" : "ID {$id} (non trouvé)");
                }
            }
            
            if (!empty($chaptersInfo)) {
                $this->command->info("  Chapitres assignés: " . implode(', ', $chaptersInfo));
            } else {
                $this->command->warn("  Aucun chapitre assigné!");
            }
        }

        // Afficher un récapitulatif avec vérification
        $this->command->info('=== RÉCAPITULATIF FINAL DES PRODUITS ===');
        $allProducts = CreditProduct::all();
        
        foreach ($allProducts as $product) {
            $this->command->info("  - {$product->code}: {$product->nom}");
            
            // Charger les relations
            $product->load([
                'chapitreCapital',
                'chapitreInteret',
                'chapitreFraisEtude',
                'chapitrePenalite',
                'chapitreMiseEnPlace'
            ]);
            
            $chaptersFound = [];
            
            foreach ([
                'Capital' => $product->chapitreCapital,
                'Intérêts' => $product->chapitreInteret,
                'Frais étude' => $product->chapitreFraisEtude,
                'Pénalité' => $product->chapitrePenalite,
                'Frais mise en place' => $product->chapitreMiseEnPlace
            ] as $label => $relation) {
                if ($relation) {
                    $chaptersFound[] = "{$label}: {$relation->code}";
                }
            }
            
            if (!empty($chaptersFound)) {
                $this->command->info("    " . implode(', ', $chaptersFound));
            } else {
                $this->command->warn("    ✗ Aucun chapitre comptable configuré");
                
                // Afficher les IDs stockés pour débogage
                $ids = [];
                if ($product->chapitre_capital_id) $ids[] = "Capital ID: {$product->chapitre_capital_id}";
                if ($product->chapitre_interet_id) $ids[] = "Intérêts ID: {$product->chapitre_interet_id}";
                if ($product->chapitre_frais_etude_id) $ids[] = "Frais étude ID: {$product->chapitre_frais_etude_id}";
                if ($product->chapitre_penalite_id) $ids[] = "Pénalité ID: {$product->chapitre_penalite_id}";
                if ($product->chapitre_frais_de_mise_en_place) $ids[] = "Frais mise en place ID: {$product->chapitre_frais_de_mise_en_place}";
                
                if (!empty($ids)) {
                    $this->command->info("    IDs stockés: " . implode(', ', $ids));
                }
            }
        }

        $this->command->info('=== FIN DU SEEDING ===');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si la table plan_comptable existe
        if (!DB::getSchemaBuilder()->hasTable('plan_comptable')) {
            $this->command->error('Table plan_comptable non trouvée!');
            return;
        }

        // Récupération des chapitres comptables
        $chapitres = DB::table('plan_comptable')->pluck('id', 'code')->toArray();
        
        $this->command->info("Nombre de chapitres disponibles: " . count($chapitres));

        // Codes spécifiques pour le Crédit Flash 24H d'après votre tableau
        $codesRequires = [
            // Pour FLASH_24H
            '715400002',  // Frais d'étude (FRAIS ETUDE CREDIT FLASH 24H)
            '714000003',  // Intérêts (INTERET SUR CREDIT FLASH 24H)
            '713100007',  // Pénalité (PENALITE SUR CREDIT FLASH 24H)
            
            // Pour SOLIDAIRE_BEGINING
            '713000005',  // Intérêts
            '715300012',  // Frais étude
            '713100005',  // Pénalité
            '715300006',  // Frais mise en place
        ];
        
        // Créer un mapping des codes
        $mappingCodes = [];
        foreach ($codesRequires as $code) {
            if (isset($chapitres[$code])) {
                $mappingCodes[$code] = $chapitres[$code];
                $this->command->info("✓ Chapitre {$code} trouvé avec ID: {$chapitres[$code]}");
            } else {
                $this->command->warn("✗ Chapitre {$code} non trouvé!");
            }
        }

        // Configuration détaillée pour le Flash 24H
        $detailsFlash24 = json_encode([
            'grille_tarification' => [
                [
                    'palier' => 1,
                    'min' => 0,
                    'max' => 25000,
                    'frais_etude' => 500,
                    'premier_jour' => 1500,
                    'journalier' => 500,
                    'penalite_jour' => 500,
                    'description' => '≤ 25.000 FCFA'
                ],
                [
                    'palier' => 2,
                    'min' => 25001,
                    'max' => 50000,
                    'frais_etude' => 1000,
                    'premier_jour' => 2000,
                    'journalier' => 500,
                    'penalite_jour' => 1000,
                    'description' => '25.001 - 50.000 FCFA'
                ],
                [
                    'palier' => 3,
                    'min' => 50001,
                    'max' => 250000,
                    'frais_etude' => 2000,
                    'premier_jour' => 5000,
                    'journalier' => 1000,
                    'penalite_jour' => 2000,
                    'description' => '50.001 - 250.000 FCFA'
                ],
                [
                    'palier' => 4,
                    'min' => 250001,
                    'max' => 500000,
                    'frais_etude' => 3000,
                    'premier_jour' => 10000,
                    'journalier' => 1000,
                    'penalite_jour' => 3000,
                    'description' => '250.001 - 500.000 FCFA'
                ],
                [
                    'palier' => 5,
                    'min' => 500001,
                    'max' => 2000000,
                    'frais_etude' => 'regle_trois_3000',
                    'premier_jour' => 'regle_trois_10000',
                    'journalier' => 1000,
                    'penalite_jour' => 'regle_trois_3000',
                    'description' => '500.001 - 2.000.000 FCFA (Règle de 3)'
                ]
            ],
            'conditions' => [
                'temps_decision' => 24,
                'duree_max_jours' => 14,
                'montant_max' => 2000000,
                'logique_calcul' => 'frais_fixes_par_palier',
                'formule_interet' => 'premier_jour + (journalier × (duree-1))',
                'formule_penalite' => 'penalite_jour × jours_retard',
                'formule_frais_etude' => 'fixe_selon_palier'
            ],
            'exemples_calcul' => [
                'exemple1' => [
                    'montant' => 20000,
                    'duree' => 7,
                    'frais_etude' => 500,
                    'interet' => '1500 + (500 × 6) = 4500',
                    'total' => 20000 + 500 + 4500 . ' FCFA'
                ],
                'exemple2' => [
                    'montant' => 100000,
                    'duree' => 14,
                    'frais_etude' => 2000,
                    'interet' => '5000 + (1000 × 13) = 18000',
                    'total' => 100000 + 2000 + 18000 . ' FCFA'
                ]
            ]
        ]);

        // Types de crédit à insérer
        $creditTypes = [
            // --- CRÉDIT FLASH 24H ---
            [
                'credit_characteristics' => 'CREDIT FLASH 24H',
                'code' => 'FLASH_24H',
                'description' => 'Crédit express accordé en 24h maximum avec une durée de remboursement ne dépassant pas 14 jours. Frais et intérêts calculés selon une grille par palier.',
                'category' => 'credit_flash',
                'taux_interet' => 0.00, // Taux spécifique dans la grille
                'duree' => 14, // en jours (converti en mois pour la structure: 14/30 ≈ 0.47 mois)
                'montant' => 2000000.00,
                'plan_comptable_id' => $mappingCodes['714000003'] ?? null, // Chapitre intérêts
                'chapitre_comptable' => json_encode([
                    'frais_etude' => $mappingCodes['715400002'] ?? null,
                    'interet' => $mappingCodes['714000003'] ?? null,
                    'penalite' => $mappingCodes['713100007'] ?? null
                ]),
                'frais_dossier' => 0.00, // Géré dans la grille
                'penalite' => 0.00, // Géré dans la grille
                'details_supplementaires' => $detailsFlash24
            ],

            // --- CRÉDIT SOLIDAIRE BEGINING ---
            [
                'credit_characteristics' => 'Crédit solidaire petits projets',
                'code' => 'SOLIDAIRE_BEGINING',
                'description' => 'Crédit solidaire destiné aux petits projets avec taux fixe et frais standardisés.',
                'category' => 'credit_solidaire',
                'taux_interet' => 3.00,
                'duree' => 36, // en mois
                'montant' => 5000000.00,
                'plan_comptable_id' => $mappingCodes['713000005'] ?? null, // Chapitre intérêts principal
                'chapitre_comptable' => json_encode([
                    'frais_etude' => $mappingCodes['715300012'] ?? null,
                    'interet' => $mappingCodes['713000005'] ?? null,
                    'penalite' => $mappingCodes['713100005'] ?? null,
                    'frais_mise_en_place' => $mappingCodes['715300006'] ?? null
                ]),
                'frais_dossier' => 3.00, // Pourcentage
                'penalite' => 10.00, // Pourcentage
                'details_supplementaires' => json_encode([
                    'frais_mise_en_place' => 0.50,
                    'temps_traitement' => 72,
                    'garanties' => 'Solidaire'
                ])
            ],

            // --- CRÉDIT EXPRESS ++ SECTEUR PUBLIC ---
            [
                'credit_characteristics' => 'Crédit express secteur public',
                'code' => 'EXPRESS_PLUS_PUBLIC',
                'description' => 'Crédit express pour les fonctionnaires et agents du secteur public.',
                'category' => 'credit_express',
                'taux_interet' => 3.00,
                'duree' => 60, // en mois
                'montant' => 10000000.00,
                'plan_comptable_id' => null, // À ajuster selon votre plan comptable
                'chapitre_comptable' => json_encode([
                    'frais_etude' => '715300013',
                    'interet' => '712000003',
                    'penalite' => '712100003',
                    'frais_mise_en_place' => '715300007'
                ]),
                'frais_dossier' => 3.00,
                'penalite' => 10.00,
                'details_supplementaires' => json_encode([
                    'frais_mise_en_place' => 0.50,
                    'secteur' => 'Public',
                    'garantie' => 'Prélèvement salarial'
                ])
            ],

            // --- CRÉDIT FONDS DE ROULEMENT PME ---
            [
                'credit_characteristics' => 'Fonds de roulement PME',
                'code' => 'FONDS_ROULEMENT_PME',
                'description' => 'Crédit de fonds de roulement pour les petites et moyennes entreprises.',
                'category' => 'credit_entreprise',
                'taux_interet' => 3.00,
                'duree' => 60,
                'montant' => 50000000.00,
                'plan_comptable_id' => null,
                'chapitre_comptable' => json_encode([
                    'frais_etude' => '715300010',
                    'interet' => '713000003',
                    'penalite' => '713100004',
                    'frais_mise_en_place' => '715300004'
                ]),
                'frais_dossier' => 3.00,
                'penalite' => 10.00,
                'details_supplementaires' => json_encode([
                    'frais_mise_en_place' => 0.50,
                    'type_entreprise' => 'PME',
                    'documentation' => 'Bilan, compte de résultat'
                ])
            ],

            // --- CRÉDIT IMMOBILIER ---
            [
                'credit_characteristics' => 'Crédit immobilier diversifié',
                'code' => 'IMMO_GENERAL',
                'description' => 'Crédit immobilier pour différents types de projets (T, TM, HOUSE, INVEST, BUSINESS).',
                'category' => 'credit_immobilier',
                'taux_interet' => 3.00,
                'duree' => 240, // 20 ans
                'montant' => 500000000.00,
                'plan_comptable_id' => null,
                'chapitre_comptable' => json_encode([
                    'frais_etude' => 'Various (e.g., 715100012)',
                    'interet' => 'Various (e.g., 71110001)',
                    'penalite' => 'Various (e.g., 71120001)',
                    'frais_mise_en_place' => 'Various (e.g., 715100011)'
                ]),
                'frais_dossier' => 3.00,
                'penalite' => 10.00,
                'details_supplementaires' => json_encode([
                    'frais_mise_en_place' => 0.50,
                    'types' => ['T', 'TM', 'HOUSE', 'INVEST', 'BUSINESS'],
                    'garanties' => 'Hypothèque, caution'
                ])
            ]
        ];

        $this->command->info('=== CRÉATION/MISE À JOUR DES TYPES DE CRÉDIT ===');
        
        foreach ($creditTypes as $data) {
            // Vérifier si le code existe déjà
            $exists = DB::table('credit_types')->where('code', $data['code'])->exists();
            
            if ($exists) {
                DB::table('credit_types')->where('code', $data['code'])->update($data);
                $this->command->info("✓ Type de crédit {$data['code']} mis à jour");
            } else {
                DB::table('credit_types')->insert($data);
                $this->command->info("✓ Type de crédit {$data['code']} créé");
            }
            
            // Afficher les détails du Flash 24H
            if ($data['code'] === 'FLASH_24H') {
                $this->command->info("  - Montant max: {$data['montant']} FCFA");
                $this->command->info("  - Durée max: {$data['duree']} jours");
                $this->command->info("  - Temps décision: 24h");
                
                $details = json_decode($data['details_supplementaires'], true);
                $this->command->info("  - Nombre de paliers: " . count($details['grille_tarification']));
            }
        }

        // Afficher un récapitulatif
        $this->command->info('=== RÉCAPITULATIF ===');
        $allTypes = DB::table('credit_types')->get();
        
        foreach ($allTypes as $type) {
            $this->command->info("{$type->code}: {$type->credit_characteristics}");
            $this->command->info("  Montant max: {$type->montant} FCFA | Durée: {$type->duree} mois | Taux: {$type->taux_interet}%");
            
            // Pour le Flash 24H, afficher plus de détails
            if ($type->code === 'FLASH_24H') {
                $details = json_decode($type->details_supplementaires, true);
                $this->command->info("  ⚡ Décision en 24h | Durée max 14 jours");
                $this->command->info("  📊 Calcul par palier (5 paliers)");
                
                // Afficher un exemple de calcul
                if (isset($details['exemples_calcul']['exemple1'])) {
                    $exemple = $details['exemples_calcul']['exemple1'];
                    $this->command->info("  💰 Exemple: {$exemple['montant']}F/7j = Frais:{$exemple['frais_etude']}F + Intérêt:{$exemple['interet']}");
                }
            }
        }

        $this->command->info('=== NOMBRE DE TYPES DE CRÉDIT CRÉÉS : ' . count($allTypes) . ' ===');
    }
}
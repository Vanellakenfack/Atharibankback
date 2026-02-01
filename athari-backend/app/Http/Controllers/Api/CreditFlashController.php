<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditProduct;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\chapitre\PlanComptable;

class CreditFlashController extends Controller
{
    protected $creditService;
    
    public function __construct(CreditService $creditService)
    {
        $this->middleware('auth:sanctum')->except(['index', 'simuler', 'checkProduct', 'grilleTarifaire']);
        $this->creditService = $creditService;
    }

    /**
     * Récupère la configuration du produit Crédit Flash 24H
     */
    private function getFlashProduct()
    {
        return CreditProduct::where('code', 'FLASH_24H')->first();
    }

    /**
     * Retourne les infos de base du produit
     */
    public function index()
    {
        try {
            $product = $this->getFlashProduct();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le produit FLASH_24H n\'existe pas.'
                ], 404);
            }

            // Charger les détails complets des chapitres
            $chapitres = [];
            $chapitreIds = [
                'capital' => $product->chapitre_capital_id,
                'interet' => $product->chapitre_interet_id,
                'frais_etude' => $product->chapitre_frais_etude_id,
                'penalite' => $product->chapitre_penalite_id
            ];
            
            foreach ($chapitreIds as $key => $chapitreId) {
                if ($chapitreId) {
                    $chapter = PlanComptable::with('categorie')->find($chapitreId);
                    if ($chapter) {
                        $chapitres[$key] = [
                            'id' => $chapter->id,
                            'code' => $chapter->code,
                            'libelle' => $chapter->libelle,
                            'nature_solde' => $chapter->nature_solde,
                            'est_actif' => $chapter->est_actif,
                            'code_libelle' => $chapter->code . ' - ' . $chapter->libelle,
                            'categorie' => $chapter->categorie ? [
                                'id' => $chapter->categorie->id,
                                'type_compte' => $chapter->categorie->type_compte,
                                'nom' => $chapter->categorie->nom
                            ] : null
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'nom' => $product->nom,
                    'code' => $product->code,
                    'type' => $product->type,
                    'description' => $product->description,
                    'montant_min' => $product->montant_min ?? 5000,
                    'montant_max' => $product->montant_max ?? 2000000,
                    'duree_min' => $product->duree_min ?? 1,
                    'duree_max' => $product->duree_max ?? 14,
                    'temps_obtention' => $product->temps_obtention ?? '24h',
                    'taux_interet' => $product->taux_interet ?? 0,
                    'grille_tarification' => $product->grille_tarification,
                    'formule_calcul' => $product->formule_calcul,
                    'exemple_calcul' => $product->exemple_calcul,
                    'chapitres_comptables' => $chapitres,
                    'chapitres_ids' => $chapitreIds
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur CreditFlashController@index: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
        }
    }

    /**
     * Simulation de crédit avec la nouvelle logique d'intérêts
     */
    public function simuler(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:5000',
            'duree' => 'integer|min:1|max:14'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $product = $this->getFlashProduct();
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produit introuvable'], 404);
            }

            $montant = (float) $request->montant;
            $duree = $request->duree ?? 14;

            // Vérifier si le montant est dans les limites
            $montantMin = $product->montant_min ?? 5000;
            $montantMax = $product->montant_max ?? 2000000;
            
            if ($montant < $montantMin || $montant > $montantMax) {
                return response()->json([
                    'success' => false,
                    'message' => "Le montant doit être compris entre {$montantMin} et {$montantMax} FCFA"
                ], 422);
            }

            // Utiliser le service pour le calcul
            $simulation = $this->creditService->simulateFlashCredit($montant, $duree);

            return response()->json([
                'success' => true,
                'data' => $simulation
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur CreditFlashController@simuler: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur simulation'], 500);
        }
    }

    /**
     * Création d'une demande de crédit flash
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:5000',
            'client_id' => 'required|exists:clients,id',
            'source_revenus' => 'required|string',
            'revenus_mensuels' => 'required|numeric',
            'duree' => 'integer|min:1|max:14'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $product = $this->getFlashProduct();
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produit FLASH_24H non trouvé'], 404);
            }

            $montant = (float) $request->montant;
            $duree = $request->duree ?? 14;

            // Vérifier les limites de montant
            $montantMin = $product->montant_min ?? 5000;
            $montantMax = $product->montant_max ?? 2000000;
            
            if ($montant < $montantMin || $montant > $montantMax) {
                return response()->json([
                    'success' => false,
                    'message' => "Le montant doit être compris entre {$montantMin} et {$montantMax} FCFA"
                ], 422);
            }

            // Calculer les intérêts avec la nouvelle logique
            $simulation = $this->creditService->simulateFlashCredit($montant, $duree);

            // Simulation de la référence
            $reference = 'CRF-' . now()->format('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            return response()->json([
                'success' => true,
                'message' => 'Demande de crédit flash créée avec succès',
                'data' => [
                    'reference' => $reference,
                    'montant' => $montant,
                    'duree' => $duree,
                    'frais_premier_jour' => $simulation['simulation']['frais_premier_jour'],
                    'frais_journalier' => $simulation['simulation']['frais_journalier'],
                    'frais_jours_suivants' => $simulation['simulation']['frais_jours_restants'],
                    'frais_totaux' => $simulation['simulation']['total_interets'],
                    'frais_etude' => $simulation['frais_etude'],
                    'penalite_par_jour' => $simulation['penalite_par_jour'],
                    'total_avec_frais' => $simulation['total_avec_frais'],
                    'date_echeance' => $simulation['simulation']['date_echeance'],
                    'duree_jours' => $duree,
                    'statut' => 'en_attente',
                    'produit_id' => $product->id,
                    'produit_nom' => $product->nom,
                    'chapitres_comptables' => [
                        'capital' => $product->chapitreCapital?->id,
                        'interet' => $product->chapitreInteret?->id,
                        'frais_etude' => $product->chapitreFraisEtude?->id,
                        'penalite' => $product->chapitrePenalite?->id
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Erreur CreditFlashController@store: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création'], 500);
        }
    }

    /**
     * Vérifie si le produit existe
     */
    public function checkProduct()
    {
        $product = $this->getFlashProduct();
        return response()->json([
            'success' => true,
            'exists' => (bool)$product,
            'product' => $product ? [
                'id' => $product->id, 
                'nom' => $product->nom,
                'code' => $product->code,
                'montant_min' => $product->montant_min ?? 5000,
                'montant_max' => $product->montant_max ?? 2000000,
                'duree_max' => $product->duree_max ?? 14,
                'grille_tarification' => $product->grille_tarification
            ] : null
        ]);
    }

    /**
     * Récupère la grille tarifaire détaillée
     */
    public function grilleTarifaire()
    {
        try {
            $product = $this->getFlashProduct();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }

            $grille = $product->grille_tarification ?? [];

            $grilleDetaillee = array_map(function($palier) {
                $montantMax = isset($palier['max']) ? number_format($palier['max']) . ' FCFA' : 'Illimité';
                $montantMin = number_format($palier['min']) . ' FCFA';
                
                $premierJour = $palier['premier_jour'];
                $journalier = $palier['journalier'];
                $penalite = $palier['penalite_jour'];
                $fraisEtude = $palier['frais_etude'];
                
                if ($premierJour === 'proportionnel') {
                    $premierJour = 'Calcul proportionnel';
                    $total14j = 'Calcul proportionnel + (1.000 × 13)';
                    $exemple = 'Ex: 750.000 FCFA → ' . round((750000 * 10000) / 500000) . ' + 13.000 = ' . 
                             (round((750000 * 10000) / 500000) + 13000) . ' FCFA';
                } else {
                    $total14j = number_format($premierJour) . ' + (' . number_format($journalier) . ' × 13) = ' . 
                               number_format($premierJour + ($journalier * 13)) . ' FCFA';
                    $exempleMontant = $palier['max'] ?? 25000;
                    $exemple = number_format($exempleMontant) . ' FCFA → ' . 
                             number_format($premierJour) . ' + ' . number_format($journalier * 13) . ' = ' .
                             number_format($premierJour + ($journalier * 13)) . ' FCFA';
                }
                
                return [
                    'tranche' => "{$montantMin} - {$montantMax}",
                    'premier_jour' => is_numeric($premierJour) ? number_format($premierJour) . ' FCFA' : $premierJour,
                    'journalier' => number_format($journalier) . ' FCFA/jour',
                    'penalite_retard' => is_numeric($penalite) ? number_format($penalite) . ' FCFA/jour' : $penalite,
                    'frais_etude' => is_numeric($fraisEtude) ? number_format($fraisEtude) . ' FCFA' : $fraisEtude,
                    'total_14j' => $total14j,
                    'exemple' => $exemple
                ];
            }, $grille);

            return response()->json([
                'success' => true,
                'data' => [
                    'produit' => $product->nom,
                    'code' => $product->code,
                    'duree' => '1 à 14 jours',
                    'logique_calcul' => 'Intérêts = J1 fixe selon palier + (duree-1) jours à taux journalier fixe',
                    'grille' => $grilleDetaillee,
                    'exemples_calcules' => [
                        '25.000 FCFA sur 14j' => $this->calculateExample(25000, 14),
                        '50.000 FCFA sur 14j' => $this->calculateExample(50000, 14),
                        '100.000 FCFA sur 14j' => $this->calculateExample(100000, 14),
                        '300.000 FCFA sur 14j' => $this->calculateExample(300000, 14),
                        '750.000 FCFA sur 14j' => $this->calculateExample(750000, 14)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur grilleTarifaire: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
        }
    }

    /**
     * Génère un tableau d'amortissement pour le crédit flash
     */
    public function amortissement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:5000',
            'duree' => 'integer|min:1|max:14'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $amortissement = $this->creditService->generateFlashAmortissement(
                $request->montant, 
                $request->duree ?? 14
            );
            
            return response()->json([
                'success' => true,
                'data' => $amortissement
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur amortissement: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur génération amortissement'], 500);
        }
    }

    /**
     * Calcule un exemple pour la grille tarifaire
     */
    private function calculateExample(float $montant, int $duree): array
    {
        try {
            $simulation = $this->creditService->simulateFlashCredit($montant, $duree);
            return [
                'capital' => number_format($montant) . ' FCFA',
                'premier_jour' => number_format($simulation['simulation']['frais_premier_jour']) . ' FCFA',
                'jours_suivants' => number_format($simulation['simulation']['frais_journalier']) . ' FCFA × ' . ($duree - 1) . ' jours',
                'total_interets' => number_format($simulation['simulation']['total_interets']) . ' FCFA',
                'frais_etude' => number_format($simulation['frais_etude']) . ' FCFA',
                'penalite_jour' => number_format($simulation['penalite_par_jour']) . ' FCFA/jour',
                'total_rembourser' => number_format($simulation['total_avec_frais']) . ' FCFA'
            ];
        } catch (\Exception $e) {
            return ['erreur' => 'Calcul impossible'];
        }
    }

    /**
     * Calcule les intérêts selon le palier
     */
    private function getPalierDescription($montant, $duree = 14): array
    {
        $m = (float) $montant;
        
        if ($m <= 25000) {
            $premierJour = 1500;
            $journalier = 500;
            $penalite = 1000;
            $fraisEtude = 500;
            $palier = "Palier 1: ≤ 25.000 FCFA";
        } elseif ($m <= 50000) {
            $premierJour = 2000;
            $journalier = 500;
            $penalite = 1500;
            $fraisEtude = 1000;
            $palier = "Palier 2: 25.001 - 50.000 FCFA";
        } elseif ($m <= 250000) {
            $premierJour = 5000;
            $journalier = 1000;
            $penalite = 2000;
            $fraisEtude = 2000;
            $palier = "Palier 3: 50.001 - 250.000 FCFA";
        } elseif ($m <= 500000) {
            $premierJour = 10000;
            $journalier = 1000;
            $penalite = 3000;
            $fraisEtude = 3000;
            $palier = "Palier 4: 250.001 - 500.000 FCFA";
        } else {
            // Règle de 3 sur le palier 4
            $premierJour = ($m * 10000) / 500000;
            $journalier = 1000;
            $penalite = ($m * 3000) / 500000;
            $fraisEtude = ($m * 3000) / 500000;
            $palier = "Palier 5: > 500.000 FCFA (proportionnel)";
        }
        
        $joursRestants = $duree - 1;
        $fraisJoursRestants = $journalier * $joursRestants;
        $totalInterets = $premierJour + $fraisJoursRestants;
        
        return [
            'palier' => $palier,
            'premier_jour' => round($premierJour),
            'journalier' => round($journalier),
            'jours_restants' => $joursRestants,
            'frais_jours_restants' => round($fraisJoursRestants),
            'total_interets' => round($totalInterets),
            'penalite_par_jour' => round($penalite),
            'frais_etude' => round($fraisEtude)
        ];
    }
}
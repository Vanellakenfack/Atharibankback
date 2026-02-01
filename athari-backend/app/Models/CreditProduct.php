<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'type',
        'description',
        'taux_interet',
        'frais_etude',
        'frais_mise_en_place',
        'penalite_retard',
        'duree_min',
        'duree_max',
        'temps_obtention',
        'grille_tarification',
        'montant_min',
        'montant_max',
        // Chapitres comptables
        'chapitre_capital_id',
        'chapitre_interet_id',
        'chapitre_frais_etude_id',
        'chapitre_penalite_id',
        'chapitre_frais_de_mise_en_place',
        // Autres
        'is_active',
        'logique_calcul',
        'formule_calcul',
        'exemple_calcul'
    ];

    protected $casts = [
        'grille_tarification' => 'array',
        'is_active' => 'boolean',
        'montant_min' => 'decimal:2',
        'montant_max' => 'decimal:2',
        'taux_interet' => 'decimal:2',
        'frais_etude' => 'decimal:2',
        'penalite_retard' => 'decimal:2',
        'frais_mise_en_place' => 'decimal:2',
    ];

    // Ajoutez ces accesseurs pour le débogage
    protected $appends = ['codes_chapitres', 'chapitres_comptables'];

    /* ===================== RELATIONS ===================== */

    public function chapitreCapital()
    {
        return $this->belongsTo(\App\Models\chapitre\PlanComptable::class, 'chapitre_capital_id');
    }

    public function chapitreInteret()
    {
        return $this->belongsTo(\App\Models\chapitre\PlanComptable::class, 'chapitre_interet_id');
    }

    public function chapitreFraisEtude()
    {
        return $this->belongsTo(\App\Models\chapitre\PlanComptable::class, 'chapitre_frais_etude_id');
    }

    public function chapitrePenalite()
    {
        return $this->belongsTo(\App\Models\chapitre\PlanComptable::class, 'chapitre_penalite_id');
    }

    public function chapitreMiseEnPlace()
    {
        return $this->belongsTo(\App\Models\chapitre\PlanComptable::class, 'chapitre_frais_de_mise_en_place');
    }

    /* ===================== MÉTHODES POUR CHAPITRES COMPTABLES ===================== */

    /**
     * Récupère tous les chapitres comptables associés
     */
    public function getChapitresComptablesAttribute(): array
    {
        // Charger explicitement toutes les relations
        $this->loadMissing([
            'chapitreCapital',
            'chapitreInteret',
            'chapitreFraisEtude',
            'chapitrePenalite',
            'chapitreMiseEnPlace'
        ]);

        return [
            'capital' => $this->chapitreCapital,
            'interet' => $this->chapitreInteret,
            'frais_etude' => $this->chapitreFraisEtude,
            'penalite' => $this->chapitrePenalite,
            'frais_mise_en_place' => $this->chapitreMiseEnPlace
        ];
    }

    /**
     * Récupère les codes des chapitres comptables
     */
    public function getCodesChapitresAttribute(): array
    {
        $chapitres = $this->chapitres_comptables;
        
        return [
            'capital' => $chapitres['capital']?->code,
            'interet' => $chapitres['interet']?->code,
            'frais_etude' => $chapitres['frais_etude']?->code,
            'penalite' => $chapitres['penalite']?->code,
            'frais_mise_en_place' => $chapitres['frais_mise_en_place']?->code
        ];
    }

    /**
     * Vérifie si tous les chapitres sont configurés
     */
    public function hasChapitresConfigures(): bool
    {
        $chapitres = $this->chapitres_comptables;
        return !empty(array_filter($chapitres, function($chapitre) {
            return $chapitre !== null;
        }));
    }

    /**
     * Affiche les informations de débogage des chapitres
     */
    public function debugChapitres(): array
    {
        return [
            'chapitre_capital_id' => $this->chapitre_capital_id,
            'chapitre_interet_id' => $this->chapitre_interet_id,
            'chapitre_frais_etude_id' => $this->chapitre_frais_etude_id,
            'chapitre_penalite_id' => $this->chapitre_penalite_id,
            'chapitre_frais_de_mise_en_place' => $this->chapitre_frais_de_mise_en_place,
            'relations_loaded' => [
                'chapitreCapital' => $this->relationLoaded('chapitreCapital'),
                'chapitreInteret' => $this->relationLoaded('chapitreInteret'),
                'chapitreFraisEtude' => $this->relationLoaded('chapitreFraisEtude'),
                'chapitrePenalite' => $this->relationLoaded('chapitrePenalite'),
                'chapitreMiseEnPlace' => $this->relationLoaded('chapitreMiseEnPlace'),
            ],
            'codes_chapitres' => $this->codes_chapitres
        ];
    }

    /* ===================== LOGIQUE CRÉDIT FLASH ===================== */

    /**
     * Calcule les intérêts selon la nouvelle logique du Crédit Flash
     */
    public function calculateFlashDetails(float $montant, int $duree = 14): array
    {
        $m = (float) $montant;
        $duree = max(1, min($duree, 14)); // Entre 1 et 14 jours
        
        // Calculer selon les paliers
        if ($m <= 25000) {
            $premierJour = 1500;
            $journalier = 500;
            $penaliteJour = 1000;
            $fraisEtude = 500;
            $palier = 'Palier 1: ≤ 25.000 FCFA';
        } elseif ($m <= 50000) {
            $premierJour = 2000;
            $journalier = 500;
            $penaliteJour = 1500;
            $fraisEtude = 1000;
            $palier = 'Palier 2: 25.001 - 50.000 FCFA';
        } elseif ($m <= 250000) {
            $premierJour = 5000;
            $journalier = 1000;
            $penaliteJour = 2000;
            $fraisEtude = 2000;
            $palier = 'Palier 3: 50.001 - 250.000 FCFA';
        } elseif ($m <= 500000) {
            $premierJour = 10000;
            $journalier = 1000;
            $penaliteJour = 3000;
            $fraisEtude = 3000;
            $palier = 'Palier 4: 250.001 - 500.000 FCFA';
        } else {
            // Règle de 3 sur le palier 4 (250001-500000)
            $premierJour = ($m * 10000) / 500000;
            $journalier = 1000;
            $penaliteJour = ($m * 3000) / 500000;
            $fraisEtude = ($m * 3000) / 500000;
            $palier = 'Palier 5: > 500.000 FCFA (proportionnel)';
        }
        
        $joursRestants = $duree - 1;
        
        // Calcul des frais pour les jours restants
        $fraisJoursRestants = $journalier * $joursRestants;
        
        // Total des intérêts
        $totalInterets = $premierJour + $fraisJoursRestants;
        
        // Calcul du taux d'intérêt annualisé
        $tauxJournalier = ($totalInterets / $m) * 100;
        $tauxAnnualise = $tauxJournalier * (365 / $duree);
        
        // Date d'échéance
        $dateEcheance = now()->addDays($duree)->format('Y-m-d');
        
        return [
            'montant_capital' => $m,
            'duree_jours' => $duree,
            'frais_premier_jour' => round($premierJour),
            'frais_journalier' => round($journalier),
            'frais_jours_restants' => round($fraisJoursRestants),
            'total_interets' => round($totalInterets),
            'total_a_rembourser' => round($m + $totalInterets),
            'taux_annualise' => round($tauxAnnualise, 2),
            'penalite_par_jour' => round($penaliteJour),
            'frais_etude_dossier' => round($fraisEtude),
            'date_echeance' => $dateEcheance,
            'description_frais' => "J1: " . round($premierJour) . " FCFA + {$joursRestants}j × " . round($journalier) . " FCFA",
            'palier' => $palier,
            'details_calcul' => [
                'Capital' => round($m) . ' FCFA',
                'Durée' => $duree . ' jours',
                'Premier jour' => round($premierJour) . ' FCFA',
                'Taux journalier' => round($journalier) . ' FCFA/jour',
                'Jours suivants' => "{$joursRestants} jours × " . round($journalier) . " FCFA",
                'Pénalité retard' => round($penaliteJour) . ' FCFA/jour',
                'Frais étude' => round($fraisEtude) . ' FCFA'
            ]
        ];
    }

    /**
     * Calcule les intérêts selon le type de produit
     */
    public function calculerInterets(float $montant, int $duree = null)
    {
        // Si c'est un Crédit Flash 24H, utiliser la logique spécifique
        if ($this->code === 'FLASH_24H') {
            $duree = $duree ?? $this->duree_max;
            $calcul = $this->calculateFlashDetails($montant, $duree);
            return $calcul['total_interets'];
        }

        // Si c'est un produit avec grille forfaitaire
        if (!empty($this->grille_tarification) && is_array($this->grille_tarification)) {
            foreach ($this->grille_tarification as $palier) {
                if ($montant >= $palier['min'] && $montant <= $palier['max']) {
                    return (float) $palier['frais'];
                }
            }
        }

        // Sinon calcul classique avec taux d'intérêt
        if ($this->taux_interet > 0) {
            return ($montant * $this->taux_interet * ($duree ?? 12)) / 1200;
        }

        return 0;
    }

    /**
     * Vérifie si c'est un crédit flash
     */
    public function isFlashCredit(): bool
    {
        return $this->code === 'FLASH_24H' || $this->type === 'credit_flash';
    }

    /**
     * Calcul des frais d'étude selon la grille
     */
    public function calculFraisEtude(float $montant): float
    {
        if ($this->isFlashCredit()) {
            $details = $this->calculateFlashDetails($montant);
            return $details['frais_etude_dossier'];
        }

        // Pour les autres produits
        return ($montant * $this->frais_etude) / 100;
    }

    /**
     * Calcul des pénalités de retard
     */
    public function calculPenaliteRetard(float $montant, int $joursRetard = 1): float
    {
        if ($this->isFlashCredit()) {
            $details = $this->calculateFlashDetails($montant);
            return $details['penalite_par_jour'] * $joursRetard;
        }

        // Pour les autres produits
        $penalite = ($montant * $this->penalite_retard) / 100;
        return $penalite * $joursRetard;
    }

    /* ===================== SCOPES ===================== */

    public function scopeFlashProducts($query)
    {
        return $query->where('code', 'FLASH_24H')
                    ->orWhere('type', 'credit_flash');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithChapitres($query)
    {
        return $query->with([
            'chapitreCapital',
            'chapitreInteret',
            'chapitreFraisEtude',
            'chapitrePenalite',
            'chapitreMiseEnPlace'
        ]);
    }

    /* ===================== ACCESSEURS ===================== */

    public function getGrilleInteretsAttribute()
    {
        return $this->grille_tarification ?? [];
    }

    public function getDureeMaxFormattedAttribute()
    {
        return $this->duree_max . ' jours';
    }

    public function getTempsObtentionFormattedAttribute()
    {
        return $this->temps_obtention . ' heures';
    }

    public function getMontantMinFormattedAttribute()
    {
        return number_format($this->montant_min, 0, ',', ' ') . ' FCFA';
    }

    public function getMontantMaxFormattedAttribute()
    {
        return number_format($this->montant_max, 0, ',', ' ') . ' FCFA';
    }
    
    /**
     * Génère un tableau d'amortissement pour le crédit flash
     */
    public function generateAmortissement(float $montant, int $duree = 14): array
    {
        $details = $this->calculateFlashDetails($montant, $duree);
        
        $tableau = [];
        $dateCourante = now();
        
        // Premier jour
        $tableau[] = [
            'jour' => 1,
            'date' => $dateCourante->format('Y-m-d'),
            'capital_restant' => $montant + $details['total_interets'],
            'interet_jour' => $details['frais_premier_jour'],
            'capital_rembourse' => 0,
            'type' => 'intérêt premier jour'
        ];
        
        // Jours suivants
        for ($i = 2; $i <= $duree; $i++) {
            $dateJour = $dateCourante->copy()->addDays($i - 1);
            $tableau[] = [
                'jour' => $i,
                'date' => $dateJour->format('Y-m-d'),
                'capital_restant' => $montant + $details['total_interets'] - (($i - 1) * ($details['total_interets'] / $duree)),
                'interet_jour' => $details['frais_journalier'],
                'capital_rembourse' => 0,
                'type' => 'intérêt journalier'
            ];
        }
        
        // Dernier jour (remboursement du capital)
        $tableau[] = [
            'jour' => $duree + 1,
            'date' => $dateCourante->copy()->addDays($duree)->format('Y-m-d'),
            'capital_restant' => 0,
            'interet_jour' => 0,
            'capital_rembourse' => $montant,
            'type' => 'remboursement capital'
        ];
        
        return [
            'details' => $details,
            'tableau' => $tableau,
            'resume' => [
                'capital' => $montant,
                'total_interets' => $details['total_interets'],
                'total_a_rembourser' => $details['total_a_rembourser'],
                'duree' => $duree,
                'date_debut' => now()->format('Y-m-d'),
                'date_echeance' => $details['date_echeance']
            ]
        ];
    }
}
<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Chapitre\PlanComptable;
use App\Models\Frais\FraisApplication;
use App\Models\Frais\CalculInteret;

class TypeCompte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'types_comptes';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'a_vue',
        'est_mata',
        'necessite_duree',
        'actif',
        
        // Chapitres principaux
        'chapitre_defaut_id',
        
        // Frais ouverture
        'frais_ouverture',
        'frais_ouverture_actif',
        'chapitre_frais_ouverture_id',
        
        // Frais carnet
        'frais_carnet',
        'frais_carnet_actif',
        'chapitre_frais_carnet_id',
        'frais_renouvellement_carnet',
        'frais_renouvellement_actif',
        'chapitre_renouvellement_id',
        'frais_perte_carnet',
        'frais_perte_actif',
        'chapitre_perte_id',
        
        // Commission mensuelle
        'commission_mensuelle_actif',
        'seuil_commission',
        'commission_si_superieur',
        'commission_si_inferieur',
        
        // Commission retrait
        'commission_retrait',
        'commission_retrait_actif',
        'chapitre_commission_retrait_id',
        
        // Commission SMS
        'commission_sms',
        'commission_sms_actif',
        'chapitre_commission_sms_id',
        
        // Intérêts
        'taux_interet_annuel',
        'interets_actifs',
        'frequence_calcul_interet',
        'heure_calcul_interet',
        'chapitre_interet_credit_id',
        'capitalisation_interets',
        
        // Frais déblocage
        'frais_deblocage',
        'frais_deblocage_actif',
        'chapitre_frais_deblocage_id',
        
        // Pénalités
        'penalite_retrait_anticipe',
        'penalite_actif',
        'chapitre_penalite_id',
        'frais_cloture_anticipe',
        'frais_cloture_actif',
        'chapitre_cloture_anticipe_id',
        
        // Minimum compte
        'minimum_compte',
        'minimum_compte_actif',
        
        // Compte attente
        'compte_attente_produits_id',
        
        // Retraits anticipés
        'retrait_anticipe_autorise',
        'validation_retrait_anticipe',
        
        // Durées
        'duree_blocage_min',
        'duree_blocage_max',
        
        'observations',
    ];

    protected $casts = [
        'est_mata' => 'boolean',
        'a_vue' => 'boolean',
        'necessite_duree' => 'boolean',
        'actif' => 'boolean',
        'frais_ouverture_actif' => 'boolean',
        'frais_carnet_actif' => 'boolean',
        'frais_renouvellement_actif' => 'boolean',
        'frais_perte_actif' => 'boolean',
        'commission_mensuelle_actif' => 'boolean',
        'commission_retrait_actif' => 'boolean',
        'commission_sms_actif' => 'boolean',
        'interets_actifs' => 'boolean',
        'capitalisation_interets' => 'boolean',
        'frais_deblocage_actif' => 'boolean',
        'penalite_actif' => 'boolean',
        'frais_cloture_actif' => 'boolean',
        'minimum_compte_actif' => 'boolean',
        'retrait_anticipe_autorise' => 'boolean',
        'validation_retrait_anticipe' => 'boolean',
        'heure_calcul_interet' => 'datetime:H:i',
        'taux_interet_annuel' => 'decimal:2',
        'seuil_commission' => 'decimal:2',
        'commission_si_superieur' => 'decimal:2',
        'commission_si_inferieur' => 'decimal:2',
        'frais_ouverture' => 'decimal:2',
        'frais_carnet' => 'decimal:2',
        'frais_renouvellement_carnet' => 'decimal:2',
        'frais_perte_carnet' => 'decimal:2',
        'commission_retrait' => 'decimal:2',
        'commission_sms' => 'decimal:2',
        'frais_deblocage' => 'decimal:2',
        'penalite_retrait_anticipe' => 'decimal:2',
        'frais_cloture_anticipe' => 'decimal:2',
        'minimum_compte' => 'decimal:2',
    ];

    // ========== RELATIONS ==========
    
    public function chapitreDefaut()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_defaut_id');
    }

    public function chapitreFraisOuverture()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_frais_ouverture_id');
    }

    public function chapitreFraisCarnet()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_frais_carnet_id');
    }

    public function chapitreRenouvellement()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_renouvellement_id');
    }

    public function chapitrePerte()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_perte_id');
    }

    public function chapitreCommissionRetrait()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_commission_retrait_id');
    }

    public function chapitreCommissionSms()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_commission_sms_id');
    }

    public function chapitreInteretCredit()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_interet_credit_id');
    }

    public function chapitreFraisDeblocage()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_frais_deblocage_id');
    }

    public function chapitrePenalite()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_penalite_id');
    }

    public function chapitreClotureAnticipe()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_cloture_anticipe_id');
    }

    public function compteAttenteProduits()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_attente_produits_id');
    }

    public function fraisApplications()
    {
        return $this->hasMany(FraisApplication::class, 'type_compte_id');
    }

    public function calculsInterets()
    {
        return $this->hasMany(CalculInteret::class, 'type_compte_id');
    }

    // ========== SCOPES ==========
    
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeMata($query)
    {
        return $query->where('est_mata', true);
    }

    public function scopeAVue($query) // Corrigé le nom de la méthode (camelCase)
    {
        return $query->where('a_vue', true);
    }

    // ========== MÉTHODES DE CALCUL ==========
    
    /**
     * Calculer les frais d'ouverture
     */
    public function calculerFraisOuverture(): float
    {
        if (!$this->frais_ouverture_actif) {
            return 0;
        }
        
        return (float) $this->frais_ouverture;
    }

    /**
     * Calculer la commission mensuelle en fonction du total des versements
     */
    public function calculerCommissionMensuelle(float $totalVersements): float
    {
        if (!$this->commission_mensuelle_actif) {
            return 0;
        }

        // Si seuil défini
        if ($this->seuil_commission !== null 
            && $this->commission_si_superieur !== null 
            && $this->commission_si_inferieur !== null) {
            
            return $totalVersements >= (float) $this->seuil_commission
                ? (float) $this->commission_si_superieur
                : (float) $this->commission_si_inferieur;
        }

        return 0;
    }

    /**
     * Calculer les intérêts journaliers
     */
    public function calculerInteretsJournaliers(float $solde, \DateTime $date): float
    {
        if (!$this->interets_actifs || !$this->taux_interet_annuel || $solde <= 0) {
            return 0;
        }

        $tauxJournalier = $this->getTauxJournalier();
        $interets = $solde * $tauxJournalier;
        
        return round($interets, 2);
    }

    /**
     * Calculer les intérêts pour une période
     */
    public function calculerInteretsPeriode(
        float $solde, 
        \DateTime $dateDebut, 
        \DateTime $dateFin
    ): array {
        if (!$this->interets_actifs || !$this->taux_interet_annuel || $solde <= 0) {
            return [
                'interets_bruts' => 0,
                'nombre_jours' => 0,
                'taux_journalier' => 0,
                'taux_annuel' => 0,
            ];
        }

        // Nombre de jours (inclusif)
        $interval = $dateDebut->diff($dateFin);
        $nombreJours = $interval->days + 1;

        $tauxJournalier = $this->getTauxJournalier();
        $interetsBruts = $solde * $tauxJournalier * $nombreJours;
        
        return [
            'interets_bruts' => round($interetsBruts, 2),
            'nombre_jours' => $nombreJours,
            'taux_journalier' => $tauxJournalier,
            'taux_annuel' => (float) $this->taux_interet_annuel,
        ];
    }

    /**
     * Calculer la pénalité de retrait anticipé
     */
    public function calculerPenaliteRetrait(float $montant): float
    {
        if (!$this->penalite_actif || !$this->penalite_retrait_anticipe) {
            return 0;
        }

        $penalite = $montant * ((float) $this->penalite_retrait_anticipe / 100);
        
        return round($penalite, 2);
    }

    /**
     * Vérifier si solde permet de prélever commission
     */
    public function peutPreleverCommission($compte, float $montantCommission): bool
    {
        return $compte->solde >= $montantCommission;
    }

    /**
     * Obtenir le taux journalier
     */
    public function getTauxJournalier(): float
    {
        if (!$this->taux_interet_annuel) {
            return 0;
        }
        
        return (float) $this->taux_interet_annuel / 365 / 100;
    }

    // ========== MÉTHODES D'ACCESSIBILITÉ ==========
    
    /**
     * Vérifie si le type de compte nécessite une durée
     */
    public function necessiteDuree(): bool
    {
        return $this->necessite_duree;
    }

    /**
     * Vérifie si le type de compte permet les retraits anticipés
     */
    public function permetRetraitAnticipe(): bool
    {
        return $this->retrait_anticipe_autorise && !$this->validation_retrait_anticipe;
    }

    // ========== DONNÉES STATIQUES ==========
    
    public static function getRubriquesMata(): array
    {
        return [
            'SANTE' => 'Santé',
            'BUSINESS' => 'Business',
            'SCOLARITE' => 'Scolarité',
            'FETE' => 'Fête',
            'FOURNITURES' => 'Fournitures',
            'IMMOBILIER' => 'Immobilier',
        ];
    }

    public static function getDureesBlocage(): array
    {
        return [
            3 => '3 mois',
            4 => '4 mois',
            5 => '5 mois',
            6 => '6 mois',
            7 => '7 mois',
            8 => '8 mois',
            9 => '9 mois',
            10 => '10 mois',
            11 => '11 mois',
            12 => '12 mois',
            24 => '24 mois',
            36 => '36 mois',
        ];
    }
}
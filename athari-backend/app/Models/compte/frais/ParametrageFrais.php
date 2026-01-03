<?php

namespace App\Models\compte\frais;

use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParametrageFrais extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parametrage_frais';
    
    protected $fillable = [
        'type_compte_id',
        'plan_comptable_id',
        'code_frais',
        'libelle_frais',
        'description',
        'type_frais',
        'base_calcul',
        'montant_fixe',
        'taux_pourcentage',
        'seuil_minimum',
        'montant_seuil_atteint',
        'montant_seuil_non_atteint',
        'periodicite',
        'jour_prelevement',
        'heure_prelevement',
        'prelevement_si_debiteur',
        'bloquer_operation',
        'solde_minimum_operation',
        'necessite_autorisation',
        'compte_produit_id',
        'compte_attente_id',
        'regles_speciales',
        'etat',
    ];

    protected $casts = [
        'prelevement_si_debiteur' => 'boolean',
        'bloquer_operation' => 'boolean',
        'necessite_autorisation' => 'boolean',
        'regles_speciales' => 'array',
    ];

    /**
     * Relation: Type de compte associé
     */
    public function typeCompte()
    {
        return $this->belongsTo(TypeCompte::class);
    }

    /**
     * Relation: Plan comptable associé
     */
    public function planComptable()
    {
        return $this->belongsTo(PlanComptable::class);
    }

    /**
     * Relation: Compte produit
     */
    public function compteProduit()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_produit_id');
    }

    /**
     * Relation: Compte d'attente
     */
    public function compteAttente()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_attente_id');
    }

    /**
     * Relation: Frais appliqués
     */
    public function fraisAppliques()
    {
        return $this->hasMany(FraisApplique::class);
    }

    /**
     * Scope: Frais actifs
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 'ACTIF');
    }

    /**
     * Scope: Par type de frais
     */
    public function scopeParType($query, $type)
    {
        return $query->where('type_frais', $type);
    }

    /**
     * Scope: Par périodicité
     */
    public function scopeParPeriodicite($query, $periodicite)
    {
        return $query->where('periodicite', $periodicite);
    }

    /**
     * Calculer le montant du frais
     */
    public function calculerMontant(float $baseCalcul = null): float
    {
        $montant = 0;
        
        switch ($this->base_calcul) {
            case 'FIXE':
                $montant = $this->montant_fixe ?? 0;
                break;
                
            case 'POURCENTAGE_SOLDE':
                $montant = $baseCalcul * ($this->taux_pourcentage / 100);
                break;
                
            case 'POURCENTAGE_VERSEMENT':
                $montant = $baseCalcul * ($this->taux_pourcentage / 100);
                break;
                
            case 'SEUIL_COLLECTE':
                $montant = ($baseCalcul >= $this->seuil_minimum)
                    ? ($this->montant_seuil_atteint ?? 0)
                    : ($this->montant_seuil_non_atteint ?? 0);
                break;
                
            default:
                $montant = $this->montant_fixe ?? 0;
        }
        
        return round($montant, 2);
    }

    /**
     * Vérifier si le frais est applicable aujourd'hui
     */
    public function estApplicableAujourdhui(): bool
    {
        if ($this->periodicite !== 'MENSUEL') {
            return true;
        }
        
        $jourActuel = now()->day;
        $jourPrelevement = $this->jour_prelevement;
        
        // Si jour de prélèvement spécifié, vérifier
        if ($jourPrelevement && $jourActuel != $jourPrelevement) {
            return false;
        }
        
        // Pour le dernier jour du mois
        if (!$jourPrelevement && $jourActuel != now()->endOfMonth()->day) {
            return false;
        }
        
        return true;
    }
}
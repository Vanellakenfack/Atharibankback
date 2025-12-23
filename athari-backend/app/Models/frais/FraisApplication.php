<?php

namespace App\Models\frais;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\compte\Compte;
use App\Models\User;

class FraisApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'compte_id',
        'frais_commission_id',
        'type_frais',
        'montant',
        'solde_avant',
        'solde_apres',
        'rubrique_mata',
        'versement_rubrique',
        'date_debut_periode',
        'date_fin_periode',
        'total_versements_mois',
        'nombre_retraits_mois',
        'compte_debit',
        'compte_credit',
        'statut',
        'est_automatique',
        'valide_par',
        'valide_le',
        'date_application',
        'date_effet',
        'description',
        'metadata'
    ];

    protected $casts = [
        'est_automatique' => 'boolean',
        'date_application' => 'date',
        'date_effet' => 'datetime',
        'valide_le' => 'datetime',
        'metadata' => 'array',
        'montant' => 'decimal:2',
        'solde_avant' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'versement_rubrique' => 'decimal:2',
        'total_versements_mois' => 'decimal:2'
    ];

    /**
     * Relation avec le compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Relation avec la configuration des frais
     */
    public function fraisCommission()
    {
        return $this->belongsTo(FraisCommission::class);
    }

    /**
     * Relation avec l'utilisateur qui a validé
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Scope pour les frais appliqués
     */
    public function scopeAppliques($query)
    {
        return $query->where('statut', 'applique');
    }

    /**
     * Scope pour les frais en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour un type de frais spécifique
     */
    public function scopeType($query, $type)
    {
        return $query->where('type_frais', $type);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_application', [$dateDebut, $dateFin]);
    }
}
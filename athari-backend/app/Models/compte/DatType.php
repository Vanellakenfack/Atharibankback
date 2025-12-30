<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\chapitre\PlanComptable;
use App\Models\compte\ContratDat; // Assurez-vous du nom exact du modèle contrat

class DatType extends Model
{
    protected $fillable = [
        'libelle', 
        'taux_interet', 
        'taux_penalite',
        'duree_mois', 
        'periodicite', 
        'is_jours_reels', 
        'is_precompte', 
        'plan_comptable_chapitre_id', // Racine comptable du DAT
        'plan_comptable_interet_id',  // Compte de charge (6xx)
        'plan_comptable_penalite_id', // Compte de produit (7xx)
        'is_active'
    ];

    /**
     * Le Chapitre comptable servant de racine pour ce type de DAT
     */
    public function chapitre(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_chapitre_id');
    }

    /**
     * Compte de charge pour le versement des intérêts
     */
    public function compteInteret(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_interet_id');
    }

    /**
     * Compte de produit pour la perception des pénalités
     */
    public function comptePenalite(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_penalite_id');
    }

    /**
     * Compte de liaison pour les DAT arrivés à échéance
     */
    

    /**
     * Un type de DAT peut être utilisé pour plusieurs contrats
     */
    public function contrats(): HasMany
    {
        return $this->hasMany(ContratDat::class, 'dat_type_id');
    }
}
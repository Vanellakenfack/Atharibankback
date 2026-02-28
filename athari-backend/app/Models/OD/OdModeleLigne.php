<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\chapitre\PlanComptable;

class OdModeleLigne extends Model
{
    protected $table = 'od_modele_lignes';
    
    protected $fillable = [
        'modele_id',
        'compte_id',
        'sens',
        'libelle',
        'montant_fixe',
        'taux',
        'ordre'
    ];

    protected $casts = [
        'montant_fixe' => 'decimal:2',
        'taux' => 'decimal:5,2',
        'ordre' => 'integer'
    ];

    /**
     * Relation avec le modèle parent
     */
    public function modele(): BelongsTo
    {
        return $this->belongsTo(OdModele::class, 'modele_id');
    }

    /**
     * Relation avec le compte comptable
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_id');
    }

    /**
     * Calculer le montant selon le montant total
     */
    public function calculerMontant(float $montantTotal): float
    {
        if ($this->montant_fixe) {
            return (float) $this->montant_fixe;
        }
        
        if ($this->taux) {
            return ($montantTotal * $this->taux) / 100;
        }
        
        return 0.0;
    }

    /**
     * Vérifier si la ligne utilise un montant fixe
     */
    public function utiliseMontantFixe(): bool
    {
        return !empty($this->montant_fixe);
    }

    /**
     * Vérifier si la ligne utilise un taux
     */
    public function utiliseTaux(): bool
    {
        return !empty($this->taux);
    }

    /**
     * Obtenir le libellé du sens
     */
    public function getSensLibelleAttribute(): string
    {
        return $this->sens === 'D' ? 'Débit' : 'Crédit';
    }

    /**
     * Formater le montant ou taux
     */
    public function getValeurFormateeAttribute(): string
    {
        if ($this->utiliseMontantFixe()) {
            return number_format($this->montant_fixe, 2, ',', ' ') . ' FCFA';
        }
        
        if ($this->utiliseTaux()) {
            return number_format($this->taux, 2, ',', ' ') . '%';
        }
        
        return '0,00 FCFA';
    }
}
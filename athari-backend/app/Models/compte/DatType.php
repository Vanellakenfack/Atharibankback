<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\chapitre\PlanComptable;

class DatType extends Model
{
    protected $fillable = [
        'libelle',
        'taux_interet',
        'duree_mois',
        'taux_penalite',
        'nombre_tranches_requis',
        'plan_comptable_interet_id', // <--- Ajoutez ceci
       'plan_comptable_penalite_id', // <--- Ajo
        'is_active'
    ];

    /**
     * Un type de DAT peut être utilisé pour plusieurs contrats
     */
    public function contrats(): HasMany
    {
        return $this->hasMany(ContratDat::class, 'dat_type_id');
    }

    public function comptePenalite(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_penalite_id');
    }

    public function compteInteret() {
    return $this->belongsTo(PlanComptable::class, 'plan_comptable_interet_id');
}


}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\chapitre\PlanComptable;

class CreditType extends Model
{
    protected $fillable = [
        'nom',
        'taux_interet',
        'duree_valeur',
        'duree_unite',
    ];

    public function plansComptables(): BelongsToMany
    {
        return $this->belongsToMany(
            PlanComptable::class,
            'credit_type_plan_comptable',
            'credit_type_id',
            'plan_comptable_id'
        );
    }

    public function getDureeCompleteAttribute(): string
    {
        return "{$this->duree_valeur} {$this->duree_unite}";
    }
}

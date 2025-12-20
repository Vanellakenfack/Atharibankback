<?php

namespace App\Models\chapitre;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanComptable extends Model
{
    protected $table = 'plan_comptable';

    protected $fillable = [
        'categorie_id',
        'code',
        'libelle',
        'nature_solde',
        'est_actif'
    ];

    /**
     * Récupère la catégorie parente pour accéder au type_compte (ACTIF, PASSIF...)
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieComptable::class, 'categorie_id');
    }

    /**
     * Scope pour ne récupérer que les comptes utilisables
     */
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }
}
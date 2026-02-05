<?php

namespace App\Models\compte;

use App\Models\Compte\compte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraisEnAttente extends Model
{
    protected $table = 'frais_en_attente';

    protected $fillable = [
        'compte_id',
        'montant',
        'type_frais',
        'mois',
        'annee',
        'statut'
    ];

    /**
     * Relation vers le compte
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }
}
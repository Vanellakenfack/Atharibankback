<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaisseDigitale extends Model
{
    protected $table = 'caisses_digitales';

    protected $fillable = [
        'caisse_id',
        'operateur',
        'solde_espece',
        'solde_virtuel_uv',
        'seuil_alerte_espece',
        'est_actif',
    ];

    /**
     * Casts pour assurer la précision des calculs financiers
     */
    protected $casts = [
        'solde_espece' => 'decimal:2',
        'solde_virtuel_uv' => 'decimal:2',
        'seuil_alerte_espece' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    /**
     * Relation vers la caisse physique mère
     */
    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    /**
     * Scope pour filtrer par opérateur plus facilement
     */
    public function scopeOrangeMoney($query)
    {
        return $query->where('operateur', 'ORANGE_MONEY');
    }

    public function scopeMobileMoney($query)
    {
        return $query->where('operateur', 'MOBILE_MONEY');
    }
}
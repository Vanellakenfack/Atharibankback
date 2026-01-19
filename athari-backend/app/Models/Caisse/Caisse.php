<?php

namespace App\Models\Caisse;

use App\Models\SessionAgence\CaisseSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\chapitre\PlanComptable;

class Caisse extends Model
{
    protected $fillable = [
        'guichet_id',
        'code_caisse',
        'libelle',
        'solde_actuel',
        'plafond_max',
        'est_active'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'plafond_max' => 'decimal:2',
        'est_active' => 'boolean'
    ];

    // Relation : Une caisse appartient à un guichet
    public function guichet(): BelongsTo
    {
        return $this->belongsTo(Guichet::class);
    }

    // Relation : Une caisse peut avoir plusieurs sessions (historique)
    public function sessions(): HasMany
    {
        return $this->hasMany(CaisseSession::class);
    }

    public function compteComptable()
{
    return $this->belongsTo(PlanComptable::class, 'compte_comptable_id');
}
}
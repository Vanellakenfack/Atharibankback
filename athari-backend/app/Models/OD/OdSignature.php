<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Compte\MouvementComptable;
use App\Models\Agency;
use App\Models\User;
Use App\Models\compte\Compte;
class OdSignature extends Model
{
    protected $table = 'od_signatures';
    
    protected $fillable = [
        'operation_diverse_id',
        'user_id',
        'niveau_validation',
        'role_validation',
        'decision',
        'commentaire',
        'signature_path',
        'signature_date',
    ];

    protected $casts = [
        'signature_date' => 'datetime',
    ];

    /**
     * Relation avec l'OD
     */
    public function operationDiverse(): BelongsTo
    {
        return $this->belongsTo(OperationDiverse::class);
    }

    /**
     * Relation avec le validateur
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vérifie si la signature est approuvée
     */
    public function estApprouvee(): bool
    {
        return $this->decision === 'APPROUVE';
    }

    /**
     * Vérifie si la signature est en attente
     */
    public function estEnAttente(): bool
    {
        return $this->decision === 'EN_ATTENTE';
    }

    /**
     * Vérifie si la signature est rejetée
     */
    public function estRejetee(): bool
    {
        return $this->decision === 'REJETE';
    }

    /**
     * Scope pour les signatures approuvées
     */
    public function scopeApprouvees($query)
    {
        return $query->where('decision', 'APPROUVE');
    }

    /**
     * Scope pour un niveau de validation spécifique
     */
    public function scopeNiveau($query, $niveau)
    {
        return $query->where('niveau_validation', $niveau);
    }
}
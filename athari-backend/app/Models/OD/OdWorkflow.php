<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Compte\MouvementComptable;
use App\Models\Agency;
use App\Models\User;
Use App\Models\compte\Compte;

class OdWorkflow extends Model
{
    protected $table = 'od_workflow';
    
    protected $fillable = [
        'date_comptable',
        'operation_diverse_id',
        'niveau',
        'role_requis',
        'user_id',
        'decision',
        'commentaire',
        'code_a_verifier', // Ajouté
        'date_decision',
    ];

    protected $casts = [
        'date_decision' => 'datetime',
    ];

    // Niveaux de validation
    const NIVEAU_AGENCE = 1;
    const NIVEAU_COMPTABLE = 2;
    const NIVEAU_DG = 3;

    /**
     * Relation avec l'OD
     */
    public function operationDiverse(): BelongsTo
    {
        return $this->belongsTo(OperationDiverse::class, 'operation_diverse_id');
    }

    /**
     * Relation avec le validateur
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vérifier si le niveau est approuvé
     */
    public function estApprouve(): bool
    {
        return $this->decision === 'APPROUVE';
    }

    /**
     * Vérifier si le niveau est rejeté
     */
    public function estRejete(): bool
    {
        return $this->decision === 'REJETE';
    }

    /**
     * Vérifier si le niveau est en attente
     */
    public function estEnAttente(): bool
    {
        return $this->decision === 'EN_ATTENTE';
    }

    /**
     * Obtenir le libellé du niveau
     */
    public function getLibelleNiveauAttribute(): string
    {
        $libelles = [
            self::NIVEAU_AGENCE => 'Chef d\'agence',
            self::NIVEAU_COMPTABLE => 'Chef comptable',
            self::NIVEAU_DG => 'Directeur général',
        ];
        
        return $libelles[$this->niveau] ?? "Niveau {$this->niveau}";
    }

    /**
     * Vérifier si ce workflow a un code à vérifier
     */
    public function aCodeAVerifier(): bool
    {
        return !empty($this->code_a_verifier);
    }

    /**
     * Marquer le code comme vérifié
     */
    public function marquerCodeVerifie(): bool
    {
        return $this->update(['code_a_verifier' => null]);
    }

    /**
     * Scope pour les validations DG avec code
     */
    public function scopeValideParDG($query)
    {
        return $query->where('niveau', self::NIVEAU_DG)
                    ->where('decision', 'APPROUVE');
    }

    /**
     * Scope pour les validations avec code à vérifier
     */
    public function scopeAvecCodeAVerifier($query)
    {
        return $query->whereNotNull('code_a_verifier');
    }
}
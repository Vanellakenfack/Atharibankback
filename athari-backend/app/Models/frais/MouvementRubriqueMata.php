<?php

namespace App\Models\frais;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\compte\Compte;

class MouvementRubriqueMata extends Model
{
    use HasFactory;

    protected $table = 'mouvements_rubriques_mata';

    protected $fillable = [
        'compte_id',
        'rubrique',
        'montant',
        'solde_rubrique',
        'solde_global',
        'type_mouvement',
        'reference_operation',
        'description'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'solde_rubrique' => 'decimal:2',
        'solde_global' => 'decimal:2'
    ];

    /**
     * Relation avec le compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Scope pour une rubrique spécifique
     */
    public function scopeRubrique($query, $rubrique)
    {
        return $query->where('rubrique', $rubrique);
    }

    /**
     * Scope pour un type de mouvement
     */
    public function scopeTypeMouvement($query, $type)
    {
        return $query->where('type_mouvement', $type);
    }

    /**
     * Scope pour une période
     */
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('created_at', [$dateDebut, $dateFin]);
    }

    /**
     * Obtenir le solde actuel d'une rubrique
     */
    public static function getSoldeRubrique($compteId, $rubrique)
    {
        return self::where('compte_id', $compteId)
            ->where('rubrique', $rubrique)
            ->orderBy('created_at', 'desc')
            ->value('solde_rubrique') ?? 0;
    }

    /**
     * Obtenir le dernier mouvement d'une rubrique
     */
    public static function getDernierMouvement($compteId, $rubrique)
    {
        return self::where('compte_id', $compteId)
            ->where('rubrique', $rubrique)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
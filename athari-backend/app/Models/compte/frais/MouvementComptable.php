<?php

namespace App\Models\compte\frais;

use App\Models\compte\Compte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MouvementComptable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mouvements_comptables';
    
    protected $fillable = [
        'frais_applique_id',
        'operation_id',
        'compte_id',
        'date_mouvement',
        'date_valeur',
        'libelle_mouvement',
        'description',
        'compte_debit_id',
        'compte_credit_id',
        'montant_debit',
        'montant_credit',
        'journal',
        'numero_piece',
        'reference_operation',
        'statut',
        'est_pointage',
        'validateur_id',
        'date_validation',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
        'date_valeur' => 'date',
        'est_pointage' => 'boolean',
        'date_validation' => 'datetime',
    ];

    /**
     * Relation: Frais appliqué
     */
    public function fraisApplique()
    {
        return $this->belongsTo(FraisApplique::class);
    }

    /**
     * Relation: Compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Relation: Compte débit
     */
    public function compteDebit()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_debit_id');
    }

    /**
     * Relation: Compte crédit
     */
    public function compteCredit()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_credit_id');
    }

    /**
     * Relation: Validateur
     */
    public function validateur()
    {
        return $this->belongsTo(\App\Models\User::class, 'validateur_id');
    }

    /**
     * Scope: Mouvements par période
     */
    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_mouvement', [$debut, $fin]);
    }

    /**
     * Scope: Par journal
     */
    public function scopeParJournal($query, $journal)
    {
        return $query->where('journal', $journal);
    }

    /**
     * Scope: Mouvements comptabilisés
     */
    public function scopeComptabilises($query)
    {
        return $query->where('statut', 'COMPTABILISE');
    }

    /**
     * Valider le mouvement
     */
    public function valider(int $userId): bool
    {
        $this->validateur_id = $userId;
        $this->date_validation = now();
        $this->statut = 'COMPTABILISE';
        return $this->save();
    }
}
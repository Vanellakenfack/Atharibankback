<?php

namespace App\Models\Compte;

use App\Models\chapitre\PlanComptable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementComptable extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nom de la table associée (car elle est au pluriel dans la migration)
     */
    protected $table = 'mouvements_comptables';

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
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

    /**
     * Cast des types de colonnes
     */
    protected $casts = [
        'date_mouvement' => 'date',
        'date_valeur'    => 'date',
        'date_validation' => 'datetime',
        'est_pointage'   => 'boolean',
        'montant_debit'  => 'decimal:2',
        'montant_credit' => 'decimal:2',
    ];

    /**
     * Relation avec le compte client/interne (table 'comptes')
     */
    public function compte(): BelongsTo
    {
        // Remplacez 'Compte::class' par votre modèle de compte si nécessaire
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    /**
     * Relation avec le Plan Comptable (Débit)
     */
    public function compteDebit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_debit_id');
    }

    /**
     * Relation avec le Plan Comptable (Crédit)
     */
    public function compteCredit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_credit_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }
}
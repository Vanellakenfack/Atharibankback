<?php

namespace App\Models\compte;

use App\Models\chapitre\PlanComptable;
use App\Models\User;
use App\Models\Concerns\UsesDateComptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementComptable extends Model
{
    use HasFactory, SoftDeletes, UsesDateComptable;

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
        'date_comptable',
        'jours_comptable_id',
        'agence_id',      
        'od_id',          
        'created_by'      
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
     * Boot du modèle pour ajouter les événements
     */
    protected static function booted()
    {
        static::created(function ($mouvement) {
            // NE METTRE À JOUR QUE POUR LES MOUVEMENTS D'OD
            if ($mouvement->od_id) {
                if ($mouvement->compte_id) {
                    $compte = Compte::find($mouvement->compte_id);
                    if ($compte) {
                        // Calculer la variation du solde
                        $variation = $mouvement->montant_credit - $mouvement->montant_debit;
                        
                        // Mettre à jour le solde
                        $compte->solde += $variation;
                        $compte->save();
                        
                        \Log::info('Solde mis à jour depuis OD', [
                            'compte_id' => $compte->id,
                            'numero_compte' => $compte->numero_compte,
                            'variation' => $variation,
                            'nouveau_solde' => $compte->solde,
                            'mouvement_id' => $mouvement->id,
                            'od_id' => $mouvement->od_id
                        ]);
                    }
                }
            } else {
                // Log optionnel pour les autres mouvements
                \Log::debug('Mouvement non OD - pas de mise à jour automatique', [
                    'mouvement_id' => $mouvement->id,
                    'compte_id' => $mouvement->compte_id
                ]);
            }
        });

        static::updated(function ($mouvement) {
            // Si un mouvement est modifié, on ajuste les soldes (uniquement pour les OD)
            if ($mouvement->od_id && $mouvement->isDirty(['montant_debit', 'montant_credit', 'compte_id'])) {
                // Logique de mise à jour si nécessaire
                \Log::warning('Mouvement OD modifié - vérifier la cohérence des soldes', [
                    'mouvement_id' => $mouvement->id,
                    'od_id' => $mouvement->od_id
                ]);
            }
        });

        static::deleted(function ($mouvement) {
            // Si un mouvement est supprimé, on annule son effet sur le solde (uniquement pour les OD)
            if ($mouvement->od_id && $mouvement->compte_id) {
                $compte = Compte::find($mouvement->compte_id);
                if ($compte) {
                    $variation = $mouvement->montant_credit - $mouvement->montant_debit;
                    $compte->solde -= $variation; // On soustrait l'effet
                    $compte->save();
                    
                    \Log::info('Solde ajusté après suppression OD', [
                        'compte_id' => $compte->id,
                        'variation_annulee' => $variation,
                        'nouveau_solde' => $compte->solde,
                        'od_id' => $mouvement->od_id
                    ]);
                }
            }
        });
    }

    /**
     * Relation avec le compte client/interne (table 'comptes')
     */
    public function compte(): BelongsTo
    {
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

    /**
     * Relation avec l'OD (si le mouvement vient d'une OD)
     */
    public function operationDiverse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\OD\OperationDiverse::class, 'od_id');
    }
}
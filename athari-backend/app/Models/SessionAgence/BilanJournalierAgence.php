<?php

namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BilanJournalierAgence extends Model
{
    use HasFactory;

    // Nom de la table (doit correspondre exactement à votre migration)
    protected $table = 'bilan_journalier_agences';

    protected $fillable = [
        'jour_comptable_id',
        'date_comptable',
        'total_especes_entree',
        'total_especes_sortie',
        'solde_theorique_global',
        'solde_reel_global',
        'ecart_global',
        'resume_caisses'
    ];

    /**
     * Casts pour transformer automatiquement le JSON en tableau PHP
     * et s'assurer que les montants restent des nombres précis.
     */
    protected $casts = [
        'resume_caisses' => 'array',
        'date_comptable' => 'date',
        'total_especes_entree' => 'decimal:2',
        'total_especes_sortie' => 'decimal:2',
        'solde_theorique_global' => 'decimal:2',
        'solde_reel_global' => 'decimal:2',
        'ecart_global' => 'decimal:2',
    ];

    /**
     * Relation avec la journée comptable
     */
    public function jourComptable()
    {
        return $this->belongsTo(\App\Models\SessionAgence\JourComptable::class, 'jour_comptable_id');
    }
}
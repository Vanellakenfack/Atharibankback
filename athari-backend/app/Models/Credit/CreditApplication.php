<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_demande',
        'compte_id',
        'credit_type_id',
        'montant',
        'duree',
        'taux_interet',
        'interet_total',
        'frais_dossier',
        'frais_etude',
        'montant_total',
        'penalite_par_jour',
        'calcul_details',
        'date_demande',
        'observation',
        'source_revenus',
        'revenus_mensuels',
        'autres_revenus',
        'montant_dettes',
        'description_dette',
        'nom_banque',
        'numero_banque',
        'statut',
        'code_mise_en_place',
        'note_credit',
        'plan_epargne',

        // 📸 Documents déjà existants
        'photo_4x4',
        'plan_localisation',
        'facture_electricite',
        'casier_judiciaire',
        'historique_compte',

        // 🆕 NOUVEAUX CHAMPS (migration ajoutée)
        'geolocalisation_img',
        'plan_localisation_activite_img',
        'photo_activite_img',
        'numero_personne_contact',
        'demande_credit_img',
    ];

    protected $casts = [
        'calcul_details' => 'array',
        'date_demande' => 'datetime',
        'plan_epargne' => 'boolean',
    ];

    /* =======================
        RELATIONS
    ======================== */

    public function compte()
    {
        return $this->belongsTo(\App\Models\Compte\Compte::class, 'compte_id');
    }

    public function creditType()
    {
        return $this->belongsTo(\App\Models\Credit\CreditType::class, 'credit_type_id');
    }

    public function avisCredits()
    {
        return $this->hasMany(AvisCredit::class);
    }

    public function approvals()
    {
        return $this->hasMany(CreditApproval::class);
    }

    public function pvs()
    {
        return $this->hasMany(CreditPV::class);
    }

    public function documents()
    {
        return $this->hasMany(CreditDocument::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(CreditStatusHistory::class);
    }

    public function decaissements()
    {
        return $this->hasMany(CreditDecaissement::class);
    }

    public function echeanciers()
    {
        return $this->hasMany(CreditEcheancier::class);
    }

    public function remboursements()
    {
        return $this->hasMany(CreditRemboursement::class);
    }

    public function penalites()
    {
        return $this->hasMany(CreditPenalite::class);
    }

    public function avis()
    {
        return $this->hasMany(AvisCredit::class, 'credit_application_id');
    }

    /* =======================
        SCOPES
    ======================== */

    public function scopePending($query)
    {
        return $query->where('statut', 'SOUMIS');
    }

    public function scopeApproved($query)
    {
        return $query->where('statut', 'APPROUVE');
    }

    public function scopeRejected($query)
    {
        return $query->where('statut', 'REJETE');
    }

    /* =======================
        HELPERS
    ======================== */

    public function updateStatus($statut, $observation = null)
    {
        $this->update([
            'statut' => $statut,
            'observation' => $observation ?: $this->observation
        ]);
    }
    public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}
}

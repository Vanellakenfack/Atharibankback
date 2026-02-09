<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreditApplication extends Model
{
    use HasFactory;

    // Constantes de Workflow
    const STATUS_INITIE = 'INITIE';
    const STATUS_AVIS_AGENCE = 'AVIS_AGENCE'; 
    const STATUS_COMITE_AGENCE = 'COMITE_AGENCE';
    const STATUS_VERIF_SIEGE = 'VERIF_SIEGE'; 
    const STATUS_COMITE_DG = 'COMITE_DG';
    const STATUS_VALIDE_DG = 'VALIDE_DG'; 
    const STATUS_MISE_EN_PLACE = 'MISE_EN_PLACE';

    protected $fillable = [
        'numero_demande', 'user_id', 'client_id', 'compte_id', 'credit_type_id',
        'montant', 'duree', 'taux_interet', 'interet_total', 'frais_dossier',
        'frais_etude', 'montant_total', 'penalite_par_jour', 'calcul_details',
        'date_demande', 'observation', 'garantie', 'source_revenus',
        'revenus_mensuels', 'autres_revenus', 'montant_dettes', 'description_dette',
        'nom_banque', 'numero_banque', 'statut', 'code_mise_en_place', 'note_credit',
        'plan_epargne', 'urgence', 'numero_personne_contact',

        // NOUVEAUX CHAMPS WORKFLOW
        'workflow_type',
        'avis_aar', 'avis_chef_agence', 'avis_assistant_comptable',
        'avis_assistant_juridique', 'avis_chef_comptable', 'avis_dg',
        'pv_agence_path', 'pv_direction_path', 'note_client_path',

        // Documents
        'demande_credit_img', 'photocopie_cni', 'photo_4x4', 'plan_localisation',
        'facture_electricite', 'casier_judiciaire', 'historique_compte',
        'geolocalisation_img', 'plan_localisation_activite_img', 'photo_activite_img',
        'plan_localisation_domicile', 'description_domicile', 'geolocalisation_domicile',
        'photo_domicile_1', 'photo_domicile_2', 'photo_domicile_3',
        'description_activite', 'photo_activite_1', 'photo_activite_2',
        'photo_activite_3', 'lettre_non_remboursement',
    ];

    protected $casts = [
        'calcul_details' => 'array',
        'date_demande' => 'datetime',
        'plan_epargne' => 'boolean',
        'montant' => 'decimal:2',
        'revenus_mensuels' => 'decimal:2',
        'autres_revenus' => 'decimal:2',
        'montant_dettes' => 'decimal:2',
        'interet_total' => 'decimal:2',
        'frais_dossier' => 'decimal:2',
        'frais_etude' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'penalite_par_jour' => 'decimal:2',
        'taux_interet' => 'decimal:2',
    ];

    protected $appends = [
        'file_urls',
        'status_label',
        'monthly_payment',
        'can_be_edited',
        'can_be_reviewed'
    ];

    /* =========================================================================
       RELATIONS (CORRIGÉES POUR LE CONTRÔLEUR)
       ========================================================================= */

    public function user() { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function client() { return $this->belongsTo(\App\Models\Client\Client::class, 'client_id'); }
    
    // Relations originales
    public function compte() { return $this->belongsTo(\App\Models\Compte\Compte::class, 'compte_id'); }
    public function creditType() { return $this->belongsTo(CreditType::class, 'credit_type_id'); }

    /**
     * Alias requis par CreditReviewController (eager loading 'compte_info')
     */
    public function compte_info() { return $this->compte(); }

    /**
     * Alias requis par CreditReviewController (eager loading 'credit_type_info')
     */
    public function credit_type_info() { return $this->creditType(); }
    
    public function avis() { return $this->hasMany(AvisCredit::class, 'credit_application_id'); }
    public function pvs() { return $this->hasMany(CreditPV::class, 'credit_application_id'); }
    public function reviews() { return $this->hasMany(CreditReview::class, 'credit_application_id'); }
    public function decaissements() { return $this->hasMany(CreditDecaissement::class, 'credit_application_id'); }

    /* =========================================================================
       ATTRIBUTES (ACCESSEURS)
       ========================================================================= */

    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            self::STATUS_INITIE => 'Initié / Attente Avis',
            self::STATUS_AVIS_AGENCE => 'En cours d\'avis agence',
            self::STATUS_COMITE_AGENCE => 'En Comité d\'Agence',
            self::STATUS_VERIF_SIEGE => 'Vérification Siège (Juridique/Comptable)',
            self::STATUS_COMITE_DG => 'En Comité de Direction',
            self::STATUS_VALIDE_DG => 'Approuvé par DG',
            self::STATUS_MISE_EN_PLACE => 'En mise en place',
            'REJETE' => 'Rejeté',
            'DECAISSE' => 'Décaissé',
        ];

        return $statusLabels[$this->statut] ?? $this->statut;
    }

    public function getFileUrlsAttribute()
    {
        $urls = [];
        $fileFields = [
            'demande_credit_img', 'photocopie_cni', 'photo_4x4', 'plan_localisation',
            'facture_electricite', 'casier_judiciaire', 'historique_compte',
            'geolocalisation_img', 'plan_localisation_activite_img', 'photo_activite_img',
            'plan_localisation_domicile', 'photo_domicile_1', 'photo_domicile_2',
            'photo_domicile_3', 'photo_activite_1', 'photo_activite_2',
            'photo_activite_3', 'lettre_non_remboursement',
            'pv_agence_path', 'pv_direction_path', 'note_client_path'
        ];

        foreach ($fileFields as $field) {
            if ($this->{$field}) {
                 $urls[$field] = Storage::disk('public')->exists($this->{$field}) 
                    ? Storage::url($this->{$field}) 
                    : null;
            } else {
                $urls[$field] = null;
            }
        }

        return $urls;
    }

    public function getMonthlyPaymentAttribute()
    {
        return ($this->duree > 0) ? round($this->montant_total / $this->duree, 2) : 0;
    }

    public function getCanBeEditedAttribute()
    {
        return in_array($this->statut, [self::STATUS_INITIE]);
    }

    public function getCanBeReviewedAttribute()
    {
        return !in_array($this->statut, [self::STATUS_VALIDE_DG, 'REJETE', 'DECAISSE']);
    }

    /* =========================================================================
       LOGIQUE ET HELPERS
       ========================================================================= */

    public function getRemainingAmount()
    {
        $decaissements = $this->decaissements()->sum('montant');
        return max(0, $this->montant_total - $decaissements);
    }

    public function hasAllRequiredDocuments()
    {
        $required = [
            'demande_credit_img', 'photocopie_cni', 'photo_4x4', 'plan_localisation',
            'facture_electricite', 'casier_judiciaire', 'historique_compte'
        ];

        foreach ($required as $doc) {
            if (empty($this->{$doc})) return false;
        }
        return true;
    }

    public function isFlash()
    {
        // On utilise la relation aliasée ou originale
        $type = $this->creditType;
        return $type && $type->category === 'credit_flash';
    }

    public function isFondDeRoulement()
    {
        $type = $this->creditType;
        return $type && $type->category === 'credit_entreprise';
    }

    public function getNextStatusAfterAgency()
    {
        if ($this->isFlash()) {
            return self::STATUS_VALIDE_DG;
        }
        return self::STATUS_COMITE_AGENCE;
    }
    public function avisCredits()
{
    // Remplacez 'App\Models\Credit\CreditReview' par le chemin réel de votre modèle d'avis
    return $this->hasMany(\App\Models\CreditReview::class, 'credit_application_id');
}
}
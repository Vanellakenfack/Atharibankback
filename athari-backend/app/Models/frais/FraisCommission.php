<?php

namespace App\Models\frais;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\compte\TypeCompte;
use App\Models\Concerns\UsesDateComptable;

class FraisCommission extends Model
{
    use HasFactory, SoftDeletes, UsesDateComptable;

    protected $fillable = [
        'type_compte_id',
        'frais_ouverture',
        'frais_ouverture_actif',
        'frais_tenue_compte',
        'frais_tenue_actif',
        'commission_mouvement',
        'commission_mouvement_type',
        'commission_mouvement_actif',
        'commission_retrait',
        'commission_retrait_actif',
        'commission_sms',
        'commission_sms_actif',
        'frais_deblocage',
        'frais_deblocage_actif',
        'frais_cloture_anticipe',
        'frais_cloture_anticipe_actif',
        'taux_interet_annuel',
        'frequence_calcul_interet',
        'heure_calcul_interet',
        'interets_actifs',
        'penalite_retrait_anticipe',
        'penalite_actif',
        'minimum_compte',
        'minimum_compte_actif',
        'seuil_commission_mensuelle',
        'commission_mensuelle_elevee',
        'commission_mensuelle_basse',
        'compte_commission_paiement',
        'compte_produit_commission',
        'compte_attente_produits',
        'compte_attente_sms',
        'retrait_anticipe_autorise',
        'validation_retrait_anticipe',
        'duree_blocage_min',
        'duree_blocage_max',
        'observations'
    ];

    protected $casts = [
        'frais_ouverture_actif' => 'boolean',
        'frais_tenue_actif' => 'boolean',
        'commission_mouvement_actif' => 'boolean',
        'commission_retrait_actif' => 'boolean',
        'commission_sms_actif' => 'boolean',
        'frais_deblocage_actif' => 'boolean',
        'frais_cloture_anticipe_actif' => 'boolean',
        'interets_actifs' => 'boolean',
        'penalite_actif' => 'boolean',
        'minimum_compte_actif' => 'boolean',
        'retrait_anticipe_autorise' => 'boolean',
        'validation_retrait_anticipe' => 'boolean',
        'heure_calcul_interet' => 'datetime:H:i',
        'taux_interet_annuel' => 'decimal:5',
        'penalite_retrait_anticipe' => 'decimal:2'
    ];

    /**
     * Relation avec le type de compte
     */
    public function typeCompte()
    {
        return $this->belongsTo(TypeCompte::class);
    }

    /**
     * Relation avec les applications de frais
     */
    public function applications()
    {
        return $this->hasMany(FraisApplication::class);
    }

    /**
     * Calculer les frais d'ouverture
     */
    public function calculerFraisOuverture($montant = null)
    {
        if (!$this->frais_ouverture_actif) {
            return 0;
        }

        return $this->frais_ouverture;
    }

    /**
     * Calculer la commission mensuelle en fonction du total des versements
     */
    public function calculerCommissionMensuelle($totalVersements)
    {
        if (!$this->commission_mouvement_actif) {
            return 0;
        }

        if ($this->seuil_commission_mensuelle && 
            $this->commission_mensuelle_elevee && 
            $this->commission_mensuelle_basse) {
            
            return $totalVersements >= $this->seuil_commission_mensuelle
                ? $this->commission_mensuelle_elevee
                : $this->commission_mensuelle_basse;
        }

        return $this->commission_mouvement;
    }

    /**
     * Calculer les intérêts journaliers
     */
    public function calculerInteretsJournaliers($solde)
    {
        if (!$this->interets_actifs || !$this->taux_interet_annuel) {
            return 0;
        }

        // Conversion du taux annuel en taux journalier
        $tauxJournalier = $this->taux_interet_annuel / 365;
        
        return $solde * ($tauxJournalier / 100);
    }
}
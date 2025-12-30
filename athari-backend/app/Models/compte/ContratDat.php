<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Model;
use App\Models\compte\Compte;
use Carbon\Carbon;
use App\Models\chapitre\PlanComptable;

class ContratDat extends Model
{
    protected $table = 'dat_contracts';

       protected $fillable = [
    'numero_ordre',
    'statut',
    'dat_type_id',
    'account_id',
    'client_source_account_id', 
    'destination_interet_id',
    'destination_capital_id',
    'montant_initial',
    'montant_actuel',
    'taux_interet_annuel',
    'taux_penalite_anticipe',
    'duree_mois',
    'periodicite',
    'mode_versement',
    'is_jours_reels',
    'is_precompte',
    'date_execution',
    'date_valeur',
    'date_maturite',
    'is_blocked',
    'nb_tranches_actuel'
];

    protected $appends = ['jours_restants', 'progression_temps', 'montant_interets_precomptes'];

    /**
     * RELATION : Vers le compte client (DAT)
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'account_id');
    }

    /**
     * CALCUL : Montant des intérêts si précompté
     * Formule simple : (Capital * Taux * Durée) / 12
     */
    public function getMontantInteretsPrecomptesAttribute()
    {
        if (!$this->is_precompte) return 0;
        
        $capital = $this->compte->solde ?? 0;
        $taux = $this->taux_interet_annuel;
        $duree = $this->duree_mois;

        return round(($capital * $taux * $duree) / 12, 2);
    }

    /**
     * LOGIQUE DE PÉNALITÉ : Calculée sur le solde actuel
     */
    public function calculerPenalite()
    {
        if (now()->lt(Carbon::parse($this->date_maturite))) {
            // On utilise le taux scellé au contrat
            return ($this->compte->solde ?? 0) * $this->taux_penalite_anticipe; 
        }
        return 0;
    }

    /**
     * ATTRIBUT : Jours restants avant la fin
     */
    public function getJoursRestantsAttribute()
    {
        if (!$this->date_maturite) return null;
        
        $fin = Carbon::parse($this->date_maturite);
        $maintenant = now();
        
        return $maintenant->diffInDays($fin, false) > 0 ? $maintenant->diffInDays($fin) : 0;
    }

    /**
     * ATTRIBUT : % de progression du contrat
     */
    public function getProgressionTempsAttribute()
    {
        if (!$this->date_scellage || !$this->date_maturite) return 0;
        
        $debut = Carbon::parse($this->date_scellage);
        $fin = Carbon::parse($this->date_maturite);
        
        $total = $debut->diffInDays($fin);
        $ecoule = $debut->diffInDays(now());
        
        return $total > 0 ? min(round(($ecoule / $total) * 100, 2), 100) : 0;
    }

    // Relations Comptables
    public function compteInteret() {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_interet_id');
    }

    public function comptePenalite() {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_penalite_id');
    }

    public function type()
    {
        // Vérifiez bien que la clé étrangère dans votre table contrats est 'dat_type_id'
        return $this->belongsTo(DatType::class, 'dat_type_id');
    }

    /**
     * RELATION : Le compte courant source du client
     */
    public function clientSourceAccount()
    {
        return $this->belongsTo(Compte::class, 'client_source_account_id');
    }

    /**
     * RELATION : Le compte de destination pour le capital à l'échéance
     */
    public function destinationCapital()
    {
        return $this->belongsTo(Compte::class, 'destination_capital_id');
    }

    /**
     * RELATION : Le compte de destination pour les intérêts
     */
    public function destinationInteret()
    {
        return $this->belongsTo(Compte::class, 'destination_interet_id');
    }

    /**
     * RELATION : Vers le compte scellé (Alias de account_id pour correspondre au contrôleur)
     */
    public function account()
    {
        return $this->belongsTo(Compte::class, 'account_id');
    }
}
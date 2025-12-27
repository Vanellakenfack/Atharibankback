<?php
namespace App\Models\compte; // On précise qu'il est dans le dossier Compte

use Illuminate\Database\Eloquent\Model;
use App\Models\compte\Compte;
use Carbon\Carbon; // 
use App\Models\chapitre\PlanComptable;
class ContratDat extends Model

{
    protected $table = 'dat_contracts';

         protected $fillable = [
    'account_id',
    'dat_type_id',
    'taux_interet_annuel',
    'taux_penalite_anticipe',
    'duree_mois',
    'code_comptable_interet',
    'statut',
    'is_blocked',
    'date_scellage',
    'date_maturite'
];
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'account_id');
    }

    /**
     * LOGIQUE DE PÉNALITÉ
     * Cette fonction calcule l'amende si le client part trop tôt
     */
    public function calculerPenalite()
    {
        // 1. On vérifie si on est avant la date de fin
        if (now()->lt($this->date_fin_prevue)) {
            // 2. Si oui, on prend 10% du solde actuel du compte
            // On accède au solde via la liaison $this->compte
            return $this->compte->solde * $this->taux_penalite; 
        }

        // 3. Si on est après la date, la pénalité est de 0
        return 0;
    }



// ...

// 1. Calcul des jours restants avant maturité
public function getJoursRestantsAttribute()
{
    if (!$this->date_maturite_prevue) return null;
    
    $fin = Carbon::parse($this->date_maturite_prevue);
    $maintenant = now();
    
    return $maintenant->diffInDays($fin, false) > 0 
           ? $maintenant->diffInDays($fin) 
           : 0;
}

// 2. Calcul du pourcentage de progression du temps
public function getProgressionTempsAttribute()
{
    if (!$this->date_ouverture || !$this->date_maturite_prevue) return 0;
    
    $debut = Carbon::parse($this->date_ouverture);
    $fin = Carbon::parse($this->date_maturite_prevue);
    $total = $debut->diffInDays($fin);
    $ecoule = $debut->diffInDays(now());
    
    return $total > 0 ? min(round(($ecoule / $total) * 100, 2), 100) : 0;
}

// App\Models\Compte\ContratDat.php
public function compteInteret() {
    return $this->belongsTo(PlanComptable::class, 'plan_comptable_interet_id');
}

public function comptePenalite() {
    return $this->belongsTo(PlanComptable::class, 'plan_comptable_penalite_id');
}
// 3. Pour que ces champs apparaissent dans le JSON
protected $appends = ['jours_restants', 'progression_temps'];
}
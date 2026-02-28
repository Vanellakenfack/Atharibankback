<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\compte\Compte;
use App\Models\Gestionnaire;
use App\Models\Concerns\UsesDateComptable;

class CaisseTransaction extends Model
{
    use UsesDateComptable;
   protected $fillable = [
    'reference_unique',
    'compte_id',
    'session_id',
    'code_agence',
    'code_guichet',
    'code_caisse',
    'type_flux',
    'type_versement', 
  'reference_externe',
    'montant_brut',
    'origine_fonds',
    'commissions',
    'taxes',
    'frais_en_compte',
    'numero_bordereau',
    'type_bordereau',
    'date_operation',
    'date_valeur',
    'code_desaccord',
    'caissier_id',
    'approbateur_id',
    'statut',
    'is_retrait_distance',
    'statut_workflow',
    'pj_demande_retrait',
    'pj_procuration',
    'bordereau_rerait',
    'gestionnaire_id',
    'chef_agence_id',
    'motif_rejet_ca',
    'date_validation_ca',
    'code_validation',
    'date_comptable',
    'jours_comptable_id'
];

    // Relation vers le compte client
    public function compte() {
        return $this->belongsTo(Compte::class);
    }

    // Relation vers la session de caisse
    public function session() {
        return $this->belongsTo(\App\Models\SessionAgence\CaisseSession::class, 'session_id');
    }

    // Relation vers le caissier
    public function caissier() {
        return $this->belongsTo(\App\Models\User::class, 'caissier_id');
    }

    // Relation vers l'approbateur
    public function approbateur() {
        return $this->belongsTo(\App\Models\User::class, 'approbateur_id');
    }

    // Relation vers le billetage détaillé
    public function billetages() {
        return $this->hasMany(TransactionBilletage::class, 'transaction_id');
    }
    /**
 * Récupérer les informations du tiers lié à cette transaction.
 */
        public function tier()
        {
            return $this->hasOne(TransactionTier::class, 'transaction_id');
        }

        // App/Models/Caisse/CaisseTransaction.php
public function demandeValidation()
{
    // On lie la transaction à la demande via le montant, la caissière et le statut EXECUTE
    return $this->hasOne(CaisseDemandeValidation::class, 'payload_data->reference_unique', 'reference_unique');
}

public function gestionnaire()
    {
        // Vérifie bien le nom de la classe et la clé étrangère
        return $this->belongsTo(Gestionnaire::class, 'gestionnaire_id');
    }
}
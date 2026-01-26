<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\compte\Compte;
class CaisseTransaction extends Model
{
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
    'statut'
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
}
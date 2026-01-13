<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\compte\Compte;
class CaisseTransaction extends Model
{
   protected $fillable = [
    'reference_unique',
    'compte_id',
    'code_agence',
    'code_guichet',
    'code_caisse',
    'type_flux',
    'montant_brut',
    'commissions',
    'taxes',
    'frais_en_compte', // Vérifiez bien ce nom
    'date_operation',
    'date_valeur',
    'caissier_id',
    'statut'
];

    // Relation vers le compte client
    public function compte() {
        return $this->belongsTo(Compte::class);
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
}
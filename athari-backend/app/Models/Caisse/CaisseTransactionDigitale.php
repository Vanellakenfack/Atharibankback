<?php
namespace App\Models\Caisse;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\UsesDateComptable;
use App\Models\User;
use App\Models\compte\Compte;

class CaisseTransactionDigitale extends Model
{
    use UsesDateComptable;

    protected $table = 'caisse_transactions_digitales';

    protected $fillable = [
        'reference_unique',
        'session_id',
        'compte_id',        // ID du compte si client banque, null si passage
        'code_agence',
        'code_guichet',
        'code_caisse',
        'type_flux',        // RETRAIT / VERSEMENT
        'operateur',        // ORANGE_MONEY / MOBILE_MONEY
        'montant_brut',
        'commissions',      // Commission prélevée au client
        'reference_operateur', // ID transaction fourni par l'opérateur (SIM)
        'telephone_client',
        'date_operation',
        'date_valeur',
        'caissier_id',
        'statut',
        'metadata',
        'date_comptable',
        'jour_comptable_id'
    ];

    protected $casts = [
        'metadata' => 'array',
        'date_operation' => 'datetime',
        'date_valeur' => 'datetime',
    ];

    public function caissier() {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function compteBancaire() {
        return $this->belongsTo(Compte::class, 'compte_id');
    }
    public function agence()
    {
        return $this->belongsTo(Agency::class, 'agence_id');
    }
}
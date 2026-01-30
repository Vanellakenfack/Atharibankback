<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\Caisse\CaisseTransaction; // Assurez-vous du chemin correct

class CaisseTransactionDigitale extends Model
{
    // On définit explicitement la table si Laravel ne la devine pas
    protected $table = 'caisse_transactions_digitales';

    protected $fillable = [
        'caisse_transaction_id',
        'reference_operateur',
        'telephone_client',
        'operateur',
        'commission_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relation vers la transaction parente
     */
    public function transaction()
    {
        return $this->belongsTo(CaisseTransaction::class, 'caisse_transaction_id');
    }
}
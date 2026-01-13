<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;

class TransactionBilletage extends Model
{
    protected $fillable = ['transaction_id', 'valeur_coupure', 'quantite', 'sous_total'];

    public function transaction() {
        return $this->belongsTo(CaisseTransaction::class);
    }
}
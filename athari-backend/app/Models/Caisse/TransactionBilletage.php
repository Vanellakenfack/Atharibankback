<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\UsesDateComptable;

class TransactionBilletage extends Model
{
    use UsesDateComptable;
    protected $fillable = ['transaction_id', 'valeur_coupure', 'quantite', 'sous_total', 'date_comptable', 'jour_comptable_id'];

    public function transaction() {
        return $this->belongsTo(CaisseTransaction::class);
    }
}
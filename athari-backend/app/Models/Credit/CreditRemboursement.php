<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditRemboursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference',
        'statut'
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}

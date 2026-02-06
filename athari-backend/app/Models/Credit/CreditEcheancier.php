<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditEcheancier extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'numero_echeance',
        'date_echeance',
        'montant_principal',
        'montant_interet',
        'montant_total',
        'statut'
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}

<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPenalite extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'montant_penalite',
        'motif',
        'date_application',
        'statut'
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}

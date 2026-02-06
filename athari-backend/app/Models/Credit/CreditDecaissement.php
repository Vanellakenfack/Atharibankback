<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDecaissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'montant_decaisse',
        'date_decaissement',
        'mode_decaissement',
        'reference_transaction',
        'user_id'
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

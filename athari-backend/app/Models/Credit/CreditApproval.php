<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'compte_id',
        'credit_application_id',
        'avis',
        'commentaire',
        'niveau_validation',
        'statut',
        'decision_date'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function compte()
    {
        return $this->belongsTo(\App\Models\Compte::class);
    }

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}

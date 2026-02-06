<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'user_id',
        'date_changement'
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

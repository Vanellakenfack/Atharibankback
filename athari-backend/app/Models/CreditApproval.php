<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'user_id',
        'role',
        'avis',
        'commentaire',
        'niveau',
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

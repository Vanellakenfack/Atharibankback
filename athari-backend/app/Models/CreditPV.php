<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPV extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'niveau',
        'numero_pv',
        'fichier_pdf',
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}

<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditType extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_characteristics',
        'code',
        'description',
        'category',
        'taux_interet',
        'duree',
        'montant',
        'plan_comptable_id',
        'chapitre_comptable',
        'frais_dossier',
        'penalite'
    ];

    public function creditApplications()
    {
        return $this->hasMany(CreditApplication::class);
    }
}

<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvisCredit extends Model
{
    use HasFactory;

    // Les champs autorisés à être assignés en masse
    protected $fillable = [
        'user_id',
        'credit_application_id',
        'role',
        'opinion',
        'commentaire',
        'recommandation',
        'score_risque',
        'niveau_avis',
        'statut',
        'date_avis',
    ];

    /**
     * Relation avec l'utilisateur qui donne l'avis
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relation avec la demande de crédit associée
     */
    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class, 'credit_application_id');
    }
}

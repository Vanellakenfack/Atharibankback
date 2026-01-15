<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\Agency;
class Guichet extends Model
{
    protected $fillable = [
        'guichet_id', 
        'libelle',
        'statut', 
    'heure_ouverture',
    'heure_fermeture'
    ];

    public function agence() {
    return $this->belongsTo(Agency::class, 'agence_id');
}
}



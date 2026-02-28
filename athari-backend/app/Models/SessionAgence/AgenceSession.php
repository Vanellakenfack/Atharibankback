<?php
namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use App\Models\Agency;

class AgenceSession extends Model
{
    protected $table = 'agence_sessions';
    protected $fillable = ['agence_id', 'date_comptable', 'statut', 'heure_ouverture', 'heure_fermeture', 'ouvert_par',    'jours_comptable_id'
];



    protected $casts = [
        'date_comptable' => 'date',
        'heure_ouverture' => 'datetime:H:i:s',
        'heure_fermeture' => 'datetime:H:i:s',
    ];
public function jourComptable()
{
    return $this->belongsTo(JourComptable::class, 'jours_comptable_id');
}

public function agence()
    {
        // Vérifiez que la clé étrangère est bien 'agence_id'
        return $this->belongsTo(Agency::class, 'agence_id');
    }
}
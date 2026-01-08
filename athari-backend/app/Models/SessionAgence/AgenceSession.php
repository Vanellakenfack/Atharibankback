<?php
namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;

class AgenceSession extends Model
{
    protected $table = 'agence_sessions';
    protected $fillable = ['agence_id', 'date_comptable', 'statut', 'heure_ouverture', 'heure_fermeture', 'ouvert_par'];
}
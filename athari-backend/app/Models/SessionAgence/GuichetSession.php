<?php
namespace App\Models\SessionAgence; 

use Illuminate\Database\Eloquent\Model;

class GuichetSession extends Model
{
    protected $table = 'guichet_sessions';
    protected $fillable = ['agence_session_id', 'guichet_id', 'statut', 'heure_ouverture', 'heure_fermeture'];
}
<?php
namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Caisse\Caisse;

class CaisseSession extends Model {
    use LogsActivity;

    protected $table = 'caisse_sessions';
    protected $fillable = ['guichet_session_id', 'caissier_id', 'solde_ouverture', 'solde_fermeture', 'statut','caisse_id', 'observations'];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['statut', 'solde_fermeture'])
            ->logOnlyDirty() // Ne logue que si une valeur change
            ->useLogName('session_caisse');
    }

    public function guichetSession()
{
    // Vérifiez bien le nom de la classe et de la clé étrangère
    return $this->belongsTo(GuichetSession::class, 'guichet_session_id');
}

public function caisse()
    {
        // On lie caisse_id de cette table à l'id de la table caisses
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

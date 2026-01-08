<?php
namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CaisseSession extends Model {
    use LogsActivity;

    protected $table = 'caisse_sessions';
    protected $fillable = ['guichet_session_id', 'caissier_id', 'solde_ouverture', 'solde_fermeture', 'statut', 'observations','code_caisse' ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['statut', 'solde_fermeture'])
            ->logOnlyDirty() // Ne logue que si une valeur change
            ->useLogName('session_caisse');
    }
}
<?php
namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Caisse\Caisse;
use App\Models\SessionAgence\GuichetSession;
use App\Models\Caisse\CaisseTransaction;

class CaisseSession extends Model {
    use LogsActivity;

    protected $table = 'caisse_sessions';
    protected $fillable = ['guichet_session_id', 'caissier_id', 'solde_ouverture', 'solde_fermeture', 'statut','caisse_id', 'observations','solde_ouverture','ouverture_om_espece',
        'ouverture_momo_espece',
        'solde_om_espece',
        'solde_momo_espece',

        // --- Nouveaux champs pour les stocks virtuels (UV) ---
        'solde_om_uv',
        'solde_momo_uv',

        // --- Champs de clôture pour l'audit ---
        'physique_om_espece',
        'physique_momo_espece',
        'date_ouverture',
        'date_fermeture'];


        protected $casts = [
        'date_ouverture' => 'datetime',
        'date_fermeture' => 'datetime',
    ];

    // --- Relations ---

    public function caissier()
    {
        return $this->belongsTo(\App\Models\User::class, 'caissier_id');
    }

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

    public function transactions()
    {
        return $this->hasMany(CaisseTransaction::class, 'session_id');
    }
}
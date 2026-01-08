<?php

namespace App\Models\SessionAgence;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JourComptable extends Model
{
    use LogsActivity;

    protected $table = 'jours_comptables';

    protected $fillable = [
        'agence_id', 
        'date_du_jour', 
        'date_precedente', 
        'statut', 
        'ouvert_at', 
        'ferme_at', 
        'execute_par'
    ];

    /**
     * Configuration des logs d'audit
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['statut', 'date_du_jour', 'ferme_at']) // Colonnes critiques à surveiller
            ->logOnlyDirty() // Enregistre seulement si une valeur a changé
            ->dontSubmitEmptyLogs() // Évite les logs inutiles
            ->useLogName('journee_comptable') // Nom de la catégorie de log
            ->setDescriptionForEvent(fn(string $eventName) => "La journée comptable a été {$eventName}");
    }
}
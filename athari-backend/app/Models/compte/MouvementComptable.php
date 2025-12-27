<?php
namespace App\Models\compte; 

use Illuminate\Database\Eloquent\Model;
use App\Models\Compte; // Si le modèle Compte principal est aussi ici

class MouvementComptable extends Model
{
    // On précise le nom de la table car il est différent du nom du dossier
    protected $table = 'mouvements_comptables';

    protected $fillable = [
        'account_id',
        'montant',
        'sens',
        'libelle',
        'date_operation'
    ];

    /**
     * Lien : Un mouvement appartient à un compte
     */
    public function compte()
    {
        return $this->belongsTo(\App\Models\compte\Compte::class, 'account_id');
    }
}
<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

class CaisseDemandeValidation extends Model
{
    // On précise le nom de la table créée précédemment
    protected $table = 'caisse_demandes_validation';

    protected $fillable = [
        'type_operation',
        'payload_data',
        'montant',
        'caissiere_id',
        'assistant_id',
        'code_validation',
        'statut',
        'motif_rejet',
        'date_approbation'
    ];

    /**
     * Casts des colonnes pour faciliter la manipulation
     */
    protected $casts = [
        'payload_data' => 'array', // Transforme le JSON en tableau PHP automatiquement
        'date_approbation' => 'datetime',
        'montant' => 'decimal:2'
    ];

    /**
     * Génère un code de validation unique (OTP métier)
     * Ce code sera communiqué par l'assistant à la caissière
     */
    public function genererCodeValidation()
    {
        // Génère un code de 6 caractères (ex: 4F2G9S)
        $code = strtoupper(Str::random(6));
        $this->code_validation = $code;
        return $code;
    }

    /**
     * RELATIONS
     */

    // La caissière qui a initié la demande
    public function caissiere()
    {
        return $this->belongsTo(User::class, 'caissiere_id');
    }

    // L'assistant qui a validé ou rejeté
    public function assistant()
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }
}
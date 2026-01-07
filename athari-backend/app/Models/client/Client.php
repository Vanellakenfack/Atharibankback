<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Agency;
use App\Models;


class Client extends Model
{
    protected $appends = ['nom_complet'];
    // Les champs que l'on peut remplir via le formulaire React
    protected $fillable = [
        'num_client', 'agency_id', 'type_client', 'telephone', 'email', 
        'adresse_ville', 'adresse_quartier', 'bp', 'pays_residence',
        'gestionnaire', 'profil', 'taxable', 'interdit_chequier', 'solde_initial','immobiliere', 'autres_biens'
    ];

    /**
     * Relation vers les détails d'une personne physique
     */
   public function physique()
{
    // On précise 'client_id' comme clé étrangère
    return $this->hasOne(ClientPhysique::class, 'client_id');
}

public function morale()
{
    return $this->hasOne(ClientMorale::class, 'client_id');
}

    public function agency(): BelongsTo
{
    // On précise 'agency_id' si vous avez suivi la migration précédente

    return $this->belongsTo(Agency::class); // Le client appartient à une agence

}
// Dans App\Models\Client.php

// Dans App\Models\Client.php

public function getNomCompletAttribute()
{
    // 1. Cas d'un client physique
    if ($this->type_client === 'physique' && $this->physique) {
        // On utilise la colonne exacte de votre migration : nom_prenoms
        return $this->physique->nom_prenoms;
    }

    // 2. Cas d'un client moral (entreprise)
    if ($this->type_client === 'morale' && $this->morale) {
        return $this->morale->raison_sociale;
    }

    // 3. Sécurité si aucune info n'est trouvée
    return "Client #" . $this->num_client;
}
}
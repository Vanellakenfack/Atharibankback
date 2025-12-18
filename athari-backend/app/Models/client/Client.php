<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Agency;
use App\Models;


class Client extends Model
{
    // Les champs que l'on peut remplir via le formulaire React
    protected $fillable = [
        'num_client', 'agency_id', 'type_client', 'telephone', 'email', 
        'adresse_ville', 'adresse_quartier', 'bp', 'pays_residence',
        'gestionnaire', 'profil', 'taxable', 'interdit_chequier', 'solde_initial'
    ];

    /**
     * Relation vers les détails d'une personne physique
     */
    public function physique(): HasOne
    {
        return $this->hasOne(ClientPhysique::class);
    }

    /**
     * Relation vers les détails d'une entreprise (personne morale)
     */
    public function morale(): HasOne
    {
        return $this->hasOne(ClientMorale::class);
    }

    public function agency(): BelongsTo
{
    // On précise 'agency_id' si vous avez suivi la migration précédente

    return $this->belongsTo(Agency::class); // Le client appartient à une agence

}
}
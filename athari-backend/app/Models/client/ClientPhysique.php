<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPhysique extends Model
{
    protected $table = 'clients_physiques'; // On précise le nom de la table

protected $fillable = [
        'client_id', 
        'nom_prenoms', 
        'sexe', 
        'date_naissance', 
        'lieu_naissance', 
        'nationalite', 
        'photo',
        'cni_numero', 
        'cni_delivrance', 
        'cni_expiration',
        'nom_pere', 
        'nom_mere', 
        'profession', 
        'employeur', 
        'situation_familiale', 
        'regime_matrimonial', 
        'nom_conjoint',
        'fonction_conjoint',
        'tel_conjoint',
        'adresse_conjoint',
        'date_naissance_conjoint'
    ];   public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected $appends = ['photo_url'];

    /**
     * Accessor pour générer l'URL complète de la photo.
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            // Retourne l'URL complète vers le dossier storage public
            return asset('storage/' . $this->photo);
        }

        // Optionnel : Retourner une image par défaut si aucune photo n'est présente
        return asset('images/default-avatar.png'); 
    }
}
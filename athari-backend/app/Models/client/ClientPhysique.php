<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPhysique extends Model
{
    protected $table = 'clients_physiques';

    protected $fillable = [
        'client_id', 'nom_prenoms', 'sexe', 'date_naissance', 
        'lieu_naissance', 'nationalite', 'photo', 'signature', 'nui',
        'niu_image', // NOUVEAU
        'cni_numero', 'cni_delivrance', 'cni_expiration', 'cni_recto', 'cni_verso',
        'nom_pere', 'nom_mere', 'nationalite_pere', 'nationalite_mere',
        'profession', 'employeur', 'situation_familiale', 'regime_matrimonial', 
        'nom_conjoint', 'date_naissance_conjoint', 'cni_conjoint',
        'profession_conjoint', 'salaire', 'tel_conjoint'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Ajout des accesseurs pour les URLs
    protected $appends = [
        'photo_url', 
        'signature_url', 
        'cni_recto_url', 
        'cni_verso_url',
        'niu_image_url' // NOUVEAU
    ];

    /**
     * Accesseur pour générer l'URL complète de la photo.
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return asset('images/default-avatar.png');
    }

    /**
     * Accesseur pour générer l'URL complète de la signature.
     */
    public function getSignatureUrlAttribute()
    {
        if ($this->signature) {
            return asset('storage/' . $this->signature);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète du recto de la CNI.
     */
    public function getCniRectoUrlAttribute()
    {
        if ($this->cni_recto) {
            return asset('storage/' . $this->cni_recto);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète du verso de la CNI.
     */
    public function getCniVersoUrlAttribute()
    {
        if ($this->cni_verso) {
            return asset('storage/' . $this->cni_verso);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète de la photocopie NUI.
     */
    public function getNiuImageUrlAttribute()
    {
        if ($this->niu_image) {
            return asset('storage/' . $this->niu_image);
        }
        return null;
    }
}
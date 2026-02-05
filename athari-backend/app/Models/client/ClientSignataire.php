<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSignataire extends Model
{
    protected $table = 'client_signataires';

    protected $fillable = [
         'client_id',
        'client_morale_id',
        'numero_signataire',
        'nom',
        'sexe',
        'ville',
        'quartier',
        'lieu_domicile',
        'lieu_dit_domicile',
        'telephone',
        'email',
        'cni',
        'cni_photo_recto',
        'cni_photo_verso',
        'nui',
        'nui_image',
        'photo',
        'signature',
        'lieu_dit_domicile_photo',
        'photo_localisation_domicile',
    ];

    public function clientMorale(): BelongsTo
    {
        return $this->belongsTo(ClientMorale::class, 'client_morale_id');
    }

    // Accesseurs pour les URLs
    protected $appends = [
        'photo_url',
        'signature_url',
        'cni_photo_recto_url',
        'cni_photo_verso_url',
        'nui_image_url',
        'lieu_dit_domicile_photo_url',
        'photo_localisation_domicile_url',
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
    public function getCniPhotoRectoUrlAttribute()
    {
        if ($this->cni_photo_recto) {
            return asset('storage/' . $this->cni_photo_recto);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète du verso de la CNI.
     */
    public function getCniPhotoVersoUrlAttribute()
    {
        if ($this->cni_photo_verso) {
            return asset('storage/' . $this->cni_photo_verso);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète de la photocopie NUI.
     */
    public function getNuiImageUrlAttribute()
    {
        if ($this->nui_image) {
            return asset('storage/' . $this->nui_image);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète de la photo lieu dit domicile.
     */
    public function getLieuDitDomicilePhotoUrlAttribute()
    {
        if ($this->lieu_dit_domicile_photo) {
            return asset('storage/' . $this->lieu_dit_domicile_photo);
        }
        return null;
    }

    /**
     * Accesseur pour générer l'URL complète de la photo localisation domicile.
     */
    public function getPhotoLocalisationDomicileUrlAttribute()
    {
        if ($this->photo_localisation_domicile) {
            return asset('storage/' . $this->photo_localisation_domicile);
        }
        return null;
    }

    /**
     * Accesseur pour obtenir le libellé du signataire.
     */
    public function getLibelleAttribute()
    {
        return "Signataire {$this->numero_signataire}";
    }
}
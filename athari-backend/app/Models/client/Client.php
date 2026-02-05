<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Agency;

class Client extends Model
{
    protected $appends = ['nom_complet'];
    
    protected $fillable = [
        'num_client', 'agency_id', 'type_client', 'telephone', 'email', 
        'adresse_ville', 'adresse_quartier', 'lieu_dit_domicile', 
        'photo_localisation_domicile', 'lieu_dit_activite','nui', 
        'photo_localisation_activite', 'ville_activite', 'quartier_activite',
        'bp', 'pays_residence', 'etat', 'solde_initial', 'immobiliere', 'autres_biens',
        // NOUVEAUX CHAMPS COMMUNS
        'liste_membres_pdf', 'demande_ouverture_pdf', 'formulaire_ouverture_pdf'
    ];

    const ETAT_PRESENT = 'present';
    const ETAT_SUPPRIME = 'supprime';

    public function scopeActifs($query)
    {
        return $query->where('etat', self::ETAT_PRESENT);
    }

    public function scopeSupprimes($query)
    {
        return $query->where('etat', self::ETAT_SUPPRIME);
    }

    public function marquerCommeSupprime()
    {
        $this->etat = self::ETAT_SUPPRIME;
        $this->save();
    }

    public function restaurer()
    {
        $this->etat = self::ETAT_PRESENT;
        $this->save();
    }

    public function physique()
    {
        return $this->hasOne(ClientPhysique::class, 'client_id');
    }

    public function morale()
    {
        return $this->hasOne(ClientMorale::class, 'client_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function getNomCompletAttribute()
    {
        if ($this->type_client === 'physique' && $this->physique) {
            return $this->physique->nom_prenoms;
        }

        if ($this->type_client === 'morale' && $this->morale) {
            return $this->morale->raison_sociale;
        }

        return "Client #" . $this->num_client;
    }

    public function getPhotoLocalisationDomicileUrlAttribute()
    {
        if ($this->photo_localisation_domicile) {
            return asset('storage/' . $this->photo_localisation_domicile);
        }
        return null;
    }

    public function getPhotoLocalisationActiviteUrlAttribute()
    {
        if ($this->photo_localisation_activite) {
            return asset('storage/' . $this->photo_localisation_activite);
        }
        return null;
    }

    // NOUVEAUX ACCESSORS
    public function getListeMembresPdfUrlAttribute()
    {
        if ($this->liste_membres_pdf) {
            return asset('storage/' . $this->liste_membres_pdf);
        }
        return null;
    }

}
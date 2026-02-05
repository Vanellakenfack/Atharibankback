<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientMorale extends Model
{
    protected $table = 'clients_morales';

    protected $fillable = [
        'client_id', 'raison_sociale', 'sigle', 'forme_juridique', 
        'type_entreprise', 'rccm', 'nui', 
        
        // Gérant 1
        'nom_gerant', 'telephone_gerant', 'photo_gerant',
        
        // Gérant 2
        'nom_gerant2', 'telephone_gerant2', 'photo_gerant2',
        
        // Documents administratifs - Images
        'extrait_rccm_image',
        'titre_patente_image',
        'niu_image',
        'statuts_image',
        'pv_agc_image',
        'attestation_non_redevance_image',
        'proces_verbal_image',
        'registre_coop_gic_image',
        'recepisse_declaration_association_image',
        
        // Documents administratifs - PDF
        'acte_designation_signataires_pdf',
        'liste_conseil_administration_pdf',
        
        // NOUVEAUX CHAMPS COMMUNS
        'liste_membres_pdf',
        
        // Plans de localisation signataires
        'plan_localisation_signataire1_image',
        'plan_localisation_signataire2_image',
        'plan_localisation_signataire3_image',
        
        // Factures eau signataires
        'facture_eau_signataire1_image',
        'facture_eau_signataire2_image',
        'facture_eau_signataire3_image',
        
        // Factures électricité signataires
        'facture_electricite_signataire1_image',
        'facture_electricite_signataire2_image',
        'facture_electricite_signataire3_image',
        
        // Localisation siège
        'plan_localisation_siege_image',
        'facture_eau_siege_image',
        'facture_electricite_siege_image'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation avec les signataires
     */
    public function signataires(): HasMany
    {
        return $this->hasMany(ClientSignataire::class, 'client_morale_id');
    }

    /**
     * Helper pour obtenir un signataire spécifique
     */
    public function signataire($numero)
    {
        return $this->signataires()->where('numero_signataire', $numero)->first();
    }

    /**
     * Helper pour obtenir le premier signataire
     */
    public function getSignataire1Attribute()
    {
        return $this->signataire('1');
    }

    /**
     * Helper pour obtenir le deuxième signataire
     */
    public function getSignataire2Attribute()
    {
        return $this->signataire('2');
    }

    /**
     * Helper pour obtenir le troisième signataire
     */
    public function getSignataire3Attribute()
    {
        return $this->signataire('3');
    }

    // Ajout des accesseurs pour les URLs
    protected $appends = [
        'photo_gerant_url', 
        'photo_gerant2_url',
        
        // URLs des documents administratifs images
        'extrait_rccm_image_url', 
        'titre_patente_image_url', 
        'niu_image_url',
        'statuts_image_url', 
        'pv_agc_image_url', 
        'attestation_non_redevance_image_url',
        'proces_verbal_image_url', 
        'registre_coop_gic_image_url', 
        'recepisse_declaration_association_image_url',
        
        // URLs des plans de localisation signataires
        'plan_localisation_signataire1_image_url', 
        'plan_localisation_signataire2_image_url', 
        'plan_localisation_signataire3_image_url',
        
        // URLs des factures signataires
        'facture_eau_signataire1_image_url', 
        'facture_eau_signataire2_image_url', 
        'facture_eau_signataire3_image_url',
        'facture_electricite_signataire1_image_url', 
        'facture_electricite_signataire2_image_url', 
        'facture_electricite_signataire3_image_url',
        
        // URLs localisation siège
        'plan_localisation_siege_image_url', 
        'facture_eau_siege_image_url', 
        'facture_electricite_siege_image_url',
        
        // URLs des PDF
        'acte_designation_signataires_pdf_url', 
        'liste_conseil_administration_pdf_url',
        
        // NOUVEAUX URLS
        'liste_membres_pdf_url', 
    ];

    /**
     * Accesseurs pour générer les URLs complètes des fichiers
     */
    
    // Gérants
    public function getPhotoGerantUrlAttribute()
    {
        if ($this->photo_gerant) {
            return asset('storage/' . $this->photo_gerant);
        }
        return asset('images/default-avatar.png');
    }

    public function getPhotoGerant2UrlAttribute()
    {
        if ($this->photo_gerant2) {
            return asset('storage/' . $this->photo_gerant2);
        }
        return null;
    }

    // Documents administratifs - Images
    public function getExtraitRccmImageUrlAttribute()
    {
        if ($this->extrait_rccm_image) {
            return asset('storage/' . $this->extrait_rccm_image);
        }
        return null;
    }

    public function getTitrePatenteImageUrlAttribute()
    {
        if ($this->titre_patente_image) {
            return asset('storage/' . $this->titre_patente_image);
        }
        return null;
    }

    public function getNiuImageUrlAttribute()
    {
        if ($this->niu_image) {
            return asset('storage/' . $this->niu_image);
        }
        return null;
    }

    public function getStatutsImageUrlAttribute()
    {
        if ($this->statuts_image) {
            return asset('storage/' . $this->statuts_image);
        }
        return null;
    }

    public function getPvAgcImageUrlAttribute()
    {
        if ($this->pv_agc_image) {
            return asset('storage/' . $this->pv_agc_image);
        }
        return null;
    }

    public function getAttestationNonRedevanceImageUrlAttribute()
    {
        if ($this->attestation_non_redevance_image) {
            return asset('storage/' . $this->attestation_non_redevance_image);
        }
        return null;
    }

    public function getProcesVerbalImageUrlAttribute()
    {
        if ($this->proces_verbal_image) {
            return asset('storage/' . $this->proces_verbal_image);
        }
        return null;
    }

    public function getRegistreCoopGicImageUrlAttribute()
    {
        if ($this->registre_coop_gic_image) {
            return asset('storage/' . $this->registre_coop_gic_image);
        }
        return null;
    }

    public function getRecepisseDeclarationAssociationImageUrlAttribute()
    {
        if ($this->recepisse_declaration_association_image) {
            return asset('storage/' . $this->recepisse_declaration_association_image);
        }
        return null;
    }

    // Plans de localisation signataires
    public function getPlanLocalisationSignataire1ImageUrlAttribute()
    {
        if ($this->plan_localisation_signataire1_image) {
            return asset('storage/' . $this->plan_localisation_signataire1_image);
        }
        return null;
    }

    public function getPlanLocalisationSignataire2ImageUrlAttribute()
    {
        if ($this->plan_localisation_signataire2_image) {
            return asset('storage/' . $this->plan_localisation_signataire2_image);
        }
        return null;
    }

    public function getPlanLocalisationSignataire3ImageUrlAttribute()
    {
        if ($this->plan_localisation_signataire3_image) {
            return asset('storage/' . $this->plan_localisation_signataire3_image);
        }
        return null;
    }

    // Factures eau signataires
    public function getFactureEauSignataire1ImageUrlAttribute()
    {
        if ($this->facture_eau_signataire1_image) {
            return asset('storage/' . $this->facture_eau_signataire1_image);
        }
        return null;
    }

    public function getFactureEauSignataire2ImageUrlAttribute()
    {
        if ($this->facture_eau_signataire2_image) {
            return asset('storage/' . $this->facture_eau_signataire2_image);
        }
        return null;
    }

    public function getFactureEauSignataire3ImageUrlAttribute()
    {
        if ($this->facture_eau_signataire3_image) {
            return asset('storage/' . $this->facture_eau_signataire3_image);
        }
        return null;
    }

    // Factures électricité signataires
    public function getFactureElectriciteSignataire1ImageUrlAttribute()
    {
        if ($this->facture_electricite_signataire1_image) {
            return asset('storage/' . $this->facture_electricite_signataire1_image);
        }
        return null;
    }

    public function getFactureElectriciteSignataire2ImageUrlAttribute()
    {
        if ($this->facture_electricite_signataire2_image) {
            return asset('storage/' . $this->facture_electricite_signataire2_image);
        }
        return null;
    }

    public function getFactureElectriciteSignataire3ImageUrlAttribute()
    {
        if ($this->facture_electricite_signataire3_image) {
            return asset('storage/' . $this->facture_electricite_signataire3_image);
        }
        return null;
    }

    // Localisation siège
    public function getPlanLocalisationSiegeImageUrlAttribute()
    {
        if ($this->plan_localisation_siege_image) {
            return asset('storage/' . $this->plan_localisation_siege_image);
        }
        return null;
    }

    public function getFactureEauSiegeImageUrlAttribute()
    {
        if ($this->facture_eau_siege_image) {
            return asset('storage/' . $this->facture_eau_siege_image);
        }
        return null;
    }

    public function getFactureElectriciteSiegeImageUrlAttribute()
    {
        if ($this->facture_electricite_siege_image) {
            return asset('storage/' . $this->facture_electricite_siege_image);
        }
        return null;
    }

    // Documents PDF
    public function getActeDesignationSignatairesPdfUrlAttribute()
    {
        if ($this->acte_designation_signataires_pdf) {
            return asset('storage/' . $this->acte_designation_signataires_pdf);
        }
        return null;
    }

    public function getListeConseilAdministrationPdfUrlAttribute()
    {
        if ($this->liste_conseil_administration_pdf) {
            return asset('storage/' . $this->liste_conseil_administration_pdf);
        }
        return null;
    }

    // NOUVEAUX PDF COMMUNS
    public function getListeMembresPdfUrlAttribute()
    {
        if ($this->liste_membres_pdf) {
            return asset('storage/' . $this->liste_membres_pdf);
        }
        return null;
    }

}
<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreMoraleClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) return false;

        return $user->hasAnyRole([
            'DG', 
            'Chef d\'Agence (CA)', 
            'Assistant Comptable (AC)',
            'Admin','Chef Comptable'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'agency_id' => 'nullable|exists:agencies,id',
            'raison_sociale' => 'nullable|string|max:255',
            'forme_juridique' => 'nullable|string',
            'type_entreprise' => 'nullable|in:entreprise,association',
            'rccm' => 'nullable|string|unique:clients_morales,rccm',
            'nui' => 'nullable|string',
            'nom_gerant' => 'nullable|string',
            'telephone_gerant' => 'nullable|string',
            'photo_gerant' => 'nullable|image|max:2048',
            
            // Gérant 2
            'nom_gerant2' => 'nullable|string',
            'telephone_gerant2' => 'nullable|string',
            'photo_gerant2' => 'nullable|image|max:2048',
            
            // SIGNATAIRE 1 - NOUVEAUX CHAMPS
            'nom_signataire' => 'nullable|string',
            'sexe_signataire' => 'nullable|in:M,F',
            'ville_signataire' => 'nullable|string',
            'quartier_signataire' => 'nullable|string',
            'lieu_domicile_signataire' => 'nullable|string',
            'lieu_dit_domicile_signataire' => 'nullable|string',
            'lieu_dit_domicile_photo_signataire' => 'nullable|image|max:2048',
            'photo_localisation_domicile_signataire' => 'nullable|image|max:2048',
            'email_signataire' => 'nullable|email',
            'cni_signataire' => 'nullable|string',
            'cni_photo_recto_signataire' => 'nullable|image|max:2048',
            'cni_photo_verso_signataire' => 'nullable|image|max:2048',
            'photo_signataire' => 'nullable|image|max:2048',
            'signature_signataire' => 'nullable|image|max:2048',
            'nui_signataire' => 'nullable|string',
            'nui_image_signataire' => 'nullable|image|max:2048',
            'telephone_signataire' => 'nullable|string',
            
            // SIGNATAIRE 2 - NOUVEAUX CHAMPS
            'nom_signataire2' => 'nullable|string',
            'sexe_signataire2' => 'nullable|in:M,F',
            'ville_signataire2' => 'nullable|string',
            'quartier_signataire2' => 'nullable|string',
            'lieu_domicile_signataire2' => 'nullable|string',
            'lieu_dit_domicile_signataire2' => 'nullable|string',
            'lieu_dit_domicile_photo_signataire2' => 'nullable|image|max:2048',
            'photo_localisation_domicile_signataire2' => 'nullable|image|max:2048',
            'email_signataire2' => 'nullable|email',
            'cni_signataire2' => 'nullable|string',
            'cni_photo_recto_signataire2' => 'nullable|image|max:2048',
            'cni_photo_verso_signataire2' => 'nullable|image|max:2048',
            'photo_signataire2' => 'nullable|image|max:2048',
            'signature_signataire2' => 'nullable|image|max:2048',
            'nui_signataire2' => 'nullable|string',
            'nui_image_signataire2' => 'nullable|image|max:2048',
            'telephone_signataire2' => 'nullable|string',
            
            // SIGNATAIRE 3 - NOUVEAUX CHAMPS
            'nom_signataire3' => 'nullable|string',
            'sexe_signataire3' => 'nullable|in:M,F',
            'ville_signataire3' => 'nullable|string',
            'quartier_signataire3' => 'nullable|string',
            'lieu_domicile_signataire3' => 'nullable|string',
            'lieu_dit_domicile_signataire3' => 'nullable|string',
            'lieu_dit_domicile_photo_signataire3' => 'nullable|image|max:2048',
            'photo_localisation_domicile_signataire3' => 'nullable|image|max:2048',
            'email_signataire3' => 'nullable|email',
            'cni_signataire3' => 'nullable|string',
            'cni_photo_recto_signataire3' => 'nullable|image|max:2048',
            'cni_photo_verso_signataire3' => 'nullable|image|max:2048',
            'photo_signataire3' => 'nullable|image|max:2048',
            'signature_signataire3' => 'nullable|image|max:2048',
            'nui_signataire3' => 'nullable|string',
            'nui_image_signataire3' => 'nullable|image|max:2048',
            'telephone_signataire3' => 'nullable|string',
            
            // Informations de contact
            'telephone' => 'nullable|string',
            'adresse_ville' => 'nullable|string',
            'adresse_quartier' => 'nullable|string',
            'email' => 'nullable|email',
            'sigle' => 'nullable|string',
            'lieu_dit_domicile' => 'nullable|string',
            'lieu_dit_activite' => 'nullable|string',
            'ville_activite' => 'nullable|string',
            'quartier_activite' => 'nullable|string',
            'bp' => 'nullable|string',
            'pays_residence' => 'nullable|string',
            'solde_initial' => 'nullable|numeric',
            'immobiliere' => 'nullable|string',
            'autres_biens' => 'nullable|string',
            
            // Localisation photos
            'photo_localisation_domicile' => 'nullable|image|max:2048',
            'photo_localisation_activite' => 'nullable|image|max:2048',
            
            // Documents administratifs - PDF
            'liste_conseil_administration_pdf' => 'nullable|mimes:pdf|max:5120',
            'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120',
            
            // NOUVEAUX CHAMPS COMMUNS
            'liste_membres_pdf' => 'nullable|mimes:pdf|max:5120',
            
            // Plans de localisation signataires
            'plan_localisation_signataire1_image' => 'nullable|image|max:2048',
            'plan_localisation_signataire2_image' => 'nullable|image|max:2048',
            'plan_localisation_signataire3_image' => 'nullable|image|max:2048',
            
            // Factures eau signataires
            'facture_eau_signataire1_image' => 'nullable|image|max:2048',
            'facture_eau_signataire2_image' => 'nullable|image|max:2048',
            'facture_eau_signataire3_image' => 'nullable|image|max:2048',
            
            // Factures électricité signataires
            'facture_electricite_signataire1_image' => 'nullable|image|max:2048',
            'facture_electricite_signataire2_image' => 'nullable|image|max:2048',
            'facture_electricite_signataire3_image' => 'nullable|image|max:2048',
            
            // Localisation siège
            'plan_localisation_siege_image' => 'nullable|image|max:2048',
            'facture_eau_siege_image' => 'nullable|image|max:2048',
            'facture_electricite_siege_image' => 'nullable|image|max:2048',
        ];

        // Règles spécifiques selon le type d'entreprise
        $typeEntreprise = $this->input('type_entreprise');
        
        if ($typeEntreprise === 'entreprise') {
            $rules['extrait_rccm_image'] = 'nullable|image|max:2048';
            $rules['titre_patente_image'] = 'nullable|image|max:2048';
            $rules['niu_image'] = 'nullable|image|max:2048';
            $rules['statuts_image'] = 'nullable|image|max:2048';
            $rules['acte_designation_signataires_pdf'] = 'nullable|mimes:pdf|max:5120';
        } elseif ($typeEntreprise === 'association') {
            $rules['pv_agc_image'] = 'nullable|image|max:2048';
            $rules['attestation_non_redevance_image'] = 'nullable|image|max:2048';
            $rules['proces_verbal_image'] = 'nullable|image|max:2048';
            $rules['registre_coop_gic_image'] = 'nullable|image|max:2048';
            $rules['recepisse_declaration_association_image'] = 'nullable|image|max:2048';
        }

        return $rules;
    }

    /**
     * Messages de validation personnalisés.
     */

}
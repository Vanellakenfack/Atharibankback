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
            'Admin'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'agency_id' => 'required|exists:agencies,id',
            'raison_sociale' => 'required|string|max:255',
            'forme_juridique' => 'required|string',
            'type_entreprise' => 'required|in:entreprise,association',
            'rccm' => 'nullable|string|unique:clients_morales,rccm',
            'nui' => 'required|string',
            'nom_gerant' => 'required|string',
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
            'telephone' => 'required|string',
            'adresse_ville' => 'required|string',
            'adresse_quartier' => 'required|string',
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
            'attestation_conformite_pdf' => 'required|mimes:pdf|max:5120',
            
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
            $rules['extrait_rccm_image'] = 'required|image|max:2048';
            $rules['titre_patente_image'] = 'required|image|max:2048';
            $rules['niu_image'] = 'required|image|max:2048';
            $rules['statuts_image'] = 'required|image|max:2048';
            $rules['acte_designation_signataires_pdf'] = 'required|mimes:pdf|max:5120';
        } elseif ($typeEntreprise === 'association') {
            $rules['pv_agc_image'] = 'required|image|max:2048';
            $rules['attestation_non_redevance_image'] = 'required|image|max:2048';
            $rules['proces_verbal_image'] = 'required|image|max:2048';
            $rules['registre_coop_gic_image'] = 'required|image|max:2048';
            $rules['recepisse_declaration_association_image'] = 'required|image|max:2048';
        }

        return $rules;
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'raison_sociale.required' => 'La raison sociale est obligatoire.',
            'rccm.unique' => 'Ce numéro RCCM est déjà utilisé par une autre entreprise.',
            'nui.required' => 'Le numéro NUI est obligatoire.',
            'nui.unique' => 'Ce NUI est déjà utilisé par une autre entreprise.',
            
            // Messages pour les fichiers
            'photo_localisation_domicile.max' => 'La photo de localisation du domicile ne doit pas dépasser 2 Mo.',
            'photo_localisation_activite.max' => 'La photo de localisation de l\'activité ne doit pas dépasser 2 Mo.',
            
            'extrait_rccm_image.required' => 'L\'extrait RCCM est obligatoire pour les entreprises.',
            'extrait_rccm_image.max' => 'L\'extrait RCCM ne doit pas dépasser 2 Mo.',
            'titre_patente_image.required' => 'Le titre de patente est obligatoire pour les entreprises.',
            'titre_patente_image.max' => 'Le titre de patente ne doit pas dépasser 2 Mo.',
            'niu_image.required' => 'La photocopie NUI est obligatoire pour les entreprises.',
            'niu_image.max' => 'La photocopie NUI ne doit pas dépasser 2 Mo.',
            'statuts_image.required' => 'La photocopie des statuts est obligatoire pour les entreprises.',
            'statuts_image.max' => 'La photocopie des statuts ne doit pas dépasser 2 Mo.',
            
            'pv_agc_image.required' => 'Le PV AGC est obligatoire pour les associations.',
            'pv_agc_image.max' => 'Le PV AGC ne doit pas dépasser 2 Mo.',
            'attestation_non_redevance_image.required' => 'L\'attestation de non redevance est obligatoire pour les associations.',
            'attestation_non_redevance_image.max' => 'L\'attestation de non redevance ne doit pas dépasser 2 Mo.',
            'proces_verbal_image.required' => 'Le procès-verbal est obligatoire pour les associations.',
            'proces_verbal_image.max' => 'Le procès-verbal ne doit pas dépasser 2 Mo.',
            'registre_coop_gic_image.required' => 'Le registre COOP-GIC est obligatoire pour les associations.',
            'registre_coop_gic_image.max' => 'Le registre COOP-GIC ne doit pas dépasser 2 Mo.',
            'recepisse_declaration_association_image.required' => 'Le récépissé de déclaration est obligatoire pour les associations.',
            'recepisse_declaration_association_image.max' => 'Le récépissé de déclaration ne doit pas dépasser 2 Mo.',
            
            // PDF
            'acte_designation_signataires_pdf.required' => 'L\'acte de désignation des signataires est obligatoire pour les entreprises.',
            'acte_designation_signataires_pdf.max' => 'L\'acte de désignation ne doit pas dépasser 5 Mo.',
            'acte_designation_signataires_pdf.mimes' => 'L\'acte de désignation doit être un fichier PDF.',
            'liste_conseil_administration_pdf.max' => 'La liste du conseil d\'administration ne doit pas dépasser 5 Mo.',
            'liste_conseil_administration_pdf.mimes' => 'La liste du conseil d\'administration doit être un fichier PDF.',
            'attestation_conformite_pdf.required' => 'L\'attestation de conformité est obligatoire.',
            'attestation_conformite_pdf.max' => 'L\'attestation de conformité ne doit pas dépasser 5 Mo.',
            'attestation_conformite_pdf.mimes' => 'L\'attestation de conformité doit être un fichier PDF.',
            
            // NOUVEAUX PDF COMMUNS
            'liste_membres_pdf.max' => 'La liste des membres ne doit pas dépasser 5 Mo.',
            'liste_membres_pdf.mimes' => 'La liste des membres doit être un fichier PDF.',
            
            // Plans
            'plan_localisation_signataire1_image.max' => 'Le plan de localisation du signataire 1 ne doit pas dépasser 2 Mo.',
            'plan_localisation_signataire2_image.max' => 'Le plan de localisation du signataire 2 ne doit pas dépasser 2 Mo.',
            'plan_localisation_signataire3_image.max' => 'Le plan de localisation du signataire 3 ne doit pas dépasser 2 Mo.',
            'plan_localisation_siege_image.max' => 'Le plan de localisation du siège ne doit pas dépasser 2 Mo.',
            
            // Factures
            'facture_eau_signataire1_image.max' => 'La facture d\'eau du signataire 1 ne doit pas dépasser 2 Mo.',
            'facture_electricite_signataire1_image.max' => 'La facture d\'électricité du signataire 1 ne doit pas dépasser 2 Mo.',
            'facture_eau_siege_image.max' => 'La facture d\'eau du siège ne doit pas dépasser 2 Mo.',
            'facture_electricite_siege_image.max' => 'La facture d\'électricité du siège ne doit pas dépasser 2 Mo.',
        ];
    }
}
<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour créer un compte (étape finale)
 */
class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('ouvrir compte');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Étape 1
            'etape1' => 'required|array',
            'etape1.client_id' => 'required|exists:clients,id',
            'etape1.type_compte_id' => 'required|exists:types_comptes,id',
            'etape1.code_type_compte' => 'required|string|size:2',
            'etape1.devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'etape1.gestionnaire_nom' => 'required|string|max:255',
            'etape1.gestionnaire_prenom' => 'required|string|max:255',
            'etape1.gestionnaire_code' => 'required|string|max:20',
            'etape1.rubriques_mata' => 'nullable|array',
            'etape1.rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'etape1.duree_blocage_mois' => 'nullable|integer|between:3,12',

            // Étape 2
            'etape2' => 'required|array',
            'etape2.chapitre_comptable_id' => 'required|exists:chapitres_comptables,id',

            // Étape 3 - Mandataires
            'etape3' => 'required|array',
            'etape3.mandataire_1' => 'required|array',
            'etape3.mandataire_1.sexe' => 'required|in:masculin,feminin',
            'etape3.mandataire_1.nom' => 'required|string|max:255',
            'etape3.mandataire_1.prenom' => 'required|string|max:255',
            'etape3.mandataire_1.date_naissance' => 'required|date|before:today',
            'etape3.mandataire_1.lieu_naissance' => 'required|string|max:255',
            'etape3.mandataire_1.telephone' => 'required|string|max:20',
            'etape3.mandataire_1.adresse' => 'required|string',
            'etape3.mandataire_1.nationalite' => 'required|string|max:255',
            'etape3.mandataire_1.profession' => 'required|string|max:255',
            'etape3.mandataire_1.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'etape3.mandataire_1.numero_cni' => 'required|string|max:50',
            'etape3.mandataire_1.situation_familiale' => 'required|in:marie,celibataire,autres',
            'etape3.mandataire_1.nom_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'etape3.mandataire_1.date_naissance_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|date',
            'etape3.mandataire_1.lieu_naissance_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'etape3.mandataire_1.cni_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:50',
            'etape3.mandataire_1.signature_path' => 'nullable|string',
            
            'etape3.mandataire_2' => 'nullable|array',
            'etape3.mandataire_2.sexe' => 'required_with:etape3.mandataire_2|in:masculin,feminin',
            'etape3.mandataire_2.nom' => 'required_with:etape3.mandataire_2|string|max:255',
            'etape3.mandataire_2.prenom' => 'required_with:etape3.mandataire_2|string|max:255',
            'etape3.mandataire_2.date_naissance' => 'required_with:etape3.mandataire_2|date|before:today',
            'etape3.mandataire_2.lieu_naissance' => 'required_with:etape3.mandataire_2|string|max:255',
            'etape3.mandataire_2.telephone' => 'required_with:etape3.mandataire_2|string|max:20',
            'etape3.mandataire_2.adresse' => 'required_with:etape3.mandataire_2|string',
            'etape3.mandataire_2.nationalite' => 'required_with:etape3.mandataire_2|string|max:255',
            'etape3.mandataire_2.profession' => 'required_with:etape3.mandataire_2|string|max:255',
            'etape3.mandataire_2.numero_cni' => 'required_with:etape3.mandataire_2|string|max:50',
            'etape3.mandataire_2.situation_familiale' => 'required_with:etape3.mandataire_2|in:marie,celibataire,autres',

            // Étape 4
            'etape4' => 'required|array',
            'etape4.notice_acceptee' => 'required|boolean|accepted',
            
            // Documents et signature (fichiers)
            'signature' => 'nullable|file|mimes:png,jpg,jpeg|max:5120',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'types_documents' => 'nullable|array',
            'types_documents.*' => 'required_with:documents|string',
            'descriptions_documents' => 'nullable|array',
            'descriptions_documents.*' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'etape1.required' => 'Les données de l\'étape 1 sont obligatoires.',
            'etape2.required' => 'Les données de l\'étape 2 sont obligatoires.',
            'etape3.required' => 'Les données de l\'étape 3 sont obligatoires.',
            'etape4.required' => 'Les données de l\'étape 4 sont obligatoires.',
            'etape4.notice_acceptee.accepted' => 'Vous devez accepter la notice d\'information.',
            'signature.mimes' => 'La signature doit être au format PNG, JPG ou JPEG.',
            'signature.max' => 'La signature ne doit pas dépasser 5 Mo.',
            'documents.*.mimes' => 'Les documents doivent être au format PDF, JPG, JPEG ou PNG.',
            'documents.*.max' => 'Chaque document ne doit pas dépasser 10 Mo.',
        ];
    }
}
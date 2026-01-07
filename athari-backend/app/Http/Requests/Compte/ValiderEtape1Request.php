<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour valider l'étape 1 (Informations compte et client)
 */
class ValiderEtape1Request extends FormRequest
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
            'client_id' => 'required|exists:clients,id',
            'type_compte_id' => 'required|exists:types_comptes,id',
            'code_type_compte' => 'required|string|size:2',
            'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'gestionnaire_nom' => 'required|string|max:255',
            'gestionnaire_prenom' => 'required|string|max:255',
            'gestionnaire_code' => 'required|string|max:20',
            'rubriques_mata' => 'nullable|array',
            'rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'duree_blocage_mois' => 'nullable|integer|between:3,12',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'type_compte_id.required' => 'Le type de compte est obligatoire.',
            'type_compte_id.exists' => 'Le type de compte sélectionné n\'existe pas.',
            'code_type_compte.required' => 'Le code du type de compte est obligatoire.',
            'code_type_compte.size' => 'Le code du type de compte doit contenir exactement 2 caractères.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être : FCFA, EURO, DOLLAR ou POUND.',
            'gestionnaire_nom.required' => 'Le nom du gestionnaire est obligatoire.',
            'gestionnaire_prenom.required' => 'Le prénom du gestionnaire est obligatoire.',
            'gestionnaire_code.required' => 'Le code du gestionnaire est obligatoire.',
            'rubriques_mata.*.in' => 'Les rubriques MATA doivent être parmi : SANTE, BUSINESS, FETE, FOURNITURE, IMMO, SCOLARITE.',
            'duree_blocage_mois.between' => 'La durée de blocage doit être entre 3 et 12 mois.',
        ];
    }
}
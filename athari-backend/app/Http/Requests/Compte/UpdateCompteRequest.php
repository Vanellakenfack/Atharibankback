<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour mettre à jour un compte
 */
class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls ceux qui peuvent ouvrir ou gérer l'agence peuvent modifier
        return $this->user()->hasAnyPermission([
            'ouvrir compte',
            'gestion agence'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'devise' => 'sometimes|in:FCFA,EURO,DOLLAR,POUND',
            'gestionnaire_nom' => 'sometimes|string|max:255',
            'gestionnaire_prenom' => 'sometimes|string|max:255',
            'gestionnaire_code' => 'sometimes|string|max:20',
            'rubriques_mata' => 'sometimes|array',
            'rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'duree_blocage_mois' => 'sometimes|integer|between:3,12',
            'statut' => 'sometimes|in:actif,inactif,suspendu',
            'observations' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'devise.in' => 'La devise doit être : FCFA, EURO, DOLLAR ou POUND.',
            'statut.in' => 'Le statut doit être : actif, inactif ou suspendu.',
            'duree_blocage_mois.between' => 'La durée de blocage doit être entre 3 et 12 mois.',
        ];
    }
}
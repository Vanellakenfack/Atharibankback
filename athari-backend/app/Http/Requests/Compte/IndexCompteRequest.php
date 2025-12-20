<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour lister les comptes
 */
class IndexCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Accessible par ceux qui peuvent ouvrir ou gérer les comptes
        return $this->user()->hasAnyPermission([
            'ouvrir compte',
            'cloturer compte',
            'supprimer compte',
            'gestion agence',
            'consulter logs'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'sometimes|integer|exists:clients,id',
            'statut' => 'sometimes|string|in:actif,inactif,cloture,suspendu',
            'devise' => 'sometimes|string|in:FCFA,EURO,DOLLAR,POUND',
            'type_compte_id' => 'sometimes|integer|exists:types_comptes,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
            'statut.in' => 'Le statut doit être : actif, inactif, cloture ou suspendu.',
            'devise.in' => 'La devise doit être : FCFA, EURO, DOLLAR ou POUND.',
            'type_compte_id.exists' => 'Le type de compte spécifié n\'existe pas.',
        ];
    }
}
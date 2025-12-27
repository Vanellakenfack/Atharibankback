<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OuvrirContratRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        // On retourne true pour l'instant (à lier plus tard à tes permissions/auth)
        return true;
    }

    /**
     * Définit les règles de validation.
     */
    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'exists:comptes,id', // Vérifie que le compte existe en base
            ],
            'dat_type_id' => [
                'required',
                'exists:dat_types,id', // Vérifie que le type de DAT existe
            ],
           
        'montant'     => 'required|numeric|min:0',
        'mode_versement' => 'nullable|string'
        ];
    }

    /**
     * Personnalisation des messages d'erreur (Optionnel mais Pro).
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Le numéro de compte est obligatoire.',
            'account_id.exists'   => 'Ce compte bancaire n\'existe pas.',
            'dat_type_id.required' => 'Veuillez sélectionner un type de produit DAT.',
            'dat_type_id.exists'   => 'Le type de produit sélectionné est invalide.',
        ];
    }

    /**
     * Gère l'échec de validation pour retourner du JSON propre (indispensable pour API).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'statut' => 'error',
            'errors' => $validator->errors()
        ], 422));
    }
}
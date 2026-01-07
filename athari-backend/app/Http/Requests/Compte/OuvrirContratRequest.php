<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OuvrirContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation adaptées à la logique Turbobank
     */
    public function rules(): array
    {
        return [
            // Comptes impliqués
            'account_id'               => 'required|exists:comptes,id', // Le compte DAT interne
            'client_source_account_id' => 'required|exists:comptes,id', // Compte source du client
            'dat_type_id'              => 'required|exists:dat_types,id',
            
            // Destinations des fonds (Optionnels, sinon logique par défaut dans le service)
            'destination_interet_id'   => 'nullable|exists:comptes,id',
            'destination_capital_id'   => 'nullable|exists:comptes,id',

            // Paramètres financiers
            'montant_initial'          => 'required|numeric|min:1',
            'taux_interet_annuel'      => 'required|numeric|between:0,1', // Ex: 0.045
            'taux_penalite_anticipe'   => 'nullable|numeric|between:0,1',
            'duree_mois'               => 'required|integer|min:1',
            
            // Options de calcul
            'periodicite'              => 'required|in:M,T,S,A,E', // Mensuel, Trimestriel... Échéance
            'is_jours_reels'           => 'required|boolean',
            'is_precompte'             => 'required|boolean',

            // Dates
            'date_execution'           => 'required|date',
            'date_valeur'              => 'required|date',
            'date_maturite'            => 'required|date|after:date_valeur',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'account_id.required'           => 'Le compte de stockage DAT est requis.',
            'client_source_account_id.required' => 'Le compte source du client est obligatoire.',
            'date_maturite.after'           => 'La date d\'échéance doit être postérieure à la date de valeur.',
            'periodicite.in'                => 'La périodicité sélectionnée est invalide (M, T, S, A ou E).',
            'taux_interet_annuel.between'   => 'Le taux doit être exprimé en décimal (ex: 0.05 pour 5%).',
            'montant_initial.min'           => 'Le montant du DAT doit être supérieur à 0.',
        ];
    }

    /**
     * Retourne une erreur JSON propre en cas d'échec
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'statut' => 'error',
            'message' => 'Erreur de validation des données du DAT',
            'errors' => $validator->errors()
        ], 422));
    }
}
<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour obtenir les comptes d'un client
 */
class GetComptesClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyPermission([
            'ouvrir compte',
            'gestion des clients',
            'consulter logs',
            'gestion agence',
            'consulter compte',
            'saisir depot retrait',  'saisi dat',
            'edition du journal des od',

        ]);

        return $user->hasAnyRole([
        'DG', 
        'Chef d\Agence (CA)', 
        'Assistant Comptable (AC)',
        'Admin','Chef Comptable'
    ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
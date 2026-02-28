<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour afficher un compte
 */
class ShowCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyPermission([
            'ouvrir compte',
            'cloturer compte',
            'supprimer compte',
            'gestion agence',
            'consulter logs',
            'consulter comptes',
            'saisir depot retrait',
            'saisi dat',
            'edition du journal des od'

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
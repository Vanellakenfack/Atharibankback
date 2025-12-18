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
          
    // Autorisation : Uniquement le Chef d'Agence ('chef_agence') ou le DG ('dg')
    // Adaptez les chaînes de caractères selon vos noms de rôles en base de données
         $user = $this->user();

    if (!$user) return false;

    // Utilisation de hasAnyRole (méthode Spatie)
    return $user->hasAnyRole([
        'DG', 
        'Chef d\Agence (CA)', 
        'Assistant Comptable (AC)',
        'Admin'
    ]);

    // Autorisation : Uniquement le Chef d'Agence ('chef_agence') ou le DG ('dg')
    // Adaptez les chaînes de caractères selon vos noms de rôles en base de données
    return $user->role === 'Chef d\Agence (CA)' || $user->role === 'DG'|| $user->role === 'Assistant Comptable (AC)';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'agency_id'       => 'required|exists:agencies,id',
        'type_client'     => 'required|in:morale',
        'raison_sociale'  => 'required|string|max:255',
        'forme_juridique' => 'required|string',
        'rccm'            => 'required|string|unique:clients_morales,rccm',
        'nui'             => 'required|string|unique:clients_morales,nui',
        'nom_gerant'      => 'required|string',
        'telephone'       => 'required|string',
        'adresse_ville'   => 'required|string',
        'raison_sociale' => 'required|string|max:255',
        'sigle'          => 'nullable|string|max:50',
        'nom_gerant'     => 'required|string|max:255',
        'adresse_quartier' => 'required|string', // <--- Ajoutez cette ligne

        
            //
        ];
    }
}

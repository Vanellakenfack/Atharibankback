<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StorePhysiqueClientRequest extends FormRequest
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
    
    }

    /**
     * Get the validation rules that apply to the request.
     *
     */
    public function rules(): array
    {
         return [
        'agency_id'      => 'required|exists:agencies,id',
        'type_client'    => 'required|in:physique',
        'nom_prenoms'    => 'required|string|max:255',
        'date_naissance' => 'required|date',
        'cni_numero'     => 'required|string|unique:clients_physiques,cni_numero',
        'telephone'      => 'required|string',
        'adresse_ville'  => 'required|string',
        'adresse_quartier' => 'required|string', // <--- Ajoutez cette ligne
        // Ajoutez les autres champs optionnels ici...


        'nom_prenoms'         => 'required|string|max:255',
        'sexe'                => 'required|in:M,F',
        'date_naissance'      => 'required|date',
        'lieu_naissance'      => 'nullable|string', // Ajoutez ceci
        'nationalite'         => 'nullable|string', // Ajoutez ceci
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB

        'cni_numero'          => 'required|string|unique:clients_physiques,cni_numero',
        'cni_delivrance'      => 'nullable|date',
        'cni_expiration'      => 'nullable|date',
         'nom_pere'        => 'nullable|string',
       'nationalite_pere'        => 'nullable|string',
        'nom_mere'        => 'nullable|string',
        'nationalit_mere'        => 'nullable|string',

        'profession'          => 'nullable|string', // Ajoutez ceci
        'employeur'           => 'nullable|string',
        'nom_conjoint'        => 'nullable|string',
        'situation_familiale' => 'nullable|string', // Ajoutez ceci
        'nom_conjoint' => 'nullable|string', // Ajoutez ceci
        'date_naissance_conjoint' => 'nullable|date', // Ajoutez ceci
        'cni_conjoint' => 'nullable|unique:clients_physiques,cni_conjoint',
        'profession_conjoint' => 'nullable|string', // Ajoutez ceci
'salaire' => 'nullable|decimal:0,2',
        'tel_conjoint' => 'nullable|string', // Ajoutez ceci


        
    



    ];
    }
}


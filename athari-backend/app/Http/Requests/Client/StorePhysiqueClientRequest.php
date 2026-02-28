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
        $user = $this->user();

        if (!$user) return false;

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
        return [
            'agency_id'      => 'nullable|exists:agencies,id',
            'type_client'    => 'nullable|in:physique',
            'nom_prenoms'    => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date',
            'cni_numero'     => 'nullable|string|unique:clients_physiques,cni_numero',
            'telephone'      => 'nullable|string',
            'email'          => 'nullable|email',
            'adresse_ville'  => 'nullable|string',
            'adresse_quartier' => 'nullable|string',
            'lieu_dit_domicile' => 'nullable|string|max:500',
            'lieu_dit_activite' => 'nullable|string|max:500',
            'ville_activite' => 'nullable|string|max:255',
            'quartier_activite' => 'nullable|string|max:255',

            // Champs personnels
            'nom_prenoms'         => 'nullable|string|max:255',
            'sexe'                => 'nullable|in:M,F',
            'date_naissance'      => 'nullable|date',
            'lieu_naissance'      => 'nullable|string',
            'nationalite'         => 'nullable|string',
            'nui'                 => 'nullable|string|unique:clients_physiques,nui', 
            'niu_image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'photo'               => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            'photo_localisation_domicile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            'photo_localisation_activite' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 

            // Pièces d'identité
            'cni_numero'          => 'nullable|string|unique:clients_physiques,cni_numero',
            'cni_delivrance'      => 'nullable|date',
            'cni_expiration'      => 'nullable|date',

            // Filiation
            'nom_pere'            => 'nullable|string',
            'nationalite_pere'    => 'nullable|string',
            'nom_mere'            => 'nullable|string',
            'nationalite_mere'    => 'nullable|string',

            // Profession
            'profession'          => 'nullable|string',
            'employeur'           => 'nullable|string',

            // Situation familiale
            'situation_familiale' => 'nullable|string',
            'nom_conjoint'        => 'nullable|string',
            'date_naissance_conjoint' => 'nullable|date',
            'cni_conjoint'        => 'nullable|unique:clients_physiques,cni_conjoint',
            'profession_conjoint' => 'nullable|string',
            'salaire'             => 'nullable|decimal:0,2',
            'tel_conjoint'        => 'nullable|string',

            // Autres
            'bp'                  => 'nullable|string',
            'pays_residence'      => 'nullable|string|default:Cameroun',
            'solde_initial'       => 'nullable|decimal:0,2|min:0',
            'immobiliere'         => 'nullable|string',
            'autres_biens'        => 'nullable|string',

        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'nom_prenoms.nullable' => 'Le nom complet est obligatoire.',
            'cni_numero.unique' => 'Ce numéro de CNI est déjà utilisé par un autre client.',
            'nui.unique' => 'Ce NUI est déjà utilisé par un autre client.',
            'niu_image.max' => 'La photocopie NUI ne doit pas dépasser 2 Mo.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'signature.max' => 'La signature ne doit pas dépasser 2 Mo.',
            'photo_localisation_domicile.max' => 'La photo de localisation du domicile ne doit pas dépasser 2 Mo.',
            'photo_localisation_activite.max' => 'La photo de localisation de l\'activité ne doit pas dépasser 2 Mo.',
        ];
    }
}
<?php

namespace App\Http\Requests\Plancomptable;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

    if (!$user) return false;

    // Utilisation de hasAnyRole (méthode Spatie)
    return $user->hasAnyRole([
        'DG', 
        'Chef d\Agence (CA)', 
        'Assistant Comptable (AC)',
        'Admin','Chef Comptable'
    ]);

    // Autorisation : Uniquement le Chef d'Agence ('chef_agence') ou le DG ('dg')
    // Adaptez les chaînes de caractères selon vos noms de rôles en base de données
    return $user->role === 'Chef d\Agence (CA)' || $user->role === 'DG'|| $user->role === 'Assistant Comptable (AC)'|| $user->role === 'Chef Comptable';
    

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
         public function rules(): array {
        return [
            'code'        => 'required|unique:categories_comptables',
            'libelle'     => 'required|string',
            'type_compte' => 'required|in:ACTIF,PASSIF,CHARGE,PRODUIT', // On impose le type ici
            'niveau'      => 'required|integer',
            'parent_id'   => 'nullable|exists:categories_comptables,id',
        ];
    }
}

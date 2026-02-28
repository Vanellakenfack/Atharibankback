<?php

namespace App\Http\Requests\Plancomptable;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanComptableRequest extends FormRequest
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
      public function rules(): array 
{
    // On récupère l'ID soit depuis l'objet injecté, soit directement depuis le paramètre d'URL
    $planComptableId = $this->route('plan_comptable') instanceof \App\Models\chapitre\PlanComptable 
        ? $this->route('plan_comptable')->id 
        : $this->route('id') ?? $this->route('plan_comptable');

    return [
        'categorie_id' => 'required|exists:categories_comptables,id',
        'code'         => 'required|unique:plan_comptable,code,' . $planComptableId,
        'libelle'      => 'required|string',
        'nature_solde' => 'required|in:DEBIT,CREDIT,MIXTE',
        'est_actif'    => 'sometimes|boolean'
    ];

}
}

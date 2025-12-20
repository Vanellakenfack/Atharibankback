<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanComptableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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

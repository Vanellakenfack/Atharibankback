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
      public function rules(): array {
    return [
        'categorie_id' => 'required|exists:categories_comptables,id',
        'code'         => 'required|unique:plan_comptable',
        'libelle'      => 'required|string',
        'nature_solde' => 'required|in:DEBIT,CREDIT,INDETERMINE', // On définit le comportement ici
    ];
}
}

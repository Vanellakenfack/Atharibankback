<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            ];
        }
}

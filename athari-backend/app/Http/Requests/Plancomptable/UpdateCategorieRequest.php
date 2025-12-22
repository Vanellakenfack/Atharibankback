<?php

namespace App\Http\Requests\Plancomptable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategorieRequest extends FormRequest
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
    // On récupère l'ID depuis la route (ex: categories/{id})
    $categoryId = $this->route('category') ?? $this->route('id');

    return [
        'code'        => 'required|unique:categories_comptables,code,' . $categoryId,
        'libelle'     => 'required|string',
        'type_compte' => 'required|in:ACTIF,PASSIF,CHARGE,PRODUIT',
        'niveau'      => 'required|integer',
        'parent_id'   => 'nullable|exists:categories_comptables,id',
    ];
}
}

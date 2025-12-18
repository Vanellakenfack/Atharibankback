<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->hasAnyRole(['Chef d\'Agence (CA)', 'Assistant Juridique (AJ)', 'Chef Comptable', 'DG', 'Admin']);
    }

    public function rules(): array
    {
        return [
            'validation_type' => ['required', Rule::in(['ca', 'aj'])],
            'approved' => ['required', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'validation_type.required' => 'Le type de validation est obligatoire.',
            'validation_type.in' => 'Le type de validation doit être "ca" ou "aj".',
            'approved.required' => 'La décision de validation est obligatoire.',
        ];
    }
}
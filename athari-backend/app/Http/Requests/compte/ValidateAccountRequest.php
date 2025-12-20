<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $action = $this->input('action');

        return match ($action) {
            'validate_ca' => $user->hasRole('Chef d\'Agence (CA)'),
            'validate_aj' => $user->hasRole('Assistant Juridique (AJ)'),
            'reject' => $user->hasAnyRole(['Chef d\'Agence (CA)', 'Assistant Juridique (AJ)', 'Chef Comptable']),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['validate_ca', 'validate_aj', 'reject'])],
            'motif' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'L\'action de validation est obligatoire.',
            'action.in' => 'L\'action spécifiée n\'est pas valide.',
            'motif.required_if' => 'Le motif de rejet est obligatoire.',
        ];
    }
}
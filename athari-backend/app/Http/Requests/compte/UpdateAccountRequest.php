<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ouvrir compte');
    }

    public function rules(): array
    {
        return [
            'taxable' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'minimum_compte' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
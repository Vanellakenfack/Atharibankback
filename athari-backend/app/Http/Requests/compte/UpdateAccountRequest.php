<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ouvrir compte') || $this->user()->can('gestion des clients');
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['pending', 'pending_validation', 'active', 'blocked', 'dormant'])],
            'debit_blocked' => ['sometimes', 'boolean'],
            'credit_blocked' => ['sometimes', 'boolean'],
            'blocking_reason' => ['nullable', 'string', 'max:500'],
            'blocking_end_date' => ['nullable', 'date', 'after:today'],
            'collector_id' => ['nullable', 'exists:users,id'],
            'overdraft_limit' => ['sometimes', 'numeric', 'min:0'],
            'minimum_balance_amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
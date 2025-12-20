<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
  public function authorize(): bool
{
    // L'action n'est autorisée que si l'utilisateur a la permission
    return $this->user() && $this->user()->hasRole('DG');
}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:agencies,code|max:20',
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
        ];
    }
}

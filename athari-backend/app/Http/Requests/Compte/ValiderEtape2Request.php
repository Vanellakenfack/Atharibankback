<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour valider l'étape 2 (Chapitre comptable)
 */
class ValiderEtape2Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('ouvrir compte');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'chapitre_comptable_id' => 'required|exists:plan_comptable,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'chapitre_comptable_id.required' => 'Le chapitre comptable est obligatoire.',
            'chapitre_comptable_id.exists' => 'Le chapitre comptable sélectionné n\'existe pas.',
        ];
    }
}

<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ouvrir compte');
    }

    public function rules(): array
    {
        return [
            // Étape 1: Informations du client
            'client_id' => ['required', 'exists:clients,id'],
            
            // Étape 2: Type de compte
            'account_type_id' => ['required', 'exists:account_types,id'],
            'agency_id' => ['required', 'exists:agencies,id'],
            'collector_id' => ['nullable', 'exists:users,id'],
            
            // Étape 3: Mandataires
            'mandataires' => ['nullable', 'array', 'max:2'],
            'mandataires.*.gender' => ['required_with:mandataires', Rule::in(['masculin', 'feminin'])],
            'mandataires.*.last_name' => ['required_with:mandataires', 'string', 'max:100'],
            'mandataires.*.first_name' => ['required_with:mandataires', 'string', 'max:100'],
            'mandataires.*.birth_date' => ['required_with:mandataires', 'date', 'before:today'],
            'mandataires.*.birth_place' => ['required_with:mandataires', 'string', 'max:100'],
            'mandataires.*.phone' => ['required_with:mandataires', 'string', 'max:20'],
            'mandataires.*.address' => ['nullable', 'string', 'max:255'],
            'mandataires.*.nationality' => ['nullable', 'string', 'max:50'],
            'mandataires.*.profession' => ['nullable', 'string', 'max:100'],
            'mandataires.*.mother_maiden_name' => ['nullable', 'string', 'max:100'],
            'mandataires.*.cni_number' => ['required_with:mandataires', 'string', 'max:50'],
            'mandataires.*.cni_issue_date' => ['nullable', 'date'],
            'mandataires.*.cni_expiry_date' => ['nullable', 'date', 'after:today'],
            'mandataires.*.marital_status' => ['required_with:mandataires', Rule::in(['celibataire', 'marie', 'divorce', 'veuf', 'autres'])],
            'mandataires.*.spouse_name' => ['nullable', 'string', 'max:100'],
            'mandataires.*.spouse_birth_date' => ['nullable', 'date'],
            'mandataires.*.spouse_birth_place' => ['nullable', 'string', 'max:100'],
            'mandataires.*.spouse_cni' => ['nullable', 'string', 'max:50'],
            'mandataires.*.signature' => ['nullable', 'string'],
            
            // Étape 4: Documents
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents', 'string'],
            'documents.*.file' => ['required_with:documents', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            
            // Notice et signature
            'notice_accepted' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'account_type_id.required' => 'Le type de compte est obligatoire.',
            'account_type_id.exists' => 'Le type de compte sélectionné n\'existe pas.',
            'agency_id.required' => 'L\'agence est obligatoire.',
            'notice_accepted.accepted' => 'Vous devez accepter la notice d\'engagement.',
            'mandataires.*.birth_date.before' => 'La date de naissance du mandataire doit être antérieure à aujourd\'hui.',
            'documents.*.file.max' => 'La taille du fichier ne doit pas dépasser 8 Mo.',
        ];
    }
}
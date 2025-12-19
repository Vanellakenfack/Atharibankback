<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ouvrir compte');
    }

    public function rules(): array
    {
        return [
            // Informations principales
            'client_id' => ['required', 'exists:clients,id'],
            'account_type_id' => ['required', 'exists:account_types,id'],
            'agency_id' => ['required', 'exists:agencies,id'],
            'taxable' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Mandataire 1
            'mandataire_1' => ['sometimes', 'array'],
            'mandataire_1.sexe' => ['required_with:mandataire_1', Rule::in(['M', 'F'])],
            'mandataire_1.nom' => ['required_with:mandataire_1', 'string', 'max:100'],
            'mandataire_1.prenoms' => ['required_with:mandataire_1', 'string', 'max:150'],
            'mandataire_1.date_naissance' => ['required_with:mandataire_1', 'date', 'before:today'],
            'mandataire_1.lieu_naissance' => ['required_with:mandataire_1', 'string', 'max:100'],
            'mandataire_1.telephone' => ['required_with:mandataire_1', 'string', 'max:20'],
            'mandataire_1.adresse' => ['required_with:mandataire_1', 'string', 'max:255'],
            'mandataire_1.nationalite' => ['required_with:mandataire_1', 'string', 'max:50'],
            'mandataire_1.profession' => ['required_with:mandataire_1', 'string', 'max:100'],
            'mandataire_1.nom_jeune_fille_mere' => ['nullable', 'string', 'max:100'],
            'mandataire_1.numero_cni' => ['required_with:mandataire_1', 'string', 'max:50', 'unique:account_mandataries,numero_cni'],
            'mandataire_1.cni_delivrance' => ['nullable', 'date'],
            'mandataire_1.cni_expiration' => ['nullable', 'date', 'after:today'],
            'mandataire_1.situation_familiale' => ['required_with:mandataire_1', Rule::in(['celibataire', 'marie', 'divorce', 'veuf', 'autre'])],
            'mandataire_1.nom_conjoint' => ['nullable', 'string', 'max:150'],
            'mandataire_1.date_naissance_conjoint' => ['nullable', 'date'],
            'mandataire_1.lieu_naissance_conjoint' => ['nullable', 'string', 'max:100'],
            'mandataire_1.cni_conjoint' => ['nullable', 'string', 'max:50'],
            'mandataire_1.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'mandataire_1.photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],

            // Mandataire 2 (optionnel)
            'mandataire_2' => ['sometimes', 'array'],
            'mandataire_2.sexe' => ['required_with:mandataire_2', Rule::in(['M', 'F'])],
            'mandataire_2.nom' => ['required_with:mandataire_2', 'string', 'max:100'],
            'mandataire_2.prenoms' => ['required_with:mandataire_2', 'string', 'max:150'],
            'mandataire_2.date_naissance' => ['required_with:mandataire_2', 'date', 'before:today'],
            'mandataire_2.lieu_naissance' => ['required_with:mandataire_2', 'string', 'max:100'],
            'mandataire_2.telephone' => ['required_with:mandataire_2', 'string', 'max:20'],
            'mandataire_2.adresse' => ['required_with:mandataire_2', 'string', 'max:255'],
            'mandataire_2.nationalite' => ['required_with:mandataire_2', 'string', 'max:50'],
            'mandataire_2.profession' => ['required_with:mandataire_2', 'string', 'max:100'],
            'mandataire_2.numero_cni' => ['required_with:mandataire_2', 'string', 'max:50', 'unique:account_mandataries,numero_cni'],
            'mandataire_2.situation_familiale' => ['required_with:mandataire_2', Rule::in(['celibataire', 'marie', 'divorce', 'veuf', 'autre'])],

            // Documents
            'documents' => ['sometimes', 'array'],
            'documents.*.type' => ['required', Rule::in(['cni_client', 'cni_mandataire', 'justificatif_domicile', 'photo_identite', 'signature', 'formulaire_ouverture', 'convention', 'autre'])],
            'documents.*.fichier' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
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
            'mandataire_1.numero_cni.unique' => 'Ce numéro de CNI est déjà utilisé par un autre mandataire.',
            'documents.*.fichier.max' => 'La taille du fichier ne doit pas dépasser 8 Mo.',
        ];
    }
}
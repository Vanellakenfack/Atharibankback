<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour valider l'étape 3 (Mandataires)
 */
class ValiderEtape3Request extends FormRequest
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
            // Mandataire 1 (obligatoire)
            'mandataire_1' => 'nullable|array',
            'mandataire_1.sexe' => 'nullable|in:masculin,feminin',
            'mandataire_1.nom' => 'nullable|string|max:255',
            'mandataire_1.prenom' => 'nullable|string|max:255',
            'mandataire_1.date_naissance' => 'nullable|date|before:today',
            'mandataire_1.lieu_naissance' => 'nullable|string|max:255',
            'mandataire_1.telephone' => 'nullable|string|max:20',
            'mandataire_1.adresse' => 'nullable|string',
            'mandataire_1.nationalite' => 'nullable|string|max:255',
            'mandataire_1.profession' => 'nullable|string|max:255',
            'mandataire_1.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'mandataire_1.numero_cni' => 'nullable|string|max:50',
            'mandataire_1.situation_familiale' => 'nullable|in:marie,celibataire,autres',
            'mandataire_1.nom_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'mandataire_1.date_naissance_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|date',
            'mandataire_1.lieu_naissance_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'mandataire_1.cni_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:50',
            'mandataire_1.signature_path' => 'nullable|string',

            // Mandataire 2 (optionnel mais si présent, valider tous les champs)
            'mandataire_2' => 'nullable|array',
            'mandataire_2.sexe' => 'nullable_with:mandataire_2|in:masculin,feminin',
            'mandataire_2.nom' => 'nullable_with:mandataire_2|string|max:255',
            'mandataire_2.prenom' => 'nullable_with:mandataire_2|string|max:255',
            'mandataire_2.date_naissance' => 'nullable_with:mandataire_2|date|before:today',
            'mandataire_2.lieu_naissance' => 'nullable_with:mandataire_2|string|max:255',
            'mandataire_2.telephone' => 'nullable_with:mandataire_2|string|max:20',
            'mandataire_2.adresse' => 'nullable_with:mandataire_2|string',
            'mandataire_2.nationalite' => 'nullable_with:mandataire_2|string|max:255',
            'mandataire_2.profession' => 'nullable_with:mandataire_2|string|max:255',
            'mandataire_2.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'mandataire_2.numero_cni' => 'nullable_with:mandataire_2|string|max:50',
            'mandataire_2.situation_familiale' => 'nullable_with:mandataire_2|in:marie,celibataire,autres',
            'mandataire_2.nom_conjoint' => 'nullable|string|max:255',
            'mandataire_2.date_naissance_conjoint' => 'nullable|date',
            'mandataire_2.lieu_naissance_conjoint' => 'nullable|string|max:255',
            'mandataire_2.cni_conjoint' => 'nullable|string|max:50',
            'mandataire_2.signature_path' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'mandataire_1.required' => 'Le mandataire principal est obligatoire.',
            'mandataire_1.sexe.required' => 'Le sexe du mandataire 1 est obligatoire.',
            'mandataire_1.sexe.in' => 'Le sexe doit être masculin ou feminin.',
            'mandataire_1.nom.required' => 'Le nom du mandataire 1 est obligatoire.',
            'mandataire_1.prenom.required' => 'Le prénom du mandataire 1 est obligatoire.',
            'mandataire_1.date_naissance.required' => 'La date de naissance du mandataire 1 est obligatoire.',
            'mandataire_1.date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'mandataire_1.lieu_naissance.required' => 'Le lieu de naissance du mandataire 1 est obligatoire.',
            'mandataire_1.telephone.required' => 'Le téléphone du mandataire 1 est obligatoire.',
            'mandataire_1.adresse.required' => 'L\'adresse du mandataire 1 est obligatoire.',
            'mandataire_1.nationalite.required' => 'La nationalité du mandataire 1 est obligatoire.',
            'mandataire_1.profession.required' => 'La profession du mandataire 1 est obligatoire.',
            'mandataire_1.numero_cni.required' => 'Le numéro CNI du mandataire 1 est obligatoire.',
            'mandataire_1.situation_familiale.required' => 'La situation familiale du mandataire 1 est obligatoire.',
            'mandataire_1.situation_familiale.in' => 'La situation familiale doit être : marie, celibataire ou autres.',
            'mandataire_1.nom_conjoint.required_if' => 'Le nom du conjoint est obligatoire pour une personne mariée.',
            'mandataire_1.date_naissance_conjoint.required_if' => 'La date de naissance du conjoint est obligatoire pour une personne mariée.',
            'mandataire_1.lieu_naissance_conjoint.required_if' => 'Le lieu de naissance du conjoint est obligatoire pour une personne mariée.',
            'mandataire_1.cni_conjoint.required_if' => 'Le numéro CNI du conjoint est obligatoire pour une personne mariée.',
        ];
    }
}
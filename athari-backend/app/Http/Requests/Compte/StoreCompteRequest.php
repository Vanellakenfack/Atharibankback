<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validation pour la création d'un compte
 */
class StoreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Étape 1: Informations de base
            'etape1.client_id' => 'required|exists:clients,id',
            'etape1.type_compte_id' => 'required|exists:types_comptes,id',
            'etape1.code_type_compte' => 'required|string|size:2',
            'etape1.devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'etape1.rubriques_mata' => 'nullable|array',
            'etape1.rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'etape1.duree_blocage_mois' => 'nullable|integer|between:3,12',

            // Étape 2: Plan comptable
            'etape2.plan_comptable_id' => 'required|exists:plan_comptable,id',
            'etape2.categorie_id' => 'nullable|exists:categories_comptables,id',
            'etape2.gestionnaire_id' => 'required|exists:gestionnaires,id',
            'etape2.gestionnaire_nom' => 'nullable|string|max:255',
            'etape2.gestionnaire_prenom' => 'nullable|string|max:255',
            'etape2.gestionnaire_code' => 'nullable|string|max:20',

            // Étape 3: Mandataires
            'etape3.mandataire_1.sexe' => 'required|in:masculin,feminin',
            'etape3.mandataire_1.nom' => 'required|string|max:255',
            'etape3.mandataire_1.prenom' => 'required|string|max:255',
            'etape3.mandataire_1.date_naissance' => 'required|date|before:today',
            'etape3.mandataire_1.lieu_naissance' => 'required|string|max:255',
            'etape3.mandataire_1.telephone' => 'required|string|max:20',
            'etape3.mandataire_1.adresse' => 'required|string',
            'etape3.mandataire_1.nationalite' => 'required|string|max:255',
            'etape3.mandataire_1.profession' => 'required|string|max:255',
            'etape3.mandataire_1.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'etape3.mandataire_1.numero_cni' => 'required|string|max:50',
            'etape3.mandataire_1.situation_familiale' => 'required|in:marie,celibataire,autres',
            'etape3.mandataire_1.nom_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'etape3.mandataire_1.date_naissance_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|date',
            'etape3.mandataire_1.lieu_naissance_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'etape3.mandataire_1.cni_conjoint' => 'required_if:etape3.mandataire_1.situation_familiale,marie|nullable|string|max:50',
            'etape3.mandataire_1.signature_path' => 'nullable|string',

            // Mandataire 2 (optionnel)
            'etape3.mandataire_2' => 'nullable|array',
            'etape3.mandataire_2.sexe' => 'nullable|in:masculin,feminin',
            'etape3.mandataire_2.nom' => 'nullable|string|max:255',
            'etape3.mandataire_2.prenom' => 'nullable|string|max:255',
            'etape3.mandataire_2.date_naissance' => 'nullable|date|before:today',
            'etape3.mandataire_2.lieu_naissance' => 'nullable|string|max:255',
            'etape3.mandataire_2.telephone' => 'nullable|string|max:20',
            'etape3.mandataire_2.adresse' => 'nullable|string',
            'etape3.mandataire_2.nationalite' => 'nullable|string|max:255',
            'etape3.mandataire_2.profession' => 'nullable|string|max:255',
            'etape3.mandataire_2.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'etape3.mandataire_2.numero_cni' => 'nullable|string|max:50',
            'etape3.mandataire_2.situation_familiale' => 'nullable|in:marie,celibataire,autres',
            'etape3.mandataire_2.nom_conjoint' => 'nullable|string|max:255',
            'etape3.mandataire_2.date_naissance_conjoint' => 'nullable|date',
            'etape3.mandataire_2.lieu_naissance_conjoint' => 'nullable|string|max:255',
            'etape3.mandataire_2.cni_conjoint' => 'nullable|string|max:50',
            'etape3.mandataire_2.signature_path' => 'nullable|string',

            // Étape 4: Documents et validation
            'etape4.notice_acceptee' => 'required|boolean|accepted',
            
            // AJOUT DES NOUVEAUX FICHIERS PDF
            'demande_ouverture_pdf' => 'required|file|mimes:pdf|max:5120', // 5 MB max
            'formulaire_ouverture_pdf' => 'required|file|mimes:pdf|max:5120', // 5 MB max
            
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'types_documents' => 'nullable|array',
            'types_documents.*' => 'nullable|string',
            'descriptions_documents' => 'nullable|array',
            'descriptions_documents.*' => 'nullable|string',
            'signature' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'etape1.client_id.required' => 'Le client est obligatoire',
            'etape1.client_id.exists' => 'Le client sélectionné n\'existe pas',
            'etape2.plan_comptable_id.required' => 'Le plan comptable est obligatoire',
            'etape2.plan_comptable_id.exists' => 'Le plan comptable sélectionné n\'existe pas',
            'etape2.gestionnaire_id.required' => 'Le gestionnaire est obligatoire',
            'etape2.gestionnaire_id.exists' => 'Le gestionnaire sélectionné n\'existe pas',
            'etape4.notice_acceptee.accepted' => 'Vous devez accepter la notice d\'engagement',
            
            // AJOUT DES MESSAGES POUR LES NOUVEAUX CHAMPS
            'demande_ouverture_pdf.required' => 'La demande d\'ouverture en PDF est obligatoire',
            'demande_ouverture_pdf.mimes' => 'La demande d\'ouverture doit être un fichier PDF',
            'demande_ouverture_pdf.max' => 'La demande d\'ouverture ne doit pas dépasser 5 MB',
            
            'formulaire_ouverture_pdf.required' => 'Le formulaire d\'ouverture en PDF est obligatoire',
            'formulaire_ouverture_pdf.mimes' => 'Le formulaire d\'ouverture doit être un fichier PDF',
            'formulaire_ouverture_pdf.max' => 'Le formulaire d\'ouverture ne doit pas dépasser 5 MB',
            
            'documents.*.max' => 'Chaque document ne doit pas dépasser 10 MB',
        ];
    }
}
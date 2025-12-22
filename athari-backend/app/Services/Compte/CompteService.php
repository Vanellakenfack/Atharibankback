<?php

namespace App\Services\Compte;

use App\Models\compte\Compte;
use App\Models\client\Client;
use App\Models\chapitre\PlanComptable;
use App\Models\compte\TypeCompte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service de gestion des comptes bancaires
 * Gère la logique métier complexe de création et gestion des comptes
 */
class CompteService
{
    /**
     * Générer un numéro de compte unique
     * 
     * Format: AAA-CCCCCC-TT-O-K
     * - AAA: Code agence (3 chiffres) - récupéré du client
     * - CCCCCC: Numéro client (6 chiffres)
     * - TT: Code type compte (2 chiffres)
     * - O: Numéro ordinal (nombre de comptes de même type)
     * - K: Clé de contrôle (lettre majuscule)
     * 
     * @param int $clientId ID du client
     * @param string $codeTypeCompte Code du type de compte (2 chiffres)
     * @return string Numéro de compte généré
     */
    public function genererNumeroCompte(int $clientId, string $codeTypeCompte): string
    {
        $client = Client::findOrFail($clientId);
        
        $numeroClient = $client->numero_client;
        $codeAgence = substr($numeroClient, 0, 3);
        $numClient = substr($numeroClient, 3, 6);
        
        $nombreComptesMemetype = Compte::where('client_id', $clientId)
            ->whereHas('typeCompte', function ($query) use ($codeTypeCompte) {
                $query->where('code', $codeTypeCompte);
            })
            ->count();
        
        $numeroOrdinal = $nombreComptesMemetype + 1;
        
        $numeroSansCle = $codeAgence . $numClient . str_pad($codeTypeCompte, 2, '0', STR_PAD_LEFT) . $numeroOrdinal;
        
        $cle = $this->genererCleControle($numeroSansCle);
        
        return $numeroSansCle . $cle;
    }

    /**
     * Générer une clé de contrôle (lettre majuscule)
     * 
     * @param string $numeroSansCle Numéro de compte sans la clé
     * @return string Lettre majuscule
     */
    private function genererCleControle(string $numeroSansCle): string
    {
        // Algorithme simple: somme des chiffres modulo 26 + 'A'
        $somme = array_sum(str_split($numeroSansCle));
        $index = $somme % 26;
        return chr(65 + $index); // A=65 en ASCII
    }

    /**
     * Créer un nouveau compte bancaire (processus complet en 4 étapes)
     * 
     * @param array $donneesEtape1 Données de l'étape 1
     * @param array $donneesEtape2 Données de l'étape 2
     * @param array $donneesEtape3 Données de l'étape 3
     * @param array $donneesEtape4 Données de l'étape 4
     * @return Compte Compte créé
     */
public function creerCompte(
        array $donneesEtape1,
        array $donneesEtape2,
        array $donneesEtape3,
        array $donneesEtape4
    ): Compte {
        return DB::transaction(function () use ($donneesEtape1, $donneesEtape2, $donneesEtape3, $donneesEtape4) {
            
            $numeroCompte = $this->genererNumeroCompte(
                $donneesEtape1['client_id'],
                $donneesEtape1['code_type_compte']
            );
            
            // Créer le compte avec plan_comptable_id
            $compte = Compte::create([
                'numero_compte' => $numeroCompte,
                'client_id' => $donneesEtape1['client_id'],
                'type_compte_id' => $donneesEtape1['type_compte_id'],
                'plan_comptable_id' => $donneesEtape2['plan_comptable_id'], // MODIFICATION
                'devise' => $donneesEtape1['devise'],
                'gestionnaire_nom' => $donneesEtape1['gestionnaire_nom'],
                'gestionnaire_prenom' => $donneesEtape1['gestionnaire_prenom'],
                'gestionnaire_code' => $donneesEtape1['gestionnaire_code'],
                'rubriques_mata' => $donneesEtape1['rubriques_mata'] ?? null,
                'duree_blocage_mois' => $donneesEtape1['duree_blocage_mois'] ?? null,
                'statut' => 'actif',
                'solde' => 0,
                'notice_acceptee' => $donneesEtape4['notice_acceptee'],
                'date_acceptation_notice' => now(),
                'signature_path' => $donneesEtape4['signature_path'] ?? null,
                'date_ouverture' => now(),
            ]);
            
            // Créer les mandataires
            if (isset($donneesEtape3['mandataire_1'])) {
                $compte->mandataires()->create(array_merge(
                    $donneesEtape3['mandataire_1'],
                    ['ordre' => 1]
                ));
            }
            
            if (isset($donneesEtape3['mandataire_2'])) {
                $compte->mandataires()->create(array_merge(
                    $donneesEtape3['mandataire_2'],
                    ['ordre' => 2]
                ));
            }
            
            // Enregistrer les documents
            if (isset($donneesEtape4['documents'])) {
                foreach ($donneesEtape4['documents'] as $document) {
                    $compte->documents()->create($document);
                }
            }
            
            return $compte->load(['client', 'typeCompte', 'planComptable.categorie', 'mandataires', 'documents']);
        });
    }


    /**
     * Valider les données de l'étape 1
     * 
     * @param array $donnees Données à valider
     * @return array Données validées
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validerEtape1(array $donnees): array
    {
        return validator($donnees, [
            'client_id' => 'required|exists:clients,id',
            'type_compte_id' => 'required|exists:types_comptes,id',
            'code_type_compte' => 'required|string|size:2',
            'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'gestionnaire_nom' => 'required|string|max:255',
            'gestionnaire_prenom' => 'required|string|max:255',
            'gestionnaire_code' => 'required|string|max:20',
            'rubriques_mata' => 'nullable|array',
            'rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'duree_blocage_mois' => 'nullable|integer|between:3,12',
        ])->validate();
    }

    /**
     * Valider les données de l'étape 2
     * 
     * @param array $donnees Données à valider
     * @return array Données validées
     */
    public function validerEtape2(array $donnees): array
    {
        return validator($donnees, [
            'plan_comptable_id' => 'required|exists:plan_comptable,id',
            'categorie_id' => 'nullable|exists:categories_comptables,id',
        ])->validate();
    }

    /**
     * Valider les données de l'étape 3 (mandataires)
     * 
     * @param array $donnees Données à valider
     * @return array Données validées
     */
    public function validerEtape3(array $donnees): array
    {
        $rules = [
            // Mandataire 1 (obligatoire)
            'mandataire_1.sexe' => 'required|in:masculin,feminin',
            'mandataire_1.nom' => 'required|string|max:255',
            'mandataire_1.prenom' => 'required|string|max:255',
            'mandataire_1.date_naissance' => 'required|date|before:today',
            'mandataire_1.lieu_naissance' => 'required|string|max:255',
            'mandataire_1.telephone' => 'required|string|max:20',
            'mandataire_1.adresse' => 'required|string',
            'mandataire_1.nationalite' => 'required|string|max:255',
            'mandataire_1.profession' => 'required|string|max:255',
            'mandataire_1.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'mandataire_1.numero_cni' => 'required|string|max:50',
            'mandataire_1.situation_familiale' => 'required|in:marie,celibataire,autres',
            'mandataire_1.nom_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'mandataire_1.date_naissance_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|date',
            'mandataire_1.lieu_naissance_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:255',
            'mandataire_1.cni_conjoint' => 'required_if:mandataire_1.situation_familiale,marie|nullable|string|max:50',
            'mandataire_1.signature_path' => 'nullable|string',
            
            // Mandataire 2 (optionnel)
            'mandataire_2.sexe' => 'nullable|in:masculin,feminin',
            'mandataire_2.nom' => 'nullable|string|max:255',
            'mandataire_2.prenom' => 'nullable|string|max:255',
            'mandataire_2.date_naissance' => 'nullable|date|before:today',
            'mandataire_2.lieu_naissance' => 'nullable|string|max:255',
            'mandataire_2.telephone' => 'nullable|string|max:20',
            'mandataire_2.adresse' => 'nullable|string',
            'mandataire_2.nationalite' => 'nullable|string|max:255',
            'mandataire_2.profession' => 'nullable|string|max:255',
            'mandataire_2.nom_jeune_fille_mere' => 'nullable|string|max:255',
            'mandataire_2.numero_cni' => 'nullable|string|max:50',
            'mandataire_2.situation_familiale' => 'nullable|in:marie,celibataire,autres',
            'mandataire_2.nom_conjoint' => 'nullable|string|max:255',
            'mandataire_2.date_naissance_conjoint' => 'nullable|date',
            'mandataire_2.lieu_naissance_conjoint' => 'nullable|string|max:255',
            'mandataire_2.cni_conjoint' => 'nullable|string|max:50',
            'mandataire_2.signature_path' => 'nullable|string',
        ];

        return validator($donnees, $rules)->validate();
    }

    /**
     * Valider les données de l'étape 4 (documents et validation)
     * 
     * @param array $donnees Données à valider
     * @return array Données validées
     */
    public function validerEtape4(array $donnees): array
    {
        return validator($donnees, [
            'notice_acceptee' => 'required|boolean|accepted',
            'signature_path' => 'nullable|string',
            'documents' => 'required|array|min:1',
            'documents.*.type_document' => 'required|string',
            'documents.*.fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10 MB
        ])->validate();
    }

    /**
     * Obtenir les plans comptables par catégorie
     * NOUVELLE MÉTHODE
     */
    public function getPlansComptablesParCategorie(?int $categorieId = null)
    {
        $query = PlanComptable::with('categorie')->actif();

        if ($categorieId) {
            $query->where('categorie_id', $categorieId);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Mettre à jour un compte
     * 
     * @param int $compteId ID du compte
     * @param array $donnees Données à mettre à jour
     * @return Compte Compte mis à jour
     */
    public function mettreAJourCompte(int $compteId, array $donnees): Compte
    {
        $compte = Compte::findOrFail($compteId);
        
        $compte->update($donnees);
        
        return $compte->fresh();
    }

    /**
     * Clôturer un compte
     * 
     * @param int $compteId ID du compte
     * @param string|null $motif Motif de clôture
     * @return Compte Compte clôturé
     */
    public function cloturerCompte(int $compteId, ?string $motif = null): Compte
    {
        $compte = Compte::findOrFail($compteId);
        
        // Vérifier que le solde est à zéro
        if ($compte->solde != 0) {
            throw new \Exception('Le compte doit avoir un solde de 0 pour être clôturé.');
        }
        
        $compte->update([
            'statut' => 'cloture',
            'date_cloture' => now(),
            'observations' => $motif,
        ]);
        
        return $compte;
    }

  /**
     * Obtenir les comptes d'un client
     */
    public function getComptesClient(int $clientId)
    {
        return Compte::where('client_id', $clientId)
            ->with(['typeCompte', 'planComptable.categorie', 'mandataires'])
            ->get();
    }
}
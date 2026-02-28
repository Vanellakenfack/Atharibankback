<?php

namespace App\Services\Compte;

use App\Models\compte\Compte;
use App\Models\client\Client;
use App\Models\chapitre\PlanComptable;
use App\Models\compte\MouvementComptable;
use App\Models\compte\TypeCompte;
use Illuminate\Support\Facades\DB;
use App\Notifications\CompteActiveNotification;
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
        $numeroClient = $client->num_client;

        // Vérifier que le numéro client est exactement de 9 chiffres
        if (!preg_match('/^\d{9}$/', $numeroClient)) {
            throw new \Exception("Numéro client invalide pour la génération de compte : doit être exactement 9 chiffres.");
        }

        $nombreComptesMemetype = Compte::where('client_id', $clientId)
            ->whereHas('typeCompte', function ($query) use ($codeTypeCompte) {
                $query->where('code', $codeTypeCompte);
            })
            ->count();

        $numeroOrdinal = $nombreComptesMemetype + 1;

        $numeroSansCle = $numeroClient . str_pad($codeTypeCompte, 2, '0', STR_PAD_LEFT) . $numeroOrdinal;

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
            $typeCompte = TypeCompte::findOrFail($donneesEtape1['type_compte_id']);
            // 🔹 2. Vérification sécurité
            if (!$typeCompte->chapitre_defaut_id) {
                throw new \Exception(
                    "Aucun plan comptable par défaut défini pour le type de compte {$typeCompte->libelle}"
                );
            }

            // Récupérer l'agence_id depuis le client
            $client = Client::findOrFail($donneesEtape1['client_id']);
           $agenceId = request()->input('agency_id') 
                  ?? request()->input('agence_id') 
                  ?? $client->agence_id;

        if (!$agenceId) {
            throw new \Exception("L'identifiant de l'agence est requis pour ouvrir un compte.");
        }

        if (Compte::where('numero_compte', $numeroCompte)->exists()) {
            throw new \Exception("Erreur critique : Le numéro de compte généré [$numeroCompte] existe déjà dans le système.");
        }

        // 2. Récupération de la session via le service (plus propre que de requêter le modèle ici)
        // Note: Le middleware a déjà dû vérifier cela, mais on sécurise la logique métier.
        $session = \App\Services\ComptabiliteService::getSessionActive($agenceId);
        
        if (!$session) {
            throw new \Exception("Opération impossible : La journée comptable n'est pas ouverte pour cette agence.");
        }

        $jourComptableId = $session->jours_comptable_id;

            // Créer le compte avec plan_comptable_id
            $compte = Compte::create([
                'numero_compte' => $numeroCompte,
                'client_id' => $donneesEtape1['client_id'],
                'type_compte_id' => $donneesEtape1['type_compte_id'],
               'plan_comptable_id'  => $donneesEtape2['plan_comptable_id'],
                'devise' => $donneesEtape1['devise'],
                'gestionnaire_id' => $donneesEtape2['gestionnaire_id'] ,
               'created_by' => auth()->id() ?? 1, // Si pas connecté, on met l'ID 1 par défaut
                'rubriques_mata' => $donneesEtape1['rubriques_mata'] ?? null,
                'duree_blocage_mois' => $donneesEtape1['duree_blocage_mois'] ?? null,
                
                'statut'             => 'actif', 
                'est_en_opposition'  => false, // Bloqué jusqu'à validation CA + Juridique
                'validation_chef_agence' => true,
                'validation_juridique'   => true,

                'solde' => $donneesEtape2['solde'] ?? 0,
                'notice_acceptee' => $donneesEtape4['notice_acceptee'],
                'date_acceptation_notice' => now(),
                'signature_path' => $donneesEtape4['signature_path'] ?? null,
                'date_ouverture'    => $session->jourComptable->date_du_jour, 
                'jours_comptable_id' => $jourComptableId,                   
                'date_comptable'    => $session->jourComptable->date_du_jour, // Optionnel mais conseillé
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

            // On récupère le montant du dépôt initial envoyé par React (étape 2)
        $montantInitial = (float) ($donneesEtape2['solde'] ?? 0);
        
        // On appelle la fonction qui va générer les écritures de mouvement
        $this->traiterOuvertureComptable($compte, $montantInitial);

            return $compte->load(['client', 'typeCompte', 'planComptable.categorie', 'mandataires', 'documents', 'jourComptable']);
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
            'agence_id' => 'nullable|exists:agencies,id', // Ajout de l'agence_id obligatoire
            'type_compte_id' => 'required|exists:types_comptes,id',
            'code_type_compte' => 'required|string|size:2',
            'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
             'agency_id'        => 'required|exists:agencies,id', // Obligatoire pour le middleware
            'rubriques_mata' => 'nullable|array',
            'rubriques_mata.*' => 'in:SANTE,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITE',
            'duree_blocage_mois' => 'nullable|integer|between:3,12',
            'solde' => 'nullable|integer|min:0',

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
                        'gestionnaire_id' => 'required|exists:gestionnaires,id',

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
            'documents.*.fichier' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10 MB
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


    /**
 * Récupère l'ID d'un chapitre à partir de son code brut
 */
private function getIdParCode(string $code)
    {
        $chapitre = \App\Models\chapitre\PlanComptable::where('code', $code)->first();
        if (!$chapitre) {
            throw new \Exception("Le code comptable [ $code ] n'existe pas dans le plan.");
        }
        return $chapitre->id;
    }

   
public function traiterOuvertureComptable(Compte $compte, float $montantDepot)
{
    $type = $compte->typeCompte;

    // 1. Calcul du total des frais + minimum
    $fraisTotal = 0;
   // if ($type->frais_ouverture_actif) $fraisTotal += $type->frais_ouverture;
    //if ($type->commission_sms_actif) $fraisTotal += $type->commission_sms;
   // if ($type->frais_carnet_actif)   $fraisTotal += $type->frais_carnet;
    //if ($type->frais_livret_actif)   $fraisTotal += $type->frais_livret;

    // On récupère le minimum obligatoire
   // $minimumCompte = $type->minimum_compte_actif ? $type->minimum_compte : 0;
     
    return DB::transaction(function () use ($compte, $type, $montantDepot, $fraisTotal) {
        
        // --- ÉTAPE 1 : DÉPÔT (0 ou plus) ---
     /*   if ($montantDepot > 0) {
            $this->enregistrerEcriture(
                $compte,
                $montantDepot,
                "Dépôt initial ouverture",
                $this->getIdParCode('57100000'), 
                $type->chapitre_defaut_id,
                'CAISSE'
            );
        }*/

        // --- ÉTAPE 2 : DÉDUCTION DES FRAIS ---


                // Frais d'ouverture
      /*  if ($type->frais_ouverture_actif && $type->frais_ouverture > 0) {
            $this->enregistrerEcriture(
                $compte,
                $type->frais_ouverture,
                "Déduction Frais d'ouverture",
                $type->chapitre_defaut_id,          // Débit : Client
                $type->chapitre_frais_ouverture_id, // Crédit : Banque
                'BANQUE'
            );
        }*/

     

        // Frais de carnet
       /* if ( $type->frais_carnet_actif && $type->frais_carnet > 0) {
            $this->enregistrerEcriture(
                $compte,
                $type->frais_carnet,
                "Déduction Frais de carnet",
                $type->chapitre_defaut_id,
                $type->chapitre_frais_carnet_id,
                'BANQUE'
            );
        }

        // Frais de livret
        if ($type->frais_livret_actif && $type->frais_livret > 0) {
            $this->enregistrerEcriture(
                $compte,
                $type->frais_livret,
                "Déduction Frais de livret",
                $type->chapitre_defaut_id,
                $type->chapitre_frais_livret_id,
                'BANQUE'
            );
        }


*/
 
        // --- ÉTAPE 3 : MISE À JOUR DU SOLDE (Incluant le minimum) ---
        // Le solde devient : Dépôt - Frais - MinimumObligatoire
        $soldeFinal = $montantDepot - ($fraisTotal );
        
        $compte->update([
            'solde' => $soldeFinal 
        ]);

        return $compte->fresh();
    });
}

private function enregistrerEcriture($compte, $montant, $libelle, $debitId, $creditId, $journal = 'BANQUE')
{
    $reference = 'OP-' . strtoupper(Str::random(8));
    $date = now();

    // 1. LA LIGNE DU DÉBIT
    \App\Models\compte\MouvementComptable::create([
        'compte_id'         => $compte->id,
        'date_mouvement'    => $date,
        'libelle_mouvement' => $libelle . ' (D)',
        'montant_debit'     => $montant,
        'montant_credit'    => 0, // Zéro ici
        'compte_debit_id'   => $debitId,
        'compte_credit_id'  => $creditId,
        'journal'           => $journal, 
        'statut'            => 'COMPTABILISE',
        'reference'         => $reference
    ]);

    // 2. LA LIGNE DU CRÉDIT
    return \App\Models\compte\MouvementComptable::create([
        'compte_id'         => $compte->id,
        'date_mouvement'    => $date,
        'libelle_mouvement' => $libelle . ' (C)',
        'montant_debit'     => 0, // Zéro ici
        'montant_credit'    => $montant,
        'compte_debit_id'   => $debitId,
        'compte_credit_id'  => $creditId,
        'journal'           => $journal, 
        'statut'            => 'COMPTABILISE',
        'reference'         => $reference
    ]);
}


/**
 * Action de l'assistant juridique
 * Vérifie si le NUI est présent dans la table client avant de valider

/**
 * Tente d'activer le compte et de lever l'opposition 
 * si et seulement si toutes les validations sont au vert.
 */
/*private function tenterActivationFinale(Compte $compte)
{
    // On rafraîchit pour avoir dossier_complet mis à jour par le juriste
    $compte->refresh();

    if ($compte->validation_chef_agence && $compte->validation_juridique) {
        
        /**
         * LOGIQUE MICROFINANCE :
         * Si le dossier est complet, on lève l'opposition (false).
         * Si des documents manquent, on maintient l'opposition (true).
         */
     /*   $maintenirOpposition = ! (bool) ($compte->dossier_complet ?? false);

        $compte->update([
            'statut' => 'actif',
            'est_en_opposition' => $maintenirOpposition, 
            'date_activation_definitive' => now()
        ]);

        try {
            // On ne notifie le client que si le compte est totalement prêt (sans opposition)
            if (!$maintenirOpposition) {
                $compte->client->notify(new CompteActiveNotification($compte));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur notification : " . $e->getMessage());
        }
    }
}*/
/**
 * Gère la validation par étapes pour lever l'opposition
 */
/*public function validerOuvertureCompte(int $compteId, string $roleApprobateur, array $checkboxes = [], ?string $nui = null)
{
    return DB::transaction(function () use ($compteId, $roleApprobateur, $checkboxes, $nui) {
        $compte = Compte::with('client')->lockForUpdate()->findOrFail($compteId);

        // --- LOGIQUE CHEF D'AGENCE ---
        if ($roleApprobateur === "Chef d'Agence (CA)") {
            $compte->validation_chef_agence = true;
            $compte->ca_id = auth()->id();
        }
 
        // --- LOGIQUE ASSISTANT JURIDIQUE ---
        if ($roleApprobateur === "Assistant Juridique (AJ)") {
            // 1. Gestion du NUI
            $nuiFinal = $nui ?: $compte->client->nui;
            if (empty($nuiFinal)) {
                throw new \Exception("Le NUI est obligatoire pour la validation juridique.");
            }
            if ($nui && $nui !== $compte->client->nui) {
                $compte->client->update(['nui' => $nui]);
            }

            // 2. Gestion Spécifique des Checkboxes (uniquement pour AJ)
            // Convertir si c'est un array simple en objet avec clés/valeurs
            $checkboxesFormatted = [];
            if (is_array($checkboxes)) {
                // Si c'est un array séquentiel [0, 1, 2, ...], convertir en objet
                if (array_keys($checkboxes) === range(0, count($checkboxes) - 1)) {
                    // C'est un array simple, créer un objet avec les valeurs comme clés
                    foreach ($checkboxes as $value) {
                        $checkboxesFormatted[$value] = true;
                    }
                } else {
                    // C'est déjà un objet
                    $checkboxesFormatted = $checkboxes;
                }
            }

            $documentsObligatoires = ['cni_valide', 'plan_localisation', 'photo_identite'];
            $toutEstCoche = true;

            foreach ($documentsObligatoires as $doc) {
                if (!isset($checkboxesFormatted[$doc]) || $checkboxesFormatted[$doc] !== true) {
                    $toutEstCoche = false;
                }
            }

            $compte->checklist_juridique = $checkboxesFormatted;
            $compte->dossier_complet = $toutEstCoche;
            $compte->validation_juridique = true;
            $compte->date_validation_juridique = now();
            $compte->juriste_id = auth()->id();
        }

        $compte->save();

        // Tentative d'activation
        $this->tenterActivationFinale($compte);

        return $compte->fresh(['client', 'typeCompte', 'planComptable', 'mandataires', 'documents']);
    });
}

public function rejeterOuverture(int $compteId, string $motif)
{
    return DB::transaction(function () use ($compteId, $motif) {
        $compte = Compte::findOrFail($compteId);
$user = auth()->user(); // Récupère le Chef d'Agence ou le Juriste connecté
        // On enregistre le rejet
        $compte->update([
            'statut' => 'rejete', 
         'motif_rejet' => $motif, // On enregistre le texte du rejet
            'date_rejet' => now(),
            'rejete_par'   => $user?->id,  // Enregistre l'ID de l'auteur (si connecté)
            // IMPORTANT : On réinitialise les validations
            // Si le dossier est corrigé, tout le monde doit revérifier
            'validation_chef_agence' => false,
            'validation_juridique' => false,
            'est_en_opposition' => true // On maintient le blocage
        ]);

        return $compte;
    });
}*/

// Dans CompteController.php
/*public function showForValidation($id)
{
    // On récupère le compte avec le client et tous ses documents chargés
    $compte = Compte::with(['client', 'documents', 'mandataires', 'typeCompte'])
                    ->findOrFail($id);

    return response()->json($compte);
}*/
 
 /* Récupère le journal détaillé des ouvertures de comptes
 */
   public function journalOuvertures($dateDebut = null, $dateFin = null, $codeAgence = null)
{
    // On part de la table Compte pour ne perdre aucune ouverture
    $query = Compte::with([
        'client.physique', // Charge les infos physiques
        'client.morale',
        'client.agency',
        'typeCompte',
        'mouvements' => function($q) {
            // On ne récupère que le mouvement de dépôt s'il existe
            $q->where('libelle_mouvement', 'LIKE', 'Dépôt initial%');
        }
    ]);

    
    $query->whereDate('date_comptable', '>=', $dateDebut)
          ->whereDate('date_comptable', '<=', $dateFin);


    if ($codeAgence) {
        $query->whereHas('client.agency', function($q) use ($codeAgence) {
            $q->where('code', $codeAgence);
        });
    }

    return $query->get()->sortBy(function($compte) {
        return ($compte->client->agency->code ?? '999') . 
               ($compte->typeCompte->libelle ?? 'ZZZ') . 
               $compte->date_ouverture;
    })->values();
}/**
 * Résumé statistique des ouvertures pour une clôture de journée
 */
public function resumeClotureOuvertures($date = null, $codeAgence = null)
{
    $date = $date ?? now()->toDateString();

    $query = \App\Models\compte\MouvementComptable::with(['compte.typeCompte', 'compte.client.agency'])
        ->whereDate('date_mouvement', $date);

    // Ajout du filtre strict par code agence
    if (!empty($codeAgence)) {
        $query->whereHas('compte.client.agency', function($q) use ($codeAgence) {
            $q->where('code', $codeAgence);
        });
    }

    return $query->get()
        ->groupBy(function($mvt) {
            return $mvt->compte->typeCompte->libelle ?? 'Non défini';
        })
        ->map(function ($mouvements, $typeLibelle) {
            // On compte les comptes uniques (un dépôt initial = un compte)
            $nbComptes = $mouvements->where('libelle_mouvement', 'LIKE', 'Dépôt initial%')->count();
            
            return [
                'type_compte'    => $typeLibelle,
                'nombre_comptes' => $nbComptes,
                'total_depots'   => (float) $mouvements->where('libelle_mouvement', 'LIKE', 'Dépôt initial%')->sum('montant_debit'),
                'total_frais'    => (float) $mouvements->where('libelle_mouvement', 'LIKE', 'Déduction Frais%')->sum('montant_debit'),
            ];
        })->values();
}

/**
 * Vérifie si le dépôt couvre les frais + le minimum obligatoire
 */


/**
 * Récupère l'historique des mouvements d'un compte avec calcul du solde progressif
 * * @param Compte $compte
 * @param string|null $dateDebut
 * @param string|null $dateFin
 * @return \Illuminate\Support\Collection
 * 
 * 
 */

public function historiqueCompte(Compte $compte, $dateDebut = null, $dateFin = null)
{
    // 1. CALCUL DU SOLDE INITIAL (Avant la date de début)
    // On somme tous les mouvements passés avant la date demandée
    $soldeInitial = 0;
    if ($dateDebut) {
        $mouvementsPrecedents = MouvementComptable::where('compte_id', $compte->id)
            ->whereDate('date_mouvement', '<', $dateDebut)
            ->selectRaw('SUM(montant_credit) - SUM(montant_debit) as solde')
            ->first();
        
        $soldeInitial = (float) ($mouvementsPrecedents->solde ?? 0);
    }

    // 2. RÉCUPÉRATION DES MOUVEMENTS DE LA PÉRIODE
    $query = MouvementComptable::where('compte_id', $compte->id)
        ->orderBy('date_mouvement', 'asc') // Chronologique pour le calcul
        ->orderBy('created_at', 'asc');    // Second tri pour l'ordre de saisie exact

    if ($dateDebut) $query->whereDate('date_mouvement', '>=', $dateDebut);
    if ($dateFin) $query->whereDate('date_mouvement', '<=', $dateFin);

    $mouvements = $query->get();

    // 3. CALCUL PROGRESSIF
    $soldeProgressif = $soldeInitial;

    $historique = $mouvements->map(function ($mvt) use (&$soldeProgressif) {
        $debit = (float) $mvt->montant_debit;
        $credit = (float) $mvt->montant_credit;

        // Formule : Solde précédent + ce qui rentre - ce qui sort
        $soldeProgressif += ($credit - $debit);

        return [
            'id' => $mvt->id,
            'date' => $mvt->date_mouvement,
            'reference' => $mvt->reference,
            'libelle' => $mvt->libelle_mouvement,
            'debit' => $debit,
            'credit' => $credit,
            'solde_apres' => $soldeProgressif, // C'est le solde à cet instant T
            'journal' => $mvt->journal,
            'statut' => $mvt->statut
        ];
    });

    // 4. RETOUR ET TRI POUR L'AFFICHAGE
    // On inverse à la fin pour que l'utilisateur voit le plus récent en haut du tableau
    return [
        'solde_initial' => $soldeInitial,
        'mouvements' => $historique->reverse()->values(),
        'solde_final' => $soldeProgressif
    ];
}
}
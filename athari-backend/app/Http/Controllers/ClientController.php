<?php

namespace App\Http\Controllers;

use App\Models\Client\Client;
use App\Services\ClientNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * LISTE DES CLIENTS (seulement les actifs)
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $clients = Client::with(['physique', 'morale', 'agency'])
            ->actifs()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $clients->count(),
            'data'    => $clients
        ]);
    }

    /**
     * CRÉATION CLIENT PHYSIQUE
     */
    public function storePhysique(Request $request)
    {
        // 1. Valider les données JSON
        $validator = validator($request->all(), [
            'agency_id' => 'required|exists:agencies,id',
            'nom_prenoms' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date',
            'cni_numero' => 'required|string|unique:clients_physiques,cni_numero',
            'telephone' => 'required|string',
            'adresse_ville' => 'required|string',
            'adresse_quartier' => 'required|string',
            'email' => 'nullable|email',
            'lieu_dit_domicile' => 'nullable|string',
            'lieu_dit_activite' => 'nullable|string',
            'ville_activite' => 'nullable|string',
            'quartier_activite' => 'nullable|string',
            'bp' => 'nullable|string',
            'pays_residence' => 'nullable|string',
            'solde_initial' => 'nullable|numeric',
            'immobiliere' => 'nullable|string',
            'autres_biens' => 'nullable|string',
            'lieu_naissance' => 'nullable|string',
            'nationalite' => 'nullable|string',
            'nui' => 'nullable|string',
            'cni_delivrance' => 'nullable|date',
            'cni_expiration' => 'nullable|date',
            'nom_pere' => 'nullable|string',
            'nom_mere' => 'nullable|string',
            'nationalite_pere' => 'nullable|string',
            'nationalite_mere' => 'nullable|string',
            'profession' => 'nullable|string',
            'employeur' => 'nullable|string',
            'situation_familiale' => 'nullable|string',
            'regime_matrimonial' => 'nullable|string',
            'nom_conjoint' => 'nullable|string',
            'date_naissance_conjoint' => 'nullable|date',
            'cni_conjoint' => 'nullable|string',
            'profession_conjoint' => 'nullable|string',
            'salaire' => 'nullable|numeric',
            'tel_conjoint' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
            'cni_recto' => 'nullable|image|max:2048',
            'cni_verso' => 'nullable|image|max:2048',
            'photo_localisation_domicile' => 'nullable|image|max:2048',
            'photo_localisation_activite' => 'nullable|image|max:2048',
            // Champs ajoutés pour le client physique
            'nui_image' => 'nullable|image|max:2048',
            'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Vérifier l'autorisation
        $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        // 3. Traitement dans une transaction
        return DB::transaction(function () use ($request) {
            // Vérification de doublon par nom
            $doublon = Client::whereHas('physique', function ($query) use ($request) {
                $query->where('nom_prenoms', $request->nom_prenoms);
            })->actifs()->first();
            
            if ($doublon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un client avec le même nom existe déjà'
                ], 422);
            }

            // Vérification de doublon par CNI
            $doublonCni = DB::table('clients_physiques')
                ->where('cni_numero', $request->cni_numero)
                ->exists();
            
            if ($doublonCni) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un client avec le même numéro de CNI existe déjà'
                ], 422);
            }

            // Vérification de doublon par NUI
            if ($request->nui) {
                $doublonNui = DB::table('clients_physiques')
                    ->where('nui', $request->nui)
                    ->exists();
                
                if ($doublonNui) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un client avec le même numéro NUI existe déjà'
                    ], 422);
                }
            }

            // Génération du numéro
            $numClient = ClientNumberService::generate($request->agency_id);

            // Création du client principal
            $clientData = [
                'num_client' => $numClient,
                'agency_id' => $request->agency_id,
                'type_client' => 'physique',
                'telephone' => $request->telephone,
                'email' => $request->email,
                'adresse_ville' => $request->adresse_ville,
                'adresse_quartier' => $request->adresse_quartier,
                'lieu_dit_domicile' => $request->lieu_dit_domicile,
                'lieu_dit_activite' => $request->lieu_dit_activite,
                'ville_activite' => $request->ville_activite,
                'quartier_activite' => $request->quartier_activite,
                'bp' => $request->bp,
                'pays_residence' => $request->pays_residence ?? 'Cameroun',
                'solde_initial' => $request->solde_initial ?? 0,
                'immobiliere' => $request->immobiliere,
                'autres_biens' => $request->autres_biens,
                'etat' => Client::ETAT_PRESENT,
            ];

            // Gestion des fichiers uploadés pour le client principal
            if ($request->hasFile('photo_localisation_domicile')) {
                $clientData['photo_localisation_domicile'] = $request->file('photo_localisation_domicile')->store('clients/localisation/domicile', 'public');
            }

            if ($request->hasFile('photo_localisation_activite')) {
                $clientData['photo_localisation_activite'] = $request->file('photo_localisation_activite')->store('clients/localisation/activite', 'public');
            }

            $client = Client::create($clientData);

            // Création des détails physiques
            $physiqueData = [
                'nom_prenoms' => $request->nom_prenoms,
                'sexe' => $request->sexe,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'nationalite' => $request->nationalite,
                'nui' => $request->nui,
                'cni_numero' => $request->cni_numero,
                'cni_delivrance' => $request->cni_delivrance,
                'cni_expiration' => $request->cni_expiration,
                'nom_pere' => $request->nom_pere,
                'nom_mere' => $request->nom_mere,
                'nationalite_pere' => $request->nationalite_pere,
                'nationalite_mere' => $request->nationalite_mere,
                'profession' => $request->profession,
                'employeur' => $request->employeur,
                'situation_familiale' => $request->situation_familiale,
                'regime_matrimonial' => $request->regime_matrimonial,
                'nom_conjoint' => $request->nom_conjoint,
                'date_naissance_conjoint' => $request->date_naissance_conjoint,
                'cni_conjoint' => $request->cni_conjoint,
                'profession_conjoint' => $request->profession_conjoint,
                'salaire' => $request->salaire,
                'tel_conjoint' => $request->tel_conjoint,
            ];

            // Gestion des fichiers pour le physique
            if ($request->hasFile('photo')) {
                $physiqueData['photo'] = $request->file('photo')->store('clients/photos', 'public');
            }

            if ($request->hasFile('signature')) {
                $physiqueData['signature'] = $request->file('signature')->store('clients/signatures', 'public');
            }

            // Gestion des fichiers CNI
            if ($request->hasFile('cni_recto')) {
                $physiqueData['cni_recto'] = $request->file('cni_recto')->store('clients/cni/recto', 'public');
            }

            if ($request->hasFile('cni_verso')) {
                $physiqueData['cni_verso'] = $request->file('cni_verso')->store('clients/cni/verso', 'public');
            }

            // Gestion du fichier NUI pour le client physique
            if ($request->hasFile('nui_image')) {
                $physiqueData['nui_image'] = $request->file('nui_image')->store('clients/nui', 'public');
            }

            // Gestion du fichier attestation de conformité PDF
            if ($request->hasFile('attestation_conformite_pdf')) {
                $physiqueData['attestation_conformite_pdf'] = $request->file('attestation_conformite_pdf')->store('clients/attestations/conformite', 'public');
            }

            $client->physique()->create($physiqueData);

            // Charger les relations pour la réponse
            $client->load('physique');

            return response()->json([
                'success'    => true,
                'message'    => 'Client physique créé avec succès',
                'num_client' => $numClient,
                'data'       => $client
            ], 201);
        });
    }

    /**
     * CRÉATION CLIENT MORAL - VERSION COMPLÈTE
     */
    public function storeMorale(Request $request)
    {
        // 1. Valider les données
        $validator = validator($request->all(), [
            'agency_id' => 'required|exists:agencies,id',
            'raison_sociale' => 'required|string|max:255',
            'forme_juridique' => 'required|string',
            'type_entreprise' => 'required|in:entreprise,association',
            'rccm' => 'required|string|unique:clients_morales,rccm',
            'nom_gerant' => 'required|string',
            'telephone_gerant' => 'nullable|string',
            'photo_gerant' => 'nullable|image|max:2048',
            
            // Gérant 2
            'nom_gerant2' => 'nullable|string',
            'telephone_gerant2' => 'nullable|string',
            'photo_gerant2' => 'nullable|image|max:2048',
            
            // Signataire 1
            'nom_signataire' => 'nullable|string',
            'telephone_signataire' => 'nullable|string',
            'photo_signataire' => 'nullable|image|max:2048',
            'signature_signataire' => 'nullable|image|max:2048',
            
            // Signataire 2
            'nom_signataire2' => 'nullable|string',
            'telephone_signataire2' => 'nullable|string',
            'photo_signataire2' => 'nullable|image|max:2048',
            'signature_signataire2' => 'nullable|image|max:2048',
            
            // Signataire 3
            'nom_signataire3' => 'nullable|string',
            'telephone_signataire3' => 'nullable|string',
            'photo_signataire3' => 'nullable|image|max:2048',
            'signature_signataire3' => 'nullable|image|max:2048',
            
            // Informations de contact
            'telephone' => 'required|string',
            'adresse_ville' => 'required|string',
            'adresse_quartier' => 'required|string',
            'email' => 'nullable|email',
            'sigle' => 'nullable|string',
            'nui' => 'nullable|string',
            'lieu_dit_domicile' => 'nullable|string',
            'lieu_dit_activite' => 'nullable|string',
            'ville_activite' => 'nullable|string',
            'quartier_activite' => 'nullable|string',
            'bp' => 'nullable|string',
            'pays_residence' => 'nullable|string',
            'solde_initial' => 'nullable|numeric',
            'immobiliere' => 'nullable|string',
            'autres_biens' => 'nullable|string',
            
            // Localisation photos
            'photo_localisation_domicile' => 'nullable|image|max:2048',
            'photo_localisation_activite' => 'nullable|image|max:2048',
            
            // Documents administratifs - Images
            'extrait_rccm_image' => 'nullable|image|max:2048',
            'titre_patente_image' => 'nullable|image|max:2048',
            'nui_image' => 'nullable|image|max:2048',
            'statuts_image' => 'nullable|image|max:2048',
            'pv_agc_image' => 'nullable|image|max:2048',
            'attestation_non_redevance_image' => 'nullable|image|max:2048',
            'proces_verbal_image' => 'nullable|image|max:2048',
            'registre_coop_gic_image' => 'nullable|image|max:2048',
            'recepisse_declaration_association_image' => 'nullable|image|max:2048',
            
            // Documents administratifs - PDF
            'acte_designation_signataires_pdf' => 'nullable|mimes:pdf|max:5120',
            'liste_conseil_administration_pdf' => 'nullable|mimes:pdf|max:5120',
            'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120',
            
            // Plans de localisation signataires
            'plan_localisation_signataire1_image' => 'nullable|image|max:2048',
            'plan_localisation_signataire2_image' => 'nullable|image|max:2048',
            'plan_localisation_signataire3_image' => 'nullable|image|max:2048',
            
            // Factures eau signataires
            'facture_eau_signataire1_image' => 'nullable|image|max:2048',
            'facture_eau_signataire2_image' => 'nullable|image|max:2048',
            'facture_eau_signataire3_image' => 'nullable|image|max:2048',
            
            // Factures électricité signataires
            'facture_electricite_signataire1_image' => 'nullable|image|max:2048',
            'facture_electricite_signataire2_image' => 'nullable|image|max:2048',
            'facture_electricite_signataire3_image' => 'nullable|image|max:2048',
            
            // Localisation siège
            'plan_localisation_siege_image' => 'nullable|image|max:2048',
            'facture_eau_siege_image' => 'nullable|image|max:2048',
            'facture_electricite_siege_image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Vérifier l'autorisation
        $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        // 3. Traitement dans une transaction
        return DB::transaction(function () use ($request) {
            // Vérification de doublon par raison sociale
            $doublon = Client::whereHas('morale', function ($query) use ($request) {
                $query->where('raison_sociale', $request->raison_sociale);
            })->actifs()->first();
            
            if ($doublon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une entreprise avec la même raison sociale existe déjà'
                ], 422);
            }

            // Vérification de doublon par RCCM
            $doublonRccm = DB::table('clients_morales')
                ->where('rccm', $request->rccm)
                ->exists();
            
            if ($doublonRccm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une entreprise avec le même numéro RCCM existe déjà'
                ], 422);
            }

            // Vérification de doublon par NUI
            if ($request->nui) {
                $doublonNui = DB::table('clients_morales')
                    ->where('nui', $request->nui)
                    ->exists();
                
                if ($doublonNui) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Une entreprise avec le même numéro NUI existe déjà'
                    ], 422);
                }
            }

            // Génération du numéro
            $numClient = ClientNumberService::generate($request->agency_id);

            // Création du client principal
            $clientData = [
                'num_client' => $numClient,
                'agency_id' => $request->agency_id,
                'type_client' => 'morale',
                'telephone' => $request->telephone,
                'email' => $request->email,
                'adresse_ville' => $request->adresse_ville,
                'adresse_quartier' => $request->adresse_quartier,
                'lieu_dit_domicile' => $request->lieu_dit_domicile,
                'lieu_dit_activite' => $request->lieu_dit_activite,
                'ville_activite' => $request->ville_activite,
                'quartier_activite' => $request->quartier_activite,
                'bp' => $request->bp,
                'pays_residence' => $request->pays_residence ?? 'Cameroun',
                'solde_initial' => $request->solde_initial ?? 0,
                'immobiliere' => $request->immobiliere,
                'autres_biens' => $request->autres_biens,
                'etat' => Client::ETAT_PRESENT,
            ];

            // Gestion des fichiers uploadés du client principal
            if ($request->hasFile('photo_localisation_domicile')) {
                $clientData['photo_localisation_domicile'] = $request->file('photo_localisation_domicile')
                    ->store('clients/localisation/domicile', 'public');
            }

            if ($request->hasFile('photo_localisation_activite')) {
                $clientData['photo_localisation_activite'] = $request->file('photo_localisation_activite')
                    ->store('clients/localisation/activite', 'public');
            }

            $client = Client::create($clientData);

            // Création des détails moraux
            $moraleData = [
                'raison_sociale' => $request->raison_sociale,
                'sigle' => $request->sigle,
                'forme_juridique' => $request->forme_juridique,
                'type_entreprise' => $request->type_entreprise,
                'rccm' => $request->rccm,
                'nui' => $request->nui,
                'nom_gerant' => $request->nom_gerant,
                'telephone_gerant' => $request->telephone_gerant,
                
                // Gérant 2
                'nom_gerant2' => $request->nom_gerant2,
                'telephone_gerant2' => $request->telephone_gerant2,
                
                // Signataire 1
                'nom_signataire' => $request->nom_signataire,
                'telephone_signataire' => $request->telephone_signataire,
                
                // Signataire 2
                'nom_signataire2' => $request->nom_signataire2,
                'telephone_signataire2' => $request->telephone_signataire2,
                
                // Signataire 3
                'nom_signataire3' => $request->nom_signataire3,
                'telephone_signataire3' => $request->telephone_signataire3,
            ];

            // Gestion des fichiers pour les photos existantes
            $photoFields = [
                'photo_gerant' => 'clients/gerants',
                'photo_gerant2' => 'clients/gerants',
                'photo_signataire' => 'clients/signataires/photos',
                'photo_signataire2' => 'clients/signataires/photos',
                'photo_signataire3' => 'clients/signataires/photos',
                'signature_signataire' => 'clients/signataires/signatures',
                'signature_signataire2' => 'clients/signataires/signatures',
                'signature_signataire3' => 'clients/signataires/signatures',
                
                // Documents administratifs - Images
                'extrait_rccm_image' => 'clients/documents/extrait_rccm',
                'titre_patente_image' => 'clients/documents/titre_patente',
                'nui_image' => 'clients/documents/nui',
                'statuts_image' => 'clients/documents/statuts',
                'pv_agc_image' => 'clients/documents/pv_agc',
                'attestation_non_redevance_image' => 'clients/documents/attestation_non_redevance',
                'proces_verbal_image' => 'clients/documents/proces_verbal',
                'registre_coop_gic_image' => 'clients/documents/registre_coop_gic',
                'recepisse_declaration_association_image' => 'clients/documents/recepisse_declaration',
                
                // Plans de localisation
                'plan_localisation_signataire1_image' => 'clients/localisation/signataires',
                'plan_localisation_signataire2_image' => 'clients/localisation/signataires',
                'plan_localisation_signataire3_image' => 'clients/localisation/signataires',
                'plan_localisation_siege_image' => 'clients/localisation/siege',
                
                // Factures eau
                'facture_eau_signataire1_image' => 'clients/factures/eau/signataires',
                'facture_eau_signataire2_image' => 'clients/factures/eau/signataires',
                'facture_eau_signataire3_image' => 'clients/factures/eau/signataires',
                'facture_eau_siege_image' => 'clients/factures/eau/siege',
                
                // Factures électricité
                'facture_electricite_signataire1_image' => 'clients/factures/electricite/signataires',
                'facture_electricite_signataire2_image' => 'clients/factures/electricite/signataires',
                'facture_electricite_signataire3_image' => 'clients/factures/electricite/signataires',
                'facture_electricite_siege_image' => 'clients/factures/electricite/siege',
            ];

            // Traitement des fichiers images
            foreach ($photoFields as $field => $path) {
                if ($request->hasFile($field)) {
                    $moraleData[$field] = $request->file($field)->store($path, 'public');
                }
            }
            
            // Traitement des fichiers PDF
            $pdfFields = [
                'acte_designation_signataires_pdf' => 'clients/documents/actes',
                'liste_conseil_administration_pdf' => 'clients/documents/conseil',
                'attestation_conformite_pdf' => 'clients/attestations/conformite',
            ];
            
            foreach ($pdfFields as $field => $path) {
                if ($request->hasFile($field)) {
                    $moraleData[$field] = $request->file($field)->store($path, 'public');
                }
            }

            $client->morale()->create($moraleData);

            // Charger les relations pour la réponse
            $client->load('morale');

            return response()->json([
                'success'    => true,
                'message'    => 'Client moral créé avec succès',
                'num_client' => $numClient,
                'data'       => $client
            ], 201);
        });
    }

    /**
     * AFFICHER UN CLIENT SPÉCIFIQUE
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $client = Client::with(['physique', 'morale', 'agency'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $client
        ]);
    }

    /**
     * MISE À JOUR COMPLÈTE DU CLIENT
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            // Vérifier l'autorisation
            $user = Auth::user();
            if (!$user || !$user->can('gestion des clients')) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }

            // 1. Charger le client avec ses relations
            $client = Client::with(['physique', 'morale'])->findOrFail($id);

            // 2. Valider les données selon le type de client
            $validationRules = $this->getValidationRulesForUpdate($client->type_client);
            
            // Ajouter les règles pour les fichiers communs
            $fileRules = [
                'photo_localisation_domicile' => 'nullable|image|max:2048',
                'photo_localisation_activite' => 'nullable|image|max:2048'
            ];
            
            // Ajouter les règles pour les fichiers spécifiques
            if ($client->type_client === 'physique') {
                $physiqueFileRules = [
                    'photo' => 'nullable|image|max:2048',
                    'signature' => 'nullable|image|max:2048',
                    'cni_recto' => 'nullable|image|max:2048',
                    'cni_verso' => 'nullable|image|max:2048',
                    'nui_image' => 'nullable|image|max:2048',
                    'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120'
                ];
                $fileRules = array_merge($fileRules, $physiqueFileRules);
            }
            
            // Ajouter les règles pour les fichiers du client moral
            if ($client->type_client === 'morale') {
                $moralFileRules = [
                    'photo_gerant' => 'nullable|image|max:2048',
                    'photo_gerant2' => 'nullable|image|max:2048',
                    'photo_signataire' => 'nullable|image|max:2048',
                    'photo_signataire2' => 'nullable|image|max:2048',
                    'photo_signataire3' => 'nullable|image|max:2048',
                    'signature_signataire' => 'nullable|image|max:2048',
                    'signature_signataire2' => 'nullable|image|max:2048',
                    'signature_signataire3' => 'nullable|image|max:2048',
                    
                    // Documents administratifs - Images
                    'extrait_rccm_image' => 'nullable|image|max:2048',
                    'titre_patente_image' => 'nullable|image|max:2048',
                    'nui_image' => 'nullable|image|max:2048',
                    'statuts_image' => 'nullable|image|max:2048',
                    'pv_agc_image' => 'nullable|image|max:2048',
                    'attestation_non_redevance_image' => 'nullable|image|max:2048',
                    'proces_verbal_image' => 'nullable|image|max:2048',
                    'registre_coop_gic_image' => 'nullable|image|max:2048',
                    'recepisse_declaration_association_image' => 'nullable|image|max:2048',
                    
                    // Documents administratifs - PDF
                    'acte_designation_signataires_pdf' => 'nullable|mimes:pdf|max:5120',
                    'liste_conseil_administration_pdf' => 'nullable|mimes:pdf|max:5120',
                    'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120',
                    
                    // Plans de localisation
                    'plan_localisation_signataire1_image' => 'nullable|image|max:2048',
                    'plan_localisation_signataire2_image' => 'nullable|image|max:2048',
                    'plan_localisation_signataire3_image' => 'nullable|image|max:2048',
                    'plan_localisation_siege_image' => 'nullable|image|max:2048',
                    
                    // Factures
                    'facture_eau_signataire1_image' => 'nullable|image|max:2048',
                    'facture_eau_signataire2_image' => 'nullable|image|max:2048',
                    'facture_eau_signataire3_image' => 'nullable|image|max:2048',
                    'facture_electricite_signataire1_image' => 'nullable|image|max:2048',
                    'facture_electricite_signataire2_image' => 'nullable|image|max:2048',
                    'facture_electricite_signataire3_image' => 'nullable|image|max:2048',
                    'facture_eau_siege_image' => 'nullable|image|max:2048',
                    'facture_electricite_siege_image' => 'nullable|image|max:2048',
                ];
                $fileRules = array_merge($fileRules, $moralFileRules);
            }
            
            $validationRules = array_merge($validationRules, $fileRules);
            
            // Pour les champs uniques (CNI, RCCM, NUI), exclure le client actuel
            if ($client->type_client === 'physique' && $client->physique) {
                if ($request->has('cni_numero')) {
                    $validationRules['cni_numero'] = [
                        'nullable',
                        'string',
                        'max:50',
                        Rule::unique('clients_physiques', 'cni_numero')->ignore($client->physique->id)
                    ];
                }
                
                if ($request->has('nui')) {
                    $validationRules['nui'] = [
                        'nullable',
                        'string',
                        'max:50',
                        Rule::unique('clients_physiques', 'nui')->ignore($client->physique->id)
                    ];
                }
            }
            
            if ($client->type_client === 'morale' && $client->morale) {
                if ($request->has('rccm')) {
                    $validationRules['rccm'] = [
                        'nullable',
                        'string',
                        'max:100',
                        Rule::unique('clients_morales', 'rccm')->ignore($client->morale->id)
                    ];
                }
                
                if ($request->has('nui')) {
                    $validationRules['nui'] = [
                        'nullable',
                        'string',
                        'max:50',
                        Rule::unique('clients_morales', 'nui')->ignore($client->morale->id)
                    ];
                }
            }

            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // 3. Mise à jour de la table principale 'clients'
            $clientData = [];
            
            // Champs communs du client principal
            $mainFields = [
                'telephone', 'email', 'adresse_ville', 'adresse_quartier',
                'lieu_dit_domicile', 'lieu_dit_activite', 'ville_activite',
                'quartier_activite', 'bp', 'pays_residence', 'solde_initial',
                'immobiliere', 'autres_biens'
            ];
            
            foreach ($mainFields as $field) {
                if ($request->has($field)) {
                    $clientData[$field] = $request->input($field);
                }
            }

            // Gestion des fichiers du client principal
            if ($request->hasFile('photo_localisation_domicile')) {
                // Supprimer l'ancienne photo si elle existe
                if ($client->photo_localisation_domicile && Storage::disk('public')->exists($client->photo_localisation_domicile)) {
                    Storage::disk('public')->delete($client->photo_localisation_domicile);
                }
                $clientData['photo_localisation_domicile'] = $request->file('photo_localisation_domicile')
                    ->store('clients/localisation/domicile', 'public');
            }

            if ($request->hasFile('photo_localisation_activite')) {
                // Supprimer l'ancienne photo si elle existe
                if ($client->photo_localisation_activite && Storage::disk('public')->exists($client->photo_localisation_activite)) {
                    Storage::disk('public')->delete($client->photo_localisation_activite);
                }
                $clientData['photo_localisation_activite'] = $request->file('photo_localisation_activite')
                    ->store('clients/localisation/activite', 'public');
            }

            // Mettre à jour le client principal
            if (!empty($clientData)) {
                $client->update($clientData);
            }

            // 4. Cas du Client PHYSIQUE
            if ($client->type_client === 'physique' && $client->physique) {
                $physiqueData = [];
                
                // Champs du client physique
                $physiqueFields = [
                    'nom_prenoms', 'sexe', 'date_naissance', 'lieu_naissance',
                    'nationalite', 'nui', 'cni_numero', 'cni_delivrance',
                    'cni_expiration', 'nom_pere', 'nom_mere', 'nationalite_pere',
                    'nationalite_mere', 'profession', 'employeur', 'situation_familiale',
                    'regime_matrimonial', 'nom_conjoint', 'date_naissance_conjoint',
                    'cni_conjoint', 'profession_conjoint', 'salaire', 'tel_conjoint'
                ];
                
                foreach ($physiqueFields as $field) {
                    if ($request->has($field)) {
                        $physiqueData[$field] = $request->input($field);
                    }
                }

                // Gestion des fichiers pour le client physique
                $physiqueFileFields = [
                    'photo' => 'clients/photos',
                    'signature' => 'clients/signatures',
                    'cni_recto' => 'clients/cni/recto',
                    'cni_verso' => 'clients/cni/verso',
                    'nui_image' => 'clients/nui',
                    'attestation_conformite_pdf' => 'clients/attestations/conformite',
                ];
                
                foreach ($physiqueFileFields as $field => $path) {
                    if ($request->hasFile($field)) {
                        // Supprimer l'ancien fichier si il existe
                        $oldFile = $client->physique->$field ?? null;
                        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
                            Storage::disk('public')->delete($oldFile);
                        }
                        $physiqueData[$field] = $request->file($field)->store($path, 'public');
                    }
                }

                // Mettre à jour les détails du client physique
                if (!empty($physiqueData)) {
                    $client->physique->update($physiqueData);
                }
            }

            // 5. Cas du Client MORAL
            if ($client->type_client === 'morale' && $client->morale) {
                $moraleData = [];
                
                $moraleFields = [
                    'raison_sociale', 'sigle', 'forme_juridique', 'type_entreprise',
                    'rccm', 'nui', 'nom_gerant', 'telephone_gerant',
                    'nom_gerant2', 'telephone_gerant2',
                    'nom_signataire', 'telephone_signataire',
                    'nom_signataire2', 'telephone_signataire2',
                    'nom_signataire3', 'telephone_signataire3',
                    
                    // Documents administratifs - Images
                    'extrait_rccm_image', 'titre_patente_image', 'nui_image',
                    'statuts_image', 'pv_agc_image', 'attestation_non_redevance_image',
                    'proces_verbal_image', 'registre_coop_gic_image', 'recepisse_declaration_association_image',
                    
                    // Documents administratifs - PDF
                    'acte_designation_signataires_pdf', 'liste_conseil_administration_pdf',
                    'attestation_conformite_pdf',
                    
                    // Plans localisation
                    'plan_localisation_signataire1_image', 'plan_localisation_signataire2_image', 'plan_localisation_signataire3_image',
                    'plan_localisation_siege_image',
                    
                    // Factures eau
                    'facture_eau_signataire1_image', 'facture_eau_signataire2_image', 'facture_eau_signataire3_image',
                    'facture_eau_siege_image',
                    
                    // Factures électricité
                    'facture_electricite_signataire1_image', 'facture_electricite_signataire2_image', 'facture_electricite_signataire3_image',
                    'facture_electricite_siege_image',
                ];
                
                foreach ($moraleFields as $field) {
                    if ($request->has($field)) {
                        $moraleData[$field] = $request->input($field);
                    }
                }
                
                // Gestion des fichiers pour le client moral
                $moralFileFields = [
                    'photo_gerant' => 'clients/gerants',
                    'photo_gerant2' => 'clients/gerants',
                    'photo_signataire' => 'clients/signataires/photos',
                    'photo_signataire2' => 'clients/signataires/photos',
                    'photo_signataire3' => 'clients/signataires/photos',
                    'signature_signataire' => 'clients/signataires/signatures',
                    'signature_signataire2' => 'clients/signataires/signatures',
                    'signature_signataire3' => 'clients/signataires/signatures',
                    
                    // Documents administratifs - Images
                    'extrait_rccm_image' => 'clients/documents/extrait_rccm',
                    'titre_patente_image' => 'clients/documents/titre_patente',
                    'nui_image' => 'clients/documents/nui',
                    'statuts_image' => 'clients/documents/statuts',
                    'pv_agc_image' => 'clients/documents/pv_agc',
                    'attestation_non_redevance_image' => 'clients/documents/attestation_non_redevance',
                    'proces_verbal_image' => 'clients/documents/proces_verbal',
                    'registre_coop_gic_image' => 'clients/documents/registre_coop_gic',
                    'recepisse_declaration_association_image' => 'clients/documents/recepisse_declaration',
                    
                    // Plans localisation
                    'plan_localisation_signataire1_image' => 'clients/localisation/signataires',
                    'plan_localisation_signataire2_image' => 'clients/localisation/signataires',
                    'plan_localisation_signataire3_image' => 'clients/localisation/signataires',
                    'plan_localisation_siege_image' => 'clients/localisation/siege',
                    
                    // Factures eau
                    'facture_eau_signataire1_image' => 'clients/factures/eau/signataires',
                    'facture_eau_signataire2_image' => 'clients/factures/eau/signataires',
                    'facture_eau_signataire3_image' => 'clients/factures/eau/signataires',
                    'facture_eau_siege_image' => 'clients/factures/eau/siege',
                    
                    // Factures électricité
                    'facture_electricite_signataire1_image' => 'clients/factures/electricite/signataires',
                    'facture_electricite_signataire2_image' => 'clients/factures/electricite/signataires',
                    'facture_electricite_signataire3_image' => 'clients/factures/electricite/signataires',
                    'facture_electricite_siege_image' => 'clients/factures/electricite/siege',
                    
                    // PDF
                    'acte_designation_signataires_pdf' => 'clients/documents/actes',
                    'liste_conseil_administration_pdf' => 'clients/documents/conseil',
                    'attestation_conformite_pdf' => 'clients/attestations/conformite',
                ];
                
                foreach ($moralFileFields as $field => $path) {
                    if ($request->hasFile($field)) {
                        // Supprimer l'ancien fichier si il existe
                        $oldFile = $client->morale->$field ?? null;
                        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
                            Storage::disk('public')->delete($oldFile);
                        }
                        $moraleData[$field] = $request->file($field)->store($path, 'public');
                    }
                }
                
                if (!empty($moraleData)) {
                    $client->morale->update($moraleData);
                }
            }

            // Recharger les relations pour la réponse
            $client->load(['physique', 'morale', 'agency']);

            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès',
                'data'    => $client
            ]);
        });
    }

    /**
     * Obtenir les règles de validation pour la mise à jour
     */
    private function getValidationRulesForUpdate($type)
    {
        $commonRules = [
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse_ville' => 'nullable|string|max:255',
            'adresse_quartier' => 'nullable|string|max:255',
            'lieu_dit_domicile' => 'nullable|string|max:255',
            'lieu_dit_activite' => 'nullable|string|max:255',
            'ville_activite' => 'nullable|string|max:255',
            'quartier_activite' => 'nullable|string|max:255',
            'bp' => 'nullable|string|max:50',
            'pays_residence' => 'nullable|string|max:100',
            'solde_initial' => 'nullable|numeric|min:0',
            'immobiliere' => 'nullable|string',
            'autres_biens' => 'nullable|string',
        ];

        if ($type === 'physique') {
            $physiqueRules = [
                'nom_prenoms' => 'nullable|string|max:255',
                'sexe' => 'nullable|in:M,F',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string|max:255',
                'nationalite' => 'nullable|string|max:100',
                'nui' => 'nullable|string|max:50',
                'cni_numero' => 'nullable|string|max:50',
                'cni_delivrance' => 'nullable|date',
                'cni_expiration' => 'nullable|date',
                'nom_pere' => 'nullable|string|max:255',
                'nom_mere' => 'nullable|string|max:255',
                'nationalite_pere' => 'nullable|string|max:100',
                'nationalite_mere' => 'nullable|string|max:100',
                'profession' => 'nullable|string|max:255',
                'employeur' => 'nullable|string|max:255',
                'situation_familiale' => 'nullable|string|max:100',
                'regime_matrimonial' => 'nullable|string|max:100',
                'nom_conjoint' => 'nullable|string|max:255',
                'date_naissance_conjoint' => 'nullable|date',
                'cni_conjoint' => 'nullable|string|max:50',
                'profession_conjoint' => 'nullable|string|max:255',
                'salaire' => 'nullable|numeric|min:0',
                'tel_conjoint' => 'nullable|string|max:20',
            ];
            
            return array_merge($commonRules, $physiqueRules);
        }

        $moraleRules = [
            'raison_sociale' => 'nullable|string|max:255',
            'sigle' => 'nullable|string|max:100',
            'forme_juridique' => 'nullable|string|max:100',
            'type_entreprise' => 'nullable|in:entreprise,association',
            'rccm' => 'nullable|string|max:100',
            'nui' => 'nullable|string|max:50',
            
            // Gérants
            'nom_gerant' => 'nullable|string|max:255',
            'telephone_gerant' => 'nullable|string|max:20',
            'nom_gerant2' => 'nullable|string|max:255',
            'telephone_gerant2' => 'nullable|string|max:20',
            
            // Signataires
            'nom_signataire' => 'nullable|string|max:255',
            'telephone_signataire' => 'nullable|string|max:20',
            'nom_signataire2' => 'nullable|string|max:255',
            'telephone_signataire2' => 'nullable|string|max:20',
            'nom_signataire3' => 'nullable|string|max:255',
            'telephone_signataire3' => 'nullable|string|max:20',
        ];

        return array_merge($commonRules, $moraleRules);
    }

    /**
     * SUPPRESSION LOGIQUE DU CLIENT
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole(['DG', 'Admin'])) {
            return response()->json([
                'message' => 'Seul le DG ou l\'Administrateur peut supprimer un client'
            ], 403);
        }

        $client = Client::findOrFail($id);

        // Marquer le client comme supprimé
        $client->marquerCommeSupprime();

        return response()->json([
            'success' => true,
            'message' => 'Client marqué comme supprimé avec succès',
            'data'    => $client
        ]);
    }

    /**
     * RESTAURER UN CLIENT SUPPRIMÉ
     */
    public function restaurer($id)
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole(['DG', 'Admin'])) {
            return response()->json([
                'message' => 'Seul le DG ou l\'Administrateur peut restaurer un client'
            ], 403);
        }

        $client = Client::supprimes()->findOrFail($id);
        $client->restaurer();

        return response()->json([
            'success' => true,
            'message' => 'Client restauré avec succès',
            'data'    => $client->load(['physique', 'morale', 'agency'])
        ]);
    }

    /**
     * LISTE DES CLIENTS SUPPRIMÉS
     */
    public function supprimes()
    {
        $user = Auth::user();

        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $clients = Client::with(['physique', 'morale', 'agency'])
            ->supprimes()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $clients->count(),
            'data'    => $clients
        ]);
    }

    /**
     * OBTENIR LE PROCHAIN NUMÉRO DE CLIENT
     */
    public function getNextNumber($agencyId)
    {
        $nextNumber = \App\Services\ClientNumberService::generate($agencyId);
        
        return response()->json([
            'success' => true,
            'next_number' => $nextNumber
        ]);
    }

    /**
     * RECHERCHER UN CLIENT
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $search = $request->get('search', '');
        
        if (empty($search)) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez fournir un terme de recherche'
            ], 400);
        }

        $clients = Client::with(['physique', 'morale', 'agency'])
            ->actifs()
            ->where(function ($query) use ($search) {
                $query->where('num_client', 'LIKE', "%{$search}%")
                    ->orWhere('telephone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('physique', function ($q) use ($search) {
                        $q->where('nom_prenoms', 'LIKE', "%{$search}%")
                          ->orWhere('cni_numero', 'LIKE', "%{$search}%")
                          ->orWhere('nui', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('morale', function ($q) use ($search) {
                        $q->where('raison_sociale', 'LIKE', "%{$search}%")
                          ->orWhere('rccm', 'LIKE', "%{$search}%")
                          ->orWhere('sigle', 'LIKE', "%{$search}%")
                          ->orWhere('nui', 'LIKE', "%{$search}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $clients->count(),
            'data'    => $clients
        ]);
    }
}
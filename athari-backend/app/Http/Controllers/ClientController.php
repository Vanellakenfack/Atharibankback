<?php

namespace App\Http\Controllers;

use App\Models\Client\Client;
use App\Models\Client\ClientMorale;
use App\Models\Client\ClientPhysique;
use App\Models\Client\ClientSignataire;
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

    $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        // 1. Valider les données JSON
        $validator = validator($request->all(), [
            'agency_id' => 'nullable|exists:agencies,id',
            'nom_prenoms' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
            'cni_numero' => 'nullable|string|unique:clients_physiques,cni_numero',
            'telephone' => 'nullable|string',
            'adresse_ville' => 'nullable|string',
            'adresse_quartier' => 'nullable|string',
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
            'niu_image' => 'nullable|image|max:2048',
            'attestation_conformite_pdf' => 'nullable|mimes:pdf|max:5120',
            // NOUVEAUX CHAMPS COMMUNS (sans liste_membres)
            // agency_id est requis SAUF SI client_id est présent
    
    // client_id est optionnel mais doit exister si fourni
    'client_id' => 'nullable|exists:clients,id',
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
            
           if (!empty($cniSaisie)) {
                $doublonCni = ClientPhysique::where('cni_numero', $cniSaisie)->exists();

                if ($doublonCni) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un client avec le même numéro de CNI existe déjà'
                    ], 422);
                }
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

            // Gestion des fichiers PDF communs pour le client principal
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
            if ($request->hasFile('niu_image')) {
                $physiqueData['niu_image'] = $request->file('niu_image')->store('clients/nui', 'public');
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
     * CRÉATION CLIENT MORAL - VERSION CORRIGÉE
     */
    public function storeMorale(Request $request)
    {
        // 1. Valider les données
        $validator = validator($request->all(), [
            'agency_id' => 'nullable|exists:agencies,id',
            'raison_sociale' => 'nullable|string|max:255',
            'forme_juridique' => 'nullable|string',
            'type_entreprise' => 'nullable|in:entreprise,association',
            'rccm' => 'nullable|string|unique:clients_morales,rccm',
            'nui' => 'nullable|string',
            'nom_gerant' => 'nullable|string',
            'telephone_gerant' => 'nullable|string',
            'photo_gerant' => 'nullable|image|max:2048',
            
            // Gérant 2
            'nom_gerant2' => 'nullable|string',
            'telephone_gerant2' => 'nullable|string',
            'photo_gerant2' => 'nullable|image|max:2048',
            
            // Informations de contact
            'telephone' => 'nullable|string',
            'adresse_ville' => 'nullable|string',
            'adresse_quartier' => 'nullable|string',
            'email' => 'nullable|email',
            'sigle' => 'nullable|string',
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
            
            // NOUVEAUX CHAMPS COMMUNS
            'liste_membres_pdf' => 'nullable|mimes:pdf|max:5120',
            
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
           // $doublonRccm = DB::table('clients_morales')
             //   ->where('rccm', $request->rccm)
             //  ->exists();
        
           // if ($doublonRccm) {
           //      return response()->json([
           //          'success' => false,
           //          'message' => 'Une entreprise avec le même numéro RCCM existe déjà'
           //      ], 422);
           //  }

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

            // Récupération des signataires de la requête FormData
            $signataires = [];

            // Méthode 1: Si votre frontend envoie les signataires sous forme de tableau
            if ($request->has('signataires') && is_array($request->signataires)) {
                foreach ($request->signataires as $index => $signataire) {
                    if (!empty($signataire['nom'])) {
                        $signataires[$index + 1] = [
                            'numero_signataire' => $index + 1,
                            'nom' => $signataire['nom'] ?? null,
                            'sexe' => $signataire['sexe'] ?? null,
                            'ville' => $signataire['ville'] ?? null,
                            'quartier' => $signataire['quartier'] ?? null,
                            'lieu_domicile' => $signataire['lieu_domicile'] ?? null,
                            'lieu_dit_domicile' => $signataire['lieu_dit_domicile'] ?? null,
                            'telephone' => $signataire['telephone'] ?? null,
                            'email' => $signataire['email'] ?? null,
                            'cni' => $signataire['cni'] ?? null,
                            'nui' => $signataire['nui'] ?? null,
                        ];
                    
                        // Gestion des fichiers pour chaque signataire
                        $fileFields = [
                            'photo' => "signataires.{$index}.photo",
                            'signature' => "signataires.{$index}.signature",
                            'lieu_dit_domicile_photo' => "signataires.{$index}.lieu_dit_domicile_photo",
                            'photo_localisation_domicile' => "signataires.{$index}.photo_localisation_domicile",
                            'cni_photo_recto' => "signataires.{$index}.cni_photo_recto",
                            'cni_photo_verso' => "signataires.{$index}.cni_photo_verso",
                            'nui_image' => "signataires.{$index}.nui_image",
                            'plan_localisation_image' => "signataires.{$index}.plan_localisation_image",
                            'facture_eau_image' => "signataires.{$index}.facture_eau_image",
                            'facture_electricite_image' => "signataires.{$index}.facture_electricite_image",
                        ];
                    
                        foreach ($fileFields as $dbField => $requestField) {
                            if ($request->hasFile($requestField)) {
                                $signataires[$index + 1][$dbField] = $request->file($requestField)->store("clients/signataires/{$dbField}", 'public');
                            }
                        }
                    }
                }
            } 
            // Méthode 2: Si votre frontend envoie les signataires individuellement (signataire1, signataire2, etc.)
            else {
                for ($i = 1; $i <= 3; $i++) {
                    $nomField = "nom_signataire" . ($i > 1 ? $i : '');
                    if ($request->filled($nomField)) {
                        $signataires[$i] = [
                            'numero_signataire' => $i,
                            'nom' => $request->input($nomField),
                            'sexe' => $request->input("sexe_signataire" . ($i > 1 ? $i : '')),
                            'ville' => $request->input("ville_signataire" . ($i > 1 ? $i : '')),
                            'quartier' => $request->input("quartier_signataire" . ($i > 1 ? $i : '')),
                            'lieu_domicile' => $request->input("lieu_domicile_signataire" . ($i > 1 ? $i : '')),
                            'lieu_dit_domicile' => $request->input("lieu_dit_domicile_signataire" . ($i > 1 ? $i : '')),
                            'telephone' => $request->input("telephone_signataire" . ($i > 1 ? $i : '')),
                            'email' => $request->input("email_signataire" . ($i > 1 ? $i : '')),
                            'cni' => $request->input("cni_signataire" . ($i > 1 ? $i : '')),
                            'nui' => $request->input("nui_signataire" . ($i > 1 ? $i : '')),
                        ];
                    
                        // Gestion des fichiers pour chaque signataire
                        $fileMappings = [
                            1 => [
                                'photo' => 'photo_signataire',
                                'signature' => 'signature_signataire',
                                'lieu_dit_domicile_photo' => 'lieu_dit_domicile_photo_signataire',
                                'photo_localisation_domicile' => 'photo_localisation_domicile_signataire',
                                'cni_photo_recto' => 'cni_photo_recto_signataire',
                                'cni_photo_verso' => 'cni_photo_verso_signataire',
                                'nui_image' => 'nui_image_signataire',
                                'plan_localisation_image' => 'plan_localisation_signataire1_image',
                                'facture_eau_image' => 'facture_eau_signataire1_image',
                                'facture_electricite_image' => 'facture_electricite_signataire1_image',
                            ],
                            2 => [
                                'photo' => 'photo_signataire2',
                                'signature' => 'signature_signataire2',
                                'lieu_dit_domicile_photo' => 'lieu_dit_domicile_photo_signataire2',
                                'photo_localisation_domicile' => 'photo_localisation_domicile_signataire2',
                                'cni_photo_recto' => 'cni_photo_recto_signataire2',
                                'cni_photo_verso' => 'cni_photo_verso_signataire2',
                                'nui_image' => 'nui_image_signataire2',
                                'plan_localisation_image' => 'plan_localisation_signataire2_image',
                                'facture_eau_image' => 'facture_eau_signataire2_image',
                                'facture_electricite_image' => 'facture_electricite_signataire2_image',
                            ],
                            3 => [
                                'photo' => 'photo_signataire3',
                                'signature' => 'signature_signataire3',
                                'lieu_dit_domicile_photo' => 'lieu_dit_domicile_photo_signataire3',
                                'photo_localisation_domicile' => 'photo_localisation_domicile_signataire3',
                                'cni_photo_recto' => 'cni_photo_recto_signataire3',
                                'cni_photo_verso' => 'cni_photo_verso_signataire3',
                                'nui_image' => 'nui_image_signataire3',
                                'plan_localisation_image' => 'plan_localisation_signataire3_image',
                                'facture_eau_image' => 'facture_eau_signataire3_image',
                                'facture_electricite_image' => 'facture_electricite_signataire3_image',
                            ]
                        ];
                    
                        foreach ($fileMappings[$i] as $dbField => $requestField) {
                            if ($request->hasFile($requestField)) {
                                $signataires[$i][$dbField] = $request->file($requestField)->store("clients/signataires/{$dbField}", 'public');
                            }
                        }
                    }
                }
            }

            // Vérification de doublon par CNI des signataires
            foreach ($signataires as $signataire) {
                if (!empty($signataire['cni'])) {
                    $doublonCni = DB::table('client_signataires')
                        ->where('cni', $signataire['cni'])
                        ->exists();
                
                    if ($doublonCni) {
                        return response()->json([
                            'success' => false,
                            'message' => "Un signataire avec le numéro de CNI {$signataire['cni']} existe déjà"
                        ], 422);
                    }
                }
            }

            // Vérification de doublon par CNI des signataires
            $signataireCNIs = array_filter([
                $request->input('cni_signataire'),
                $request->input('cni_signataire2'),
                $request->input('cni_signataire3')
            ]);
        
            foreach ($signataireCNIs as $cni) {
                $doublonCni = DB::table('client_signataires')
                    ->where('cni', $cni)
                    ->exists();
            
                if ($doublonCni) {
                    return response()->json([
                        'success' => false,
                        'message' => "Un signataire avec le numéro de CNI {$cni} existe déjà"
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
                // NOUVEAUX CHAMPS COMMUNS
                'liste_membres_pdf' => null,
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

            // Gestion des fichiers PDF communs pour le client principal
            if ($request->hasFile('liste_membres_pdf')) {
                $clientData['liste_membres_pdf'] = $request->file('liste_membres_pdf')
                    ->store('clients/documents/liste_membres', 'public');
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
            
                // NOUVEAUX CHAMPS COMMUNS
                'liste_membres_pdf' => $clientData['liste_membres_pdf'] ?? null,
            ];

            // Gestion des fichiers pour les photos existantes
            $photoFields = [
                'photo_gerant' => 'clients/gerants',
                'photo_gerant2' => 'clients/gerants',
            
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

            $clientMorale = $client->morale()->create($moraleData);
            
            // CORRECTION ICI - Création des signataires avec client_id
            foreach ($signataires as $signataireData) {
                // Ajouter client_id aux données du signataire
                $signataireData['client_id'] = $client->id;
                
                // Créer le signataire
                $clientMorale->signataires()->create($signataireData);
            } 

            // Charger les relations pour la réponse
            $client->load(['morale.signataires']);

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

        $client = Client::with(['physique', 'morale.signataires', 'agency'])->findOrFail($id);

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
            $client = Client::with(['physique', 'morale.signataires'])->findOrFail($id);

            // 2. Valider les données selon le type de client
            $validationRules = $this->getValidationRulesForUpdate($client->type_client);
            
            // Ajouter les règles pour les fichiers communs du client principal
            $fileRules = [
                'photo_localisation_domicile' => 'nullable|image|max:2048',
                'photo_localisation_activite' => 'nullable|image|max:2048',
                // NOUVEAUX FICHIERS COMMUNS
            ];
            
            // Ajouter liste_membres_pdf seulement pour les clients moraux
            if ($client->type_client === 'morale') {
                $fileRules['liste_membres_pdf'] = 'nullable|mimes:pdf|max:5120';
                
                // Règles pour les signataires
                for ($i = 1; $i <= 3; $i++) {
                    $fileRules["photo_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["signature_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["lieu_dit_domicile_photo_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["photo_localisation_domicile_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["cni_photo_recto_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["cni_photo_verso_signataire{$i}"] = 'nullable|image|max:2048';
                    $fileRules["nui_image_signataire{$i}"] = 'nullable|image|max:2048';
                }
            }
            
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
                
                if ($request->has('cni_conjoint')) {
                    $validationRules['cni_conjoint'] = [
                        'nullable',
                        'string',
                        'max:50',
                        Rule::unique('clients_physiques', 'cni_conjoint')->ignore($client->physique->id)
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
                
                // Validation pour les CNI des signataires
                for ($i = 1; $i <= 3; $i++) {
                    if ($request->has("cni_signataire{$i}")) {
                        $validationRules["cni_signataire{$i}"] = [
                            'nullable',
                            'string',
                            'max:50',
                        ];
                    }
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

            // Gestion des fichiers PDF communs pour le client principal
            if ($request->hasFile('liste_membres_pdf') && $client->type_client === 'morale') {
                // Supprimer l'ancien fichier si il existe
                if ($client->liste_membres_pdf && Storage::disk('public')->exists($client->liste_membres_pdf)) {
                    Storage::disk('public')->delete($client->liste_membres_pdf);
                }
                $clientData['liste_membres_pdf'] = $request->file('liste_membres_pdf')
                    ->store('clients/documents/liste_membres', 'public');
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
                    
                    // NOUVEAUX CHAMPS COMMUNS
                    'liste_membres_pdf',
                    
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
                    } elseif (in_array($field, ['liste_membres_pdf']) && isset($clientData[$field])) {
                        $moraleData[$field] = $clientData[$field];
                    }
                }
                
                // Mettre à jour les détails du client moral
                if (!empty($moraleData)) {
                    $client->morale->update($moraleData);
                }
                
                // MISE À JOUR DES SIGNATAIRES
                for ($i = 1; $i <= 3; $i++) {
                    if ($request->has("nom_signataire{$i}")) {
                        $signataireData = [
                            'nom' => $request->input("nom_signataire{$i}"),
                            'sexe' => $request->input("sexe_signataire{$i}"),
                            'ville' => $request->input("ville_signataire{$i}"),
                            'quartier' => $request->input("quartier_signataire{$i}"),
                            'lieu_domicile' => $request->input("lieu_domicile_signataire{$i}"),
                            'lieu_dit_domicile' => $request->input("lieu_dit_domicile_signataire{$i}"),
                            'telephone' => $request->input("telephone_signataire{$i}"),
                            'email' => $request->input("email_signataire{$i}"),
                            'cni' => $request->input("cni_signataire{$i}"),
                            'nui' => $request->input("nui_signataire{$i}"),
                        ];
                        
                        // Chercher le signataire existant
                        $signataire = $client->morale->signataires()->where('numero_signataire', $i)->first();
                        
                        // Gestion des fichiers pour le signataire
                        $signataireFileFields = [
                            "photo_signataire{$i}" => 'photo',
                            "signature_signataire{$i}" => 'signature',
                            "lieu_dit_domicile_photo_signataire{$i}" => 'lieu_dit_domicile_photo',
                            "photo_localisation_domicile_signataire{$i}" => 'photo_localisation_domicile',
                            "cni_photo_recto_signataire{$i}" => 'cni_photo_recto',
                            "cni_photo_verso_signataire{$i}" => 'cni_photo_verso',
                            "nui_image_signataire{$i}" => 'nui_image',
                        ];
                        
                        foreach ($signataireFileFields as $requestField => $dbField) {
                            if ($request->hasFile($requestField)) {
                                // Supprimer l'ancien fichier si il existe
                                if ($signataire && $signataire->$dbField && Storage::disk('public')->exists($signataire->$dbField)) {
                                    Storage::disk('public')->delete($signataire->$dbField);
                                }
                                $signataireData[$dbField] = $request->file($requestField)->store("clients/signataires/{$dbField}", 'public');
                            }
                        }
                        
                        if ($signataire) {
                            // Mettre à jour le signataire existant
                            $signataire->update($signataireData);
                        } elseif (!empty($signataireData['nom'])) {
                            // Créer un nouveau signataire
                            $signataireData['numero_signataire'] = $i;
                            $signataireData['client_id'] = $client->id; // AJOUTER client_id
                            $client->morale->signataires()->create($signataireData);
                        }
                    }
                }
            }

            // Recharger les relations pour la réponse
            $client->load(['physique', 'morale.signataires', 'agency']);

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
        ];

        // Ajouter les règles pour les signataires
        for ($i = 1; $i <= 3; $i++) {
            $moraleRules["nom_signataire{$i}"] = 'nullable|string|max:255';
            $moraleRules["sexe_signataire{$i}"] = 'nullable|in:M,F';
            $moraleRules["ville_signataire{$i}"] = 'nullable|string|max:100';
            $moraleRules["quartier_signataire{$i}"] = 'nullable|string|max:100';
            $moraleRules["lieu_domicile_signataire{$i}"] = 'nullable|string|max:200';
            $moraleRules["lieu_dit_domicile_signataire{$i}"] = 'nullable|string|max:200';
            $moraleRules["email_signataire{$i}"] = 'nullable|email|max:100';
            $moraleRules["cni_signataire{$i}"] = 'nullable|string|max:20';
            $moraleRules["telephone_signataire{$i}"] = 'nullable|string|max:20';
            $moraleRules["nui_signataire{$i}"] = 'nullable|string|max:20';
        }

        return array_merge($commonRules, $moraleRules);
    }

    /**
     * SUPPRESSION LOGIQUE DU CLIENT
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
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
        });
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
            'data'    => $client->load(['physique', 'morale.signataires', 'agency'])
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

        $clients = Client::with(['physique', 'morale.signataires', 'agency'])
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

        $clients = Client::with(['physique', 'morale.signataires', 'agency'])
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
                          ->orWhere('nui', 'LIKE', "%{$search}%")
                          ->orWhereHas('signataires', function ($sq) use ($search) {
                              $sq->where('nom', 'LIKE', "%{$search}%")
                                ->orWhere('cni', 'LIKE', "%{$search}%")
                                ->orWhere('nui', 'LIKE', "%{$search}%")
                                ->orWhere('telephone', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                          });
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

    /**
     * SUPPRIMER UN SIGNATAIRE
     */
    public function destroySignataire($clientId, $signataireId)
    {
        $user = Auth::user();
        
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $client = Client::findOrFail($clientId);
        
        if ($client->type_client !== 'morale') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients moraux ont des signataires'
            ], 422);
        }

        $signataire = ClientSignataire::where('client_morale_id', $client->morale->id)
            ->findOrFail($signataireId);

        // Supprimer les fichiers associés
        $filesToDelete = [
            'photo', 'signature', 'lieu_dit_domicile_photo', 
            'photo_localisation_domicile', 'cni_photo_recto', 
            'cni_photo_verso', 'nui_image'
        ];
        
        foreach ($filesToDelete as $fileField) {
            if ($signataire->$fileField && Storage::disk('public')->exists($signataire->$fileField)) {
                Storage::disk('public')->delete($signataire->$fileField);
            }
        }

        $signataire->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signataire supprimé avec succès'
        ]);
    }

    /**
     * AJOUTER OU METTRE À JOUR UN SIGNATAIRE
     */
    public function storeOrUpdateSignataire(Request $request, $clientId)
    {
        $user = Auth::user();
        
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $client = Client::with('morale')->findOrFail($clientId);
        
        if ($client->type_client !== 'morale') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients moraux ont des signataires'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'numero_signataire' => 'nullable|in:1,2,3',
            'nom' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'lieu_domicile' => 'nullable|string|max:200',
            'lieu_dit_domicile' => 'nullable|string|max:200',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'cni' => 'nullable|string|max:20',
            'nui' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
            'lieu_dit_domicile_photo' => 'nullable|image|max:2048',
            'photo_localisation_domicile' => 'nullable|image|max:2048',
            'cni_photo_recto' => 'nullable|image|max:2048',
            'cni_photo_verso' => 'nullable|image|max:2048',
            'nui_image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($request, $client) {
            $numero = $request->numero_signataire;
            
            // Vérifier l'unicité de la CNI
            if ($request->has('cni') && $request->cni) {
                $existingCni = ClientSignataire::where('cni', $request->cni)
                    ->where('id', '!=', $request->input('signataire_id'))
                    ->exists();
                
                if ($existingCni) {
                    return response()->json([
                        'success' => false,
                        'message' => "Un signataire avec le numéro de CNI {$request->cni} existe déjà"
                    ], 422);
                }
            }

            $signataireData = [
                'numero_signataire' => $numero,
                'nom' => $request->nom,
                'sexe' => $request->sexe,
                'ville' => $request->ville,
                'quartier' => $request->quartier,
                'lieu_domicile' => $request->lieu_domicile,
                'lieu_dit_domicile' => $request->lieu_dit_domicile,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'cni' => $request->cni,
                'nui' => $request->nui,
                'client_id' => $client->id, // AJOUTER client_id
            ];

            // Chercher le signataire existant
            $signataire = $client->morale->signataires()
                ->where('numero_signataire', $numero)
                ->first();

            // Gestion des fichiers
            $fileFields = [
                'photo' => 'photo',
                'signature' => 'signature',
                'lieu_dit_domicile_photo' => 'lieu_dit_domicile_photo',
                'photo_localisation_domicile' => 'photo_localisation_domicile',
                'cni_photo_recto' => 'cni_photo_recto',
                'cni_photo_verso' => 'cni_photo_verso',
                'nui_image' => 'nui_image',
            ];

            foreach ($fileFields as $requestField => $dbField) {
                if ($request->hasFile($requestField)) {
                    // Supprimer l'ancien fichier si il existe
                    if ($signataire && $signataire->$dbField && Storage::disk('public')->exists($signataire->$dbField)) {
                        Storage::disk('public')->delete($signataire->$dbField);
                    }
                    $signataireData[$dbField] = $request->file($requestField)->store("clients/signataires/{$dbField}", 'public');
                }
            }

            if ($signataire) {
                // Mettre à jour le signataire existant
                $signataire->update($signataireData);
                $message = 'Signataire mis à jour avec succès';
            } else {
                // Créer un nouveau signataire
                $signataireData['client_id'] = $client->id; // S'assurer que client_id est présent
                $client->morale->signataires()->create($signataireData);
                $message = 'Signataire ajouté avec succès';
            }

            // Recharger les relations
            $client->load(['morale.signataires']);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $client
            ]);
        });
    }

    public function exportPdf()
    {
        $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $clients = Client::with(['physique', 'morale', 'agency'])
            ->actifs()
            ->orderBy('created_at', 'desc')
            ->get();

        $clientsData = $clients->map(function ($client) {
            $data = [
                'id' => $client->id,
                'num_client' => $client->num_client,
                'type_client' => $client->type_client === 'physique' ? 'Particulier' : 'Entreprise',
                'telephone' => $client->telephone,
                'email' => $client->email,
                'adresse' => trim($client->adresse_ville . ' ' . $client->adresse_quartier),
                'ville_activite' => $client->ville_activite,
                'quartier_activite' => $client->quartier_activite,
                'bp' => $client->bp,
                'pays_residence' => $client->pays_residence,
                'solde_initial' => number_format($client->solde_initial, 0, ',', ' '),
                'date_creation' => $client->created_at->format('d/m/Y'),
                'agency' => $client->agency ? $client->agency->nom : 'Non assigné',
            ];

            if ($client->type_client === 'physique' && $client->physique) {
                $data['nom_complet'] = $client->physique->nom_prenoms;
                $data['sexe'] = $client->physique->sexe === 'M' ? 'Masculin' : 'Féminin';
                $data['date_naissance'] = $client->physique->date_naissance ? 
                    date('d/m/Y', strtotime($client->physique->date_naissance)) : null;
                $data['lieu_naissance'] = $client->physique->lieu_naissance;
                $data['nationalite'] = $client->physique->nationalite;
                $data['cni'] = $client->physique->cni_numero;
                $data['nui'] = $client->physique->nui;
                $data['profession'] = $client->physique->profession;
                $data['employeur'] = $client->physique->employeur;
                $data['situation_familiale'] = $client->physique->situation_familiale;
                $data['nom_conjoint'] = $client->physique->nom_conjoint;
                $data['photo'] = $client->physique->photo ? 
                    public_path('storage/' . $client->physique->photo) : null;
            } 
            elseif ($client->type_client === 'morale' && $client->morale) {
                $data['raison_sociale'] = $client->morale->raison_sociale;
                $data['sigle'] = $client->morale->sigle;
                $data['forme_juridique'] = $client->morale->forme_juridique;
                $data['type_entreprise'] = $client->morale->type_entreprise === 'entreprise' ? 
                    'Entreprise' : 'Association';
                $data['rccm'] = $client->morale->rccm;
                $data['nui'] = $client->morale->nui;
                $data['nom_gerant'] = $client->morale->nom_gerant;
                $data['telephone_gerant'] = $client->morale->telephone_gerant;
                $data['nom_gerant2'] = $client->morale->nom_gerant2;
                $data['telephone_gerant2'] = $client->morale->telephone_gerant2;
                $data['photo_gerant'] = $client->morale->photo_gerant ? 
                    public_path('storage/' . $client->morale->photo_gerant) : null;
                $data['nombre_signataires'] = $client->morale->signataires()->count();
            }

            return $data;
        });

        $stats = [
            'total_clients' => $clients->count(),
            'total_physiques' => $clients->where('type_client', 'physique')->count(),
            'total_morales' => $clients->where('type_client', 'morale')->count(),
            'date_export' => now()->format('d/m/Y H:i:s'),
            'exported_by' => $user->name ?? $user->email,
        ];

        // SOLUTION: Utiliser \PDF au lieu de Pdf
        $pdf = \PDF::loadView('pdf.clients-pdf', compact('clientsData', 'stats'));
        
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('liste-clients-' . date('Y-m-d') . '.pdf');
    }
}
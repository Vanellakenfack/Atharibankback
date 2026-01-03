<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StorePhysiqueClientRequest;
use App\Http\Requests\Client\StoreMoraleClientRequest;
use App\Models\Client\Client;
use App\Services\ClientNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // N'oubliez pas l'import en haut du fichier
class ClientController extends Controller
{
    /**
     * LISTE DES CLIENTS
     */
    public function index()
{
    $user = Auth::user();
    if (!$user || !$user->can('gestion des clients')) {
        return response()->json(['message' => 'Accès non autorisé'], 403);
    }

    $clients = Client::with(['physique', 'morale', 'agency'])->latest()->get();

    // Optionnel : Transformer les données pour inclure l'URL complète
    $clients->transform(function ($client) {
        if ($client->physique && $client->physique->photo) {
            $client->physique->photo_url = asset('storage/' . $client->physique->photo);
        }
        return $client;
    });

    return response()->json([
        'success' => true,
        'count'   => $clients->count(),
        'data'    => $clients
    ]);
}

    /**
     * CRÉATION CLIENT PHYSIQUE
     */
    public function storePhysique(StorePhysiqueClientRequest $request)
    {
        return $this->createClientProcess($request, 'physique');
    }

    /**
     * CRÉATION CLIENT MORAL
     */
    public function storeMorale(StoreMoraleClientRequest $request)
    {
        return $this->createClientProcess($request, 'morale');
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
     * ✅ MISE À JOUR COMPLÈTE DU CLIENT (CORRIGÉE)
     */

public function update(Request $request, $id)
{
    return DB::transaction(function () use ($request, $id) {

        // 1. Charger le client avec ses relations
        $client = Client::with(['physique', 'morale'])->findOrFail($id);

        // 2. Mise à jour de la table principale 'clients'
        $client->update($request->only([
            'telephone', 'email', 'adresse_ville', 'adresse_quartier',
            'bp', 'pays_residence', 'taxable', 'interdit_chequier'
        ]));

        // 3. Cas du Client PHYSIQUE (avec gestion PHOTO)
        if ($client->type_client === 'physique' && $request->has('physique')) {
            if ($client->physique) {
                $physiqueData = $request->input('physique');

                // --- GESTION DE LA NOUVELLE PHOTO ---
                // Note: Dans une API, la photo est souvent envoyée à part du JSON 'physique'
                if ($request->hasFile('photo')) {
                    
                    // a. Supprimer l'ancienne photo si elle existe
                    if ($client->physique->photo && Storage::disk('public')->exists($client->physique->photo)) {
                        Storage::disk('public')->delete($client->physique->photo);
                    }

                    // b. Stocker la nouvelle photo
                    $path = $request->file('photo')->store('clients/photos', 'public');
                    $physiqueData['photo'] = $path;
                }

                $client->physique->update(
                    collect($physiqueData)
                        ->except(['id', 'client_id'])
                        ->toArray()
                );
            }
        }

        // 4. Cas du Client MORAL
        if ($client->type_client === 'morale' && $request->has('morale')) {
            if ($client->morale) {
                $client->morale->update(
                    collect($request->input('morale'))
                        ->except(['id', 'client_id'])
                        ->toArray()
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Client mis à jour avec succès',
            'data'    => $client->fresh()->load(['physique', 'morale', 'agency'])
        ]);
    });
}
   

    /**
     * SUPPRESSION CLIENT
     */
   public function destroy($id)
{
    $user = Auth::user();

    if (!$user || !$user->hasAnyRole(['DG', 'Admin'])) {
        return response()->json([
            'message' => 'Seul le DG ou l’Administrateur peut supprimer un client'
        ], 403);
    }

    $client = Client::with('physique')->findOrFail($id);

    // --- SUPPRESSION DU FICHIER PHOTO ---
    if ($client->type_client === 'physique' && $client->physique && $client->physique->photo) {
        if (Storage::disk('public')->exists($client->physique->photo)) {
            Storage::disk('public')->delete($client->physique->photo);
        }
    }

    $client->delete(); // Les lignes en base de données sont supprimées ici

    return response()->json([
        'message' => 'Client et ses fichiers supprimés avec succès'
    ]);
}

    /**
     * 🛠️ LOGIQUE COMMUNE DE CRÉATION (PRIVÉE)
     */
   /**
 * 🛠️ LOGIQUE COMMUNE DE CRÉATION (MISE À JOUR AVEC UPLOAD)
 */
private function createClientProcess($request, $type)
{
    $user = Auth::user();

       
    if (!$user || !$user->can('gestion des clients')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    try {
        return DB::transaction(function () use ($request, $type) {


            // 1. Validation : Vérifier si l'agence existe réellement
           $agencyExists = DB::table('agencies')->where('id', $request->agency_id)->exists();
          if (!$agencyExists) {
          return response()->json(['message' => 'L\'agence sélectionnée n\'existe pas.'], 422);
         }
            // 1. Génération du numéro
            $numClient = ClientNumberService::generate($request->agency_id);

            // 2. Création de la table principale 'clients'
            $clientData = $request->only([
                'agency_id', 'telephone', 'email', 'adresse_ville', 
                'adresse_quartier', 'bp', 'pays_residence', 'taxable', 'interdit_chequier'
            ]);
            
            $clientData['num_client'] = $numClient;
            $clientData['type_client'] = $type;

            $client = Client::create($clientData);

            // 3. Préparation des données détaillées
            $detailsData = $request->validated();

            // --- LOGIQUE D'UPLOAD PHOTO POUR LE TYPE PHYSIQUE ---
            if ($type === 'physique') {
                if ($request->hasFile('photo')) {
                    // Stockage de l'image dans storage/app/public/clients/photos
                    $path = $request->file('photo')->store('clients/photos', 'public');
                    // On ajoute le chemin au tableau des données à insérer
                    $detailsData['photo'] = $path;
                }
                // Juste avant $client->physique()->create(...)

                
                $client->physique()->create($detailsData);
            } else {
                $client->morale()->create($detailsData);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Client ' . $type . ' créé avec succès',
                'num_client' => $numClient,
                'data'       => $client->load($type === 'physique' ? 'physique' : 'morale')
            ], 201);
        });
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création : ' . $e->getMessage()
        ], 500);
    }
}
public function getNextNumber($agencyId)
{
    // Utilise la logique de votre service pour pré-calculer le numéro
    $nextNumber = \App\Services\ClientNumberService::generate($agencyId);
    
    return response()->json([
        'success' => true,
        'next_number' => $nextNumber
    ]);
}
}
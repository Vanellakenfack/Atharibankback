<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StorePhysiqueClientRequest;
use App\Http\Requests\Client\StoreMoraleClientRequest;
use App\Models\Client\Client;
use App\Services\ClientNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
     * MISE À JOUR DU CLIENT
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $client = Client::with(['physique', 'morale'])->findOrFail($id);

            $client->update($request->only([
                'telephone', 'email', 'adresse_ville', 'adresse_quartier',
                'bp', 'pays_residence', 'taxable', 'interdit_chequier'
            ]));

            if ($client->type_client === 'physique' && $request->has('physique')) {
                if ($client->physique) {
                    $physiqueData = $request->input('physique');

                    if ($request->hasFile('photo')) {
                        if ($client->physique->photo && Storage::disk('public')->exists($client->physique->photo)) {
                            Storage::disk('public')->delete($client->physique->photo);
                        }
                        $path = $request->file('photo')->store('clients/photos', 'public');
                        $physiqueData['photo'] = $path;
                    }

                    $client->physique->update(
                        collect($physiqueData)->except(['id', 'client_id'])->toArray()
                    );
                }
            }

            if ($client->type_client === 'morale' && $request->has('morale')) {
                if ($client->morale) {
                    $client->morale->update(
                        collect($request->input('morale'))->except(['id', 'client_id'])->toArray()
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
            return response()->json(['message' => 'Accès restreint au DG/Admin'], 403);
        }

        $client = Client::with('physique')->findOrFail($id);

        if ($client->type_client === 'physique' && $client->physique && $client->physique->photo) {
            Storage::disk('public')->delete($client->physique->photo);
        }

        $client->delete();

        return response()->json(['message' => 'Client supprimé avec succès']);
    }

    /**
     * LOGIQUE COMMUNE DE CRÉATION
     */
    private function createClientProcess($request, $type)
    {
        $user = Auth::user();
        if (!$user || !$user->can('gestion des clients')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        try {
            return DB::transaction(function () use ($request, $type) {
                
                // --- CORRECTION : APPEL NON STATIQUE ---
                $service = new ClientNumberService();
                $numClient = $service->generate($request->agency_id);

                // 2. Création table 'clients'
                $clientData = $request->only([
                    'agency_id', 'telephone', 'email', 'adresse_ville', 
                    'adresse_quartier', 'bp', 'pays_residence', 'taxable', 'interdit_chequier'
                ]);
                
                // On s'assure que le nom de la colonne correspond à votre DB (num_client)
                $clientData['num_client'] = $numClient;
                $clientData['type_client'] = $type;

                $client = Client::create($clientData);

                // 3. Données détaillées
                $detailsData = $request->validated();

                if ($type === 'physique') {
                    if ($request->hasFile('photo')) {
                        $path = $request->file('photo')->store('clients/photos', 'public');
                        $detailsData['photo'] = $path;
                    }
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
                // Si l'erreur est un doublon SQL (Code 23000)
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce numéro de client a déjà été attribué. Veuillez réessayer.'
                    ], 422);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur technique : ' . $e->getMessage()
                ], 500);
            }
    }
}
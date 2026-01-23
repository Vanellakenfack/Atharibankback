<?php

namespace App\Http\Controllers\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\Gestionnaire;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class GestionnaireController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/gestionnaires",
     *     summary="Liste des gestionnaires",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par nom, prénom ou code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="agence_id",
     *         in="query",
     *         description="Filtrer par agence",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des gestionnaires",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Gestionnaire")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        
        $query = Gestionnaire::with('agence')
            ->actifs()
            ->orderBy('gestionnaire_nom');

        // Filtre par agence
        if ($request->has('agence_id')) {
            $query->where('agence_id', $request->agence_id);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gestionnaire_nom', 'LIKE', "%{$search}%")
                  ->orWhere('gestionnaire_prenom', 'LIKE', "%{$search}%")
                  ->orWhere('gestionnaire_code', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('ville', 'LIKE', "%{$search}%")
                  ->orWhere('quartier', 'LIKE', "%{$search}%");
            });
        }

        $gestionnaires = $query->paginate($perPage);

        // Ajouter les URLs des images
        $gestionnaires->getCollection()->transform(function ($gestionnaire) {
            return $this->addImageUrls($gestionnaire);
        });

        return response()->json([
            'success' => true,
            'data' => $gestionnaires->items(),
            'pagination' => [
                'current_page' => $gestionnaires->currentPage(),
                'last_page' => $gestionnaires->lastPage(),
                'per_page' => $gestionnaires->perPage(),
                'total' => $gestionnaires->total(),
                'from' => $gestionnaires->firstItem(),
                'to' => $gestionnaires->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/gestionnaires/{id}",
     *     summary="Afficher un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du gestionnaire",
     *         @OA\JsonContent(ref="#/components/schemas/Gestionnaire")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gestionnaire non trouvé"
     *     )
     * )
     */
    public function show($id)
    {
        $gestionnaire = Gestionnaire::with(['agence', 'comptes.client'])->actifs()->find($id);

        if (!$gestionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Gestionnaire non trouvé'
            ], 404);
        }

        $gestionnaire = $this->addImageUrls($gestionnaire);

        return response()->json([
            'success' => true,
            'data' => $gestionnaire
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/gestionnaires",
     *     summary="Créer un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/GestionnaireCreate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Gestionnaire créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Gestionnaire")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Générer le code automatiquement
        $gestionnaireCode = Gestionnaire::genererCode();
        
        $validator = Validator::make($request->all(), [
            'gestionnaire_nom' => 'required|string|max:255',
            'gestionnaire_prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:gestionnaires,email',
            'agence_id' => 'required|exists:agencies,id',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'cni_recto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cni_verso' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'plan_localisation_domicile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'gestionnaire_nom',
            'gestionnaire_prenom',
            'telephone',
            'email',
            'agence_id',
            'ville',
            'quartier'
        ]);

        // Ajouter le code généré automatiquement
        $data['gestionnaire_code'] = $gestionnaireCode;
        $data['etat'] = 'present';

        // Gestion du téléchargement des images
        if ($request->hasFile('cni_recto')) {
            $data['cni_recto'] = $this->storeImage($request->file('cni_recto'), 'gestionnaires/cni/recto');
        }

        if ($request->hasFile('cni_verso')) {
            $data['cni_verso'] = $this->storeImage($request->file('cni_verso'), 'gestionnaires/cni/verso');
        }

        if ($request->hasFile('plan_localisation_domicile')) {
            $data['plan_localisation_domicile'] = $this->storeImage($request->file('plan_localisation_domicile'), 'gestionnaires/plans');
        }

        if ($request->hasFile('signature')) {
            $data['signature'] = $this->storeImage($request->file('signature'), 'gestionnaires/signatures');
        }

        $gestionnaire = Gestionnaire::create($data);

        $gestionnaire = $this->addImageUrls($gestionnaire->load('agence'));

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire créé avec succès',
            'data' => $gestionnaire
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/gestionnaires/{id}",
     *     summary="Mettre à jour un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/GestionnaireUpdate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gestionnaire mis à jour",
     *         @OA\JsonContent(ref="#/components/schemas/Gestionnaire")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gestionnaire non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $gestionnaire = Gestionnaire::actifs()->find($id);

        if (!$gestionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Gestionnaire non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'gestionnaire_nom' => 'required|string|max:255',
            'gestionnaire_prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:gestionnaires,email,' . $gestionnaire->id,
            'agence_id' => 'required|exists:agencies,id',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'cni_recto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cni_verso' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'plan_localisation_domicile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'gestionnaire_nom',
            'gestionnaire_prenom',
            'telephone',
            'email',
            'agence_id',
            'ville',
            'quartier'
        ]);

        // Gestion du téléchargement des images
        // CORRECTION IMPORTANTE : Utiliser has() au lieu de hasFile() pour vérifier si un champ est présent dans la requête
        // même si la valeur est vide (pour supprimer l'image)
        
        // Pour cni_recto
        if ($request->has('cni_recto') && $request->cni_recto !== null) {
            if ($request->cni_recto === '') {
                // Supprimer l'image existante si une chaîne vide est envoyée
                if ($gestionnaire->cni_recto) {
                    Storage::delete('public/' . $gestionnaire->cni_recto);
                    $data['cni_recto'] = null;
                }
            } elseif ($request->hasFile('cni_recto')) {
                // Nouveau fichier
                if ($gestionnaire->cni_recto) {
                    Storage::delete('public/' . $gestionnaire->cni_recto);
                }
                $data['cni_recto'] = $this->storeImage($request->file('cni_recto'), 'gestionnaires/cni/recto');
            }
        }

        // Pour cni_verso
        if ($request->has('cni_verso') && $request->cni_verso !== null) {
            if ($request->cni_verso === '') {
                if ($gestionnaire->cni_verso) {
                    Storage::delete('public/' . $gestionnaire->cni_verso);
                    $data['cni_verso'] = null;
                }
            } elseif ($request->hasFile('cni_verso')) {
                if ($gestionnaire->cni_verso) {
                    Storage::delete('public/' . $gestionnaire->cni_verso);
                }
                $data['cni_verso'] = $this->storeImage($request->file('cni_verso'), 'gestionnaires/cni/verso');
            }
        }

        // Pour plan_localisation_domicile
        if ($request->has('plan_localisation_domicile') && $request->plan_localisation_domicile !== null) {
            if ($request->plan_localisation_domicile === '') {
                if ($gestionnaire->plan_localisation_domicile) {
                    Storage::delete('public/' . $gestionnaire->plan_localisation_domicile);
                    $data['plan_localisation_domicile'] = null;
                }
            } elseif ($request->hasFile('plan_localisation_domicile')) {
                if ($gestionnaire->plan_localisation_domicile) {
                    Storage::delete('public/' . $gestionnaire->plan_localisation_domicile);
                }
                $data['plan_localisation_domicile'] = $this->storeImage($request->file('plan_localisation_domicile'), 'gestionnaires/plans');
            }
        }

        // Pour signature - CORRECTION CRITIQUE
        if ($request->has('signature')) {
            // Si la signature est une chaîne vide, supprimer l'image existante
            if ($request->signature === '' || $request->signature === null) {
                if ($gestionnaire->signature) {
                    Storage::delete('public/' . $gestionnaire->signature);
                    $data['signature'] = null;
                }
            } 
            // Si un fichier est envoyé
            elseif ($request->hasFile('signature')) {
                if ($gestionnaire->signature) {
                    Storage::delete('public/' . $gestionnaire->signature);
                }
                $data['signature'] = $this->storeImage($request->file('signature'), 'gestionnaires/signatures');
            }
        }

        // Ne pas mettre à jour les champs d'image s'ils ne sont pas présents dans la requête
        // (pour garder les valeurs existantes)
        $gestionnaire->update($data);

        $gestionnaire = $this->addImageUrls($gestionnaire->load('agence'));

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire mis à jour avec succès',
            'data' => $gestionnaire
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/gestionnaires/{id}",
     *     summary="Supprimer un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gestionnaire supprimé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gestionnaire non trouvé"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Impossible de supprimer (associé à des comptes)"
     *     )
     * )
     */
    public function destroy($id)
    {
        $gestionnaire = Gestionnaire::actifs()->find($id);

        if (!$gestionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Gestionnaire non trouvé'
            ], 404);
        }

        $gestionnaire->marquerCommeSupprime();

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire supprimé avec succès'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/gestionnaires/agence/{agenceId}",
     *     summary="Liste des gestionnaires par agence",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="agenceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des gestionnaires",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/GestionnaireSimple")
     *         )
     *     )
     * )
     */
    public function parAgence($agenceId)
    {
        $gestionnaires = Gestionnaire::where('agence_id', $agenceId)
            ->actifs()
            ->orderBy('gestionnaire_nom')
            ->get(['id', 'gestionnaire_code', 'gestionnaire_nom', 'gestionnaire_prenom', 'telephone', 'email', 'ville', 'quartier']);

        // Ajouter les URLs des images
        $gestionnaires->transform(function ($gestionnaire) {
            return $this->addImageUrls($gestionnaire);
        });

        return response()->json([
            'success' => true,
            'data' => $gestionnaires
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/gestionnaires/corbeille",
     *     summary="Liste des gestionnaires supprimés",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des gestionnaires supprimés",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Gestionnaire")
     *         )
     *     )
     * )
     */
    public function corbeille(Request $request)
    {
        $query = Gestionnaire::with('agence')
            ->onlyTrashed()
            ->orderBy('deleted_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gestionnaire_nom', 'LIKE', "%{$search}%")
                  ->orWhere('gestionnaire_prenom', 'LIKE', "%{$search}%")
                  ->orWhere('gestionnaire_code', 'LIKE', "%{$search}%")
                  ->orWhere('ville', 'LIKE', "%{$search}%")
                  ->orWhere('quartier', 'LIKE', "%{$search}%");
            });
        }

        $gestionnaires = $query->get();

        // Ajouter les URLs des images
        $gestionnaires->transform(function ($gestionnaire) {
            return $this->addImageUrls($gestionnaire);
        });

        return response()->json([
            'success' => true,
            'data' => $gestionnaires
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/gestionnaires/{id}/restaurer",
     *     summary="Restaurer un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gestionnaire restauré"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gestionnaire non trouvé"
     *     )
     * )
     */
    public function restaurer($id)
    {
        $gestionnaire = Gestionnaire::withTrashed()->find($id);

        if (!$gestionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Gestionnaire non trouvé'
            ], 404);
        }

        $gestionnaire->restaurer();

        $gestionnaire = $this->addImageUrls($gestionnaire);

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire restauré avec succès',
            'data' => $gestionnaire
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/gestionnaires/{id}/force",
     *     summary="Supprimer définitivement un gestionnaire",
     *     tags={"Gestionnaires"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gestionnaire supprimé définitivement"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gestionnaire non trouvé"
     *     )
     * )
     */
    public function supprimerDefinitivement($id)
    {
        $gestionnaire = Gestionnaire::withTrashed()->find($id);

        if (!$gestionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Gestionnaire non trouvé'
            ], 404);
        }

        // Supprimer les images du stockage
        if ($gestionnaire->cni_recto) {
            Storage::delete('public/' . $gestionnaire->cni_recto);
        }
        if ($gestionnaire->cni_verso) {
            Storage::delete('public/' . $gestionnaire->cni_verso);
        }
        if ($gestionnaire->plan_localisation_domicile) {
            Storage::delete('public/' . $gestionnaire->plan_localisation_domicile);
        }
        if ($gestionnaire->signature) {
            Storage::delete('public/' . $gestionnaire->signature);
        }

        $gestionnaire->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire supprimé définitivement'
        ]);
    }

    // Méthodes privées utilitaires

    /**
     * Stocke une image et retourne le chemin relatif
     */
    private function storeImage($file, $directory)
    {
        $path = $file->store($directory, 'public');
        return $path;
    }

    /**
     * Ajoute les URLs complètes des images au gestionnaire
     */
    private function addImageUrls($gestionnaire)
    {
        if (!$gestionnaire) {
            return $gestionnaire;
        }

        $gestionnaire->cni_recto_url = $gestionnaire->cni_recto ? asset('storage/' . $gestionnaire->cni_recto) : null;
        $gestionnaire->cni_verso_url = $gestionnaire->cni_verso ? asset('storage/' . $gestionnaire->cni_verso) : null;
        $gestionnaire->plan_localisation_domicile_url = $gestionnaire->plan_localisation_domicile ? asset('storage/' . $gestionnaire->plan_localisation_domicile) : null;
        $gestionnaire->signature_url = $gestionnaire->signature ? asset('storage/' . $gestionnaire->signature) : null;
        
        if (method_exists($gestionnaire, 'getAdresseCompleteAttribute')) {
            $gestionnaire->adresse_complete = $gestionnaire->adresse_complete;
        }

        return $gestionnaire;
    }
}
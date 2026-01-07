<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TypeCompteController extends Controller
{
    /**
     * GET /api/types-comptes
     */
    public function index(Request $request): JsonResponse
    {
        $query = TypeCompte::with([
            'chapitreDefaut',
            'chapitreFraisOuverture',
            'chapitreCommissionRetrait',
            'chapitreCommissionSms',
            'chapitreInteretCredit',
        ]);

        // Filtres
        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('est_mata')) {
            $query->where('est_mata', filter_var($request->est_mata, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('a_vue')) {
            $query->where('a_vue', filter_var($request->est_mata, FILTER_VALIDATE_BOOLEAN));
        }


        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        // Pagination
        if ($request->has('per_page')) {
            $typesComptes = $query->paginate($request->per_page);
        } else {
            $typesComptes = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $typesComptes,
        ]);
    }

    /**
     * GET /api/types-comptes/{id}
     */
    public function show($id): JsonResponse
    {
        $typeCompte = TypeCompte::with([
            'chapitreDefaut',
            'chapitreFraisOuverture',
            'chapitreFraisCarnet',
            'chapitreRenouvellement',
            'chapitrePerte',
            'chapitreCommissionRetrait',
            'chapitreCommissionSms',
            'chapitreInteretCredit',
            'chapitreFraisDeblocage',
            'chapitrePenalite',
            'chapitreClotureAnticipe',
            'compteAttenteProduits',
        ])->findOrFail($id);

        $data = $typeCompte->toArray();

        // Informations supplémentaires
        if ($typeCompte->est_mata) {
            $data['rubriques_disponibles'] = TypeCompte::getRubriquesMata();
        }

        if ($typeCompte->necessite_duree) {
            $data['durees_disponibles'] = TypeCompte::getDureesBlocage();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/types-comptes
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:10|unique:types_comptes,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',

            // Caractéristiques
            'est_mata' => 'boolean',
            'necessite_duree' => 'boolean',
            'a_vue' => 'boolean',
            'actif' => 'boolean',

            // Chapitres principaux
            'chapitre_defaut_id' => 'nullable|exists:plan_comptable,id',

            // Frais ouverture
            'frais_ouverture' => 'nullable|numeric|min:0',
            'frais_ouverture_actif' => 'boolean',
            'chapitre_frais_ouverture_id' => 'nullable|exists:plan_comptable,id',

            // Frais carnet
            'frais_carnet' => 'nullable|numeric|min:0',
            'frais_carnet_actif' => 'boolean',
            'chapitre_frais_carnet_id' => 'nullable|exists:plan_comptable,id',
            'frais_renouvellement_carnet'=> 'nullable|numeric|min:0',

            // Commission mensuelle
            'commission_mensuelle_actif' => 'boolean',
            'seuil_commission' => 'nullable|numeric|min:0',
            'commission_si_superieur' => 'nullable|numeric|min:0',
            'commission_si_inferieur' => 'nullable|numeric|min:0',
            'chapitre_commission_mensuelle_id' => 'nullable|exists:plan_comptable,id',

            // Commission retrait
            'commission_retrait' => 'nullable|numeric|min:0',
            'commission_retrait_actif' => 'boolean',
            'chapitre_commission_retrait_id' => 'nullable|exists:plan_comptable,id',

            // Commission SMS
            'commission_sms' => 'nullable|numeric|min:0',
            'commission_sms_actif' => 'boolean',
            'chapitre_commission_sms_id' => 'nullable|exists:plan_comptable,id',

            // Intérêts
            'taux_interet_annuel' => 'nullable|numeric|min:0|max:100',
            'interets_actifs' => 'boolean',
            'frequence_calcul_interet' => 'nullable|in:JOURNALIER,MENSUEL,ANNUEL',
            'heure_calcul_interet' => 'nullable|date_format:H:i',
            'chapitre_interet_credit_id' => 'nullable|exists:plan_comptable,id',

            // Pénalités
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',
            'penalite_actif' => 'boolean',
            'chapitre_penalite_id' => 'nullable|exists:plan_comptable,id',

            // Minimum compte
            'minimum_compte' => 'nullable|numeric|min:0',
            'minimum_compte_actif' => 'boolean',

            // Compte attente
            'compte_attente_produits_id' => 'nullable|exists:plan_comptable,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $typeCompte = TypeCompte::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Type de compte créé avec succès',
                'data' => $typeCompte->load([
                    'chapitreDefaut',
                    'chapitreFraisOuverture',
                ]),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/types-comptes/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $typeCompte = TypeCompte::findOrFail($id);

        $validator = Validator::make($request->all(), [
'code' => 'sometimes|string|max:10|unique:types_comptes,code,' . $id . ',id',            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',

            'est_mata' => 'sometimes|boolean',
            'necessite_duree' => 'sometimes|boolean',
            'a_vue' => 'sometimes|boolean',
            'actif' => 'sometimes|boolean',

            'frais_ouverture' => 'nullable|numeric|min:0',
            'commission_mensuelle_actif' => 'sometimes|boolean',
            'seuil_commission' => 'nullable|numeric|min:0',
            'taux_interet_annuel' => 'nullable|numeric|min:0|max:100',
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',

            // Tous les autres champs...
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $typeCompte->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Type de compte mis à jour avec succès',
                'data' => $typeCompte->fresh()->load([
                    'chapitreDefaut',
                    'chapitreFraisOuverture',
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/types-comptes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $typeCompte = TypeCompte::findOrFail($id);

            // Vérifier si des comptes utilisent ce type
            $comptesUtilisant = $typeCompte->comptes()->count();

            if ($comptesUtilisant > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer: {$comptesUtilisant} compte(s) utilisent ce type",
                ], 400);
            }

            $typeCompte->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type de compte supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/types-comptes/{id}/simuler-frais
     */
    public function simulerFrais(Request $request, int $id): JsonResponse
    {
        $typeCompte = TypeCompte::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type_operation' => 'required|in:ouverture,commission_mensuelle,retrait,deblocage',
            'montant' => 'nullable|numeric|min:0',
            'total_versements' => 'nullable|numeric|min:0',
            'est_anticipe' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $simulation = [
            'type_operation' => $request->type_operation,
            'frais_applicables' => [],
        ];

        switch ($request->type_operation) {
            case 'ouverture':
                if ($typeCompte->frais_ouverture_actif) {
                    $simulation['frais_applicables'][] = [
                        'type' => 'frais_ouverture',
                        'montant' => $typeCompte->frais_ouverture,
                        'chapitre' => $typeCompte->chapitreFraisOuverture->libelle ?? null,
                    ];
                }
                break;

            case 'commission_mensuelle':
                if ($typeCompte->commission_mensuelle_actif && $request->has('total_versements')) {
                    $montant = $typeCompte->calculerCommissionMensuelle($request->total_versements);
                    $simulation['frais_applicables'][] = [
                        'type' => 'commission_mensuelle',
                        'montant' => $montant,
                        'total_versements' => $request->total_versements,
                        'seuil' => $typeCompte->seuil_commission,
                    ];
                }
                break;

            case 'retrait':
                if ($typeCompte->commission_retrait_actif) {
                    $simulation['frais_applicables'][] = [
                        'type' => 'commission_retrait',
                        'montant' => $typeCompte->commission_retrait,
                    ];
                }

                if ($request->est_anticipe && $typeCompte->penalite_actif && $request->has('montant')) {
                    $penalite = $typeCompte->calculerPenaliteRetrait($request->montant);
                    $simulation['frais_applicables'][] = [
                        'type' => 'penalite_retrait',
                        'montant' => $penalite,
                        'taux' => $typeCompte->penalite_retrait_anticipe . '%',
                    ];
                }
                break;
        }

        $totalFrais = array_sum(array_column($simulation['frais_applicables'], 'montant'));
        $simulation['total_frais'] = $totalFrais;

        return response()->json([
            'success' => true,
            'data' => $simulation,
        ]);
    }

    /**
     * GET /api/chapitres-comptables/disponibles
     */
    public function getChapitresDisponibles(Request $request): JsonResponse
    {
        $query = PlanComptable::where('est_actif', true);

        if ($request->has('nature')) {
            $nature = strtoupper($request->nature);
            if ($nature === 'DEBIT_OR_MIXTE') {
                $query->whereIn('nature_solde', ['DEBIT', 'MIXTE']);
            } elseif ($nature === 'CREDIT_OR_MIXTE') {
                $query->whereIn('nature_solde', ['CREDIT', 'MIXTE']);
            } elseif (in_array($nature, ['DEBIT', 'CREDIT', 'MIXTE'])) {
                $query->where('nature_solde', $nature);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        $chapitres = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $chapitres,
        ]);
    }
}

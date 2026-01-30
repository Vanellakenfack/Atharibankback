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
            'frais_renouvellement_livret' => 'nullable|numeric|min:0',
            'frais_renouvellement_actif' => 'boolean',
            'chapitre_renouvellement_id' => 'nullable|exists:plan_comptable,id',

            // Frais chéquier
            'frais_chequier' => 'nullable|numeric|min:0',
            'frais_chequier_actif' => 'boolean',
            'chapitre_frais_chequier_id' => 'nullable|exists:plan_comptable,id',
            'chapitre_chequier_id' => 'nullable|exists:plan_comptable,id',

            // Frais chèque guichet
            'frais_cheque_guichet' => 'nullable|numeric|min:0',
            'frais_cheque_guichet_actif' => 'boolean',
            'chapitre_cheque_guichet_id' => 'nullable|exists:plan_comptable,id',

            // Frais livret
            'frais_livret' => 'nullable|numeric|min:0',
            'frais_livret_actif' => 'boolean',
            'chapitre_livret_id' => 'nullable|exists:plan_comptable,id',

            // Frais perte carnet
            'frais_perte_carnet' => 'nullable|numeric|min:0',
            'frais_perte_actif' => 'boolean',
            'chapitre_perte_id' => 'nullable|exists:plan_comptable,id',

            // Commission mensuelle
            'commission_mensuelle_actif' => 'boolean',
            'commission_mensuel' => 'nullable|numeric|min:0',
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
            'capitalisation_interets' => 'boolean',

            // Frais de déblocage
            'frais_deblocage' => 'nullable|numeric|min:0',
            'frais_deblocage_actif' => 'boolean',
            'chapitre_frais_deblocage_id' => 'nullable|exists:plan_comptable,id',

            // Pénalités
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',
            'penalite_actif' => 'boolean',
            'chapitre_penalite_id' => 'nullable|exists:plan_comptable,id',

            // Frais cloture anticipée
            'frais_cloture_anticipe' => 'nullable|numeric|min:0',
            'frais_cloture_actif' => 'boolean',
            'chapitre_cloture_anticipe_id' => 'nullable|exists:plan_comptable,id',

            // Minimum compte
            'minimum_compte' => 'nullable|numeric|min:0',
            'minimum_compte_actif' => 'boolean',
            'chapitre_minimum_id' => 'nullable|exists:plan_comptable,id',

            // Compte attente
            'compte_attente_produits_id' => 'nullable|exists:plan_comptable,id',

            // Retraits anticipés
            'retrait_anticipe_autorise' => 'boolean',
            'validation_retrait_anticipe' => 'boolean',
            'duree_blocage_min' => 'nullable|integer|min:0',
            'duree_blocage_max' => 'nullable|integer|min:0',

            // Observations
            'observations' => 'nullable|string',
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

            $typeCompte->refresh();
            $typeCompte->load([
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
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de compte créé avec succès',
                'data' => $typeCompte->toArray(),
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
            'code' => 'sometimes|string|max:10|unique:types_comptes,code,' . $id . ',id',
            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',

            'est_mata' => 'sometimes|boolean',
            'necessite_duree' => 'sometimes|boolean',
            'a_vue' => 'sometimes|boolean',
            'actif' => 'sometimes|boolean',

            'chapitre_defaut_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_ouverture' => 'nullable|numeric|min:0',
            'frais_ouverture_actif' => 'sometimes|boolean',
            'chapitre_frais_ouverture_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_carnet' => 'nullable|numeric|min:0',
            'frais_carnet_actif' => 'sometimes|boolean',
            'chapitre_frais_carnet_id' => 'nullable|exists:plan_comptable,id',
            'frais_renouvellement_carnet' => 'nullable|numeric|min:0',
            'frais_renouvellement_livret' => 'nullable|numeric|min:0',
            'frais_renouvellement_actif' => 'sometimes|boolean',
            'chapitre_renouvellement_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_chequier' => 'nullable|numeric|min:0',
            'frais_chequier_actif' => 'sometimes|boolean',
            'chapitre_frais_chequier_id' => 'nullable|exists:plan_comptable,id',
            'chapitre_chequier_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_cheque_guichet' => 'nullable|numeric|min:0',
            'frais_cheque_guichet_actif' => 'sometimes|boolean',
            'chapitre_cheque_guichet_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_livret' => 'nullable|numeric|min:0',
            'frais_livret_actif' => 'sometimes|boolean',
            'chapitre_livret_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_perte_carnet' => 'nullable|numeric|min:0',
            'frais_perte_actif' => 'sometimes|boolean',
            'chapitre_perte_id' => 'nullable|exists:plan_comptable,id',
            
            'commission_mensuelle_actif' => 'sometimes|boolean',
            'commission_mensuel' => 'nullable|numeric|min:0',
            'seuil_commission' => 'nullable|numeric|min:0',
            'commission_si_superieur' => 'nullable|numeric|min:0',
            'commission_si_inferieur' => 'nullable|numeric|min:0',
            'chapitre_commission_mensuelle_id' => 'nullable|exists:plan_comptable,id',
            
            'commission_retrait' => 'nullable|numeric|min:0',
            'commission_retrait_actif' => 'sometimes|boolean',
            'chapitre_commission_retrait_id' => 'nullable|exists:plan_comptable,id',
            
            'commission_sms' => 'nullable|numeric|min:0',
            'commission_sms_actif' => 'sometimes|boolean',
            'chapitre_commission_sms_id' => 'nullable|exists:plan_comptable,id',
            
            'taux_interet_annuel' => 'nullable|numeric|min:0|max:100',
            'interets_actifs' => 'sometimes|boolean',
            'frequence_calcul_interet' => 'nullable|in:JOURNALIER,MENSUEL,ANNUEL',
            'heure_calcul_interet' => 'nullable|date_format:H:i',
            'chapitre_interet_credit_id' => 'nullable|exists:plan_comptable,id',
            'capitalisation_interets' => 'sometimes|boolean',
            
            'frais_deblocage' => 'nullable|numeric|min:0',
            'frais_deblocage_actif' => 'sometimes|boolean',
            'chapitre_frais_deblocage_id' => 'nullable|exists:plan_comptable,id',
            
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',
            'penalite_actif' => 'sometimes|boolean',
            'chapitre_penalite_id' => 'nullable|exists:plan_comptable,id',
            
            'frais_cloture_anticipe' => 'nullable|numeric|min:0',
            'frais_cloture_actif' => 'sometimes|boolean',
            'chapitre_cloture_anticipe_id' => 'nullable|exists:plan_comptable,id',
            
            'minimum_compte' => 'nullable|numeric|min:0',
            'minimum_compte_actif' => 'sometimes|boolean',
            'chapitre_minimum_id' => 'nullable|exists:plan_comptable,id',
            
            'compte_attente_produits_id' => 'nullable|exists:plan_comptable,id',
            
            'retrait_anticipe_autorise' => 'sometimes|boolean',
            'validation_retrait_anticipe' => 'sometimes|boolean',
            'duree_blocage_min' => 'nullable|integer|min:0',
            'duree_blocage_max' => 'nullable|integer|min:0',
            
            'observations' => 'nullable|string',
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

            // Recharger tous les données depuis la DB
            $typeCompte->refresh();
            
            // Charger les relations
            $typeCompte->load([
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
            ]);

            $data = $typeCompte->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Type de compte mis à jour avec succès',
                'data' => $data,
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

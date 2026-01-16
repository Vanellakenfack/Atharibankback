<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OperationDiverse;
use App\Models\Agency;
use App\Models\chapitre\PlanComptable;
use App\Models\compte\Compte;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OperationDiversController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:saisir od', ['only' => ['store', 'update']]);
        $this->middleware('permission:valider les od', ['only' => ['valider', 'rejeter']]);
        $this->middleware('permission:consulter logs', ['only' => ['historique']]);
        $this->middleware('permission:comptabiliser od', ['only' => ['comptabiliser']]);
        $this->middleware('permission:annuler od', ['only' => ['annuler']]);
    }

    /**
     * Liste des OD (API)
     */
    public function index(Request $request)
    {
        try {
            $query = OperationDiverse::with(['agence', 'saisiPar', 'validePar', 'compteDebit', 'compteCredit'])
                ->latest('date_operation');
            
            // Filtres
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }
            
            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }
            
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }
            
            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_od', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('nom_tiers', 'like', "%{$search}%")
                      ->orWhere('reference_client', 'like', "%{$search}%");
                });
            }
            
            $operationDiverses = $query->paginate($request->per_page ?? 20);
            $agences = Agency::all();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'operationDiverses' => $operationDiverses,
                    'agences' => $agences
                ],
                'message' => 'Liste des opérations diverses récupérée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder une nouvelle OD (API)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agence_id' => 'required|exists:agencies,id',
            'date_operation' => 'required|date',
            'date_valeur' => 'nullable|date|after_or_equal:date_operation',
            'type_operation' => 'required|in:DEPOT,RETRAIT,VIREMENT,FRAIS,COMMISSION,REGULARISATION,AUTRE',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'required|numeric|min:0.01',
            'devise' => 'required|in:FCFA,EURO,DOLLAR,POUND',
            'compte_debit_id' => 'required|exists:plan_comptable,id',
            'compte_credit_id' => 'required|exists:plan_comptable,id',
            'compte_client_debiteur_id' => 'nullable|exists:comptes,id',
            'compte_client_crediteur_id' => 'nullable|exists:comptes,id',
            'justificatif_type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE',
            'justificatif_numero' => 'nullable|string|max:100',
            'justificatif_date' => 'nullable|date',
            'justificatif' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'reference_client' => 'nullable|string|max:100',
            'nom_tiers' => 'nullable|string|max:255',
            'est_urgence' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $od = OperationDiverse::create([
                'agence_id' => $request->agence_id,
                'date_operation' => $request->date_operation,
                'date_valeur' => $request->date_valeur ?? $request->date_operation,
                'type_operation' => $request->type_operation,
                'libelle' => $request->libelle,
                'description' => $request->description,
                'montant' => $request->montant,
                'devise' => $request->devise,
                'compte_debit_id' => $request->compte_debit_id,
                'compte_credit_id' => $request->compte_credit_id,
                'compte_client_debiteur_id' => $request->compte_client_debiteur_id,
                'compte_client_crediteur_id' => $request->compte_client_crediteur_id,
                'saisi_par' => Auth::id(),
                'justificatif_type' => $request->justificatif_type,
                'justificatif_numero' => $request->justificatif_numero,
                'justificatif_date' => $request->justificatif_date,
                'reference_client' => $request->reference_client,
                'nom_tiers' => $request->nom_tiers,
                'est_urgence' => $request->boolean('est_urgence'),
                'statut' => 'BROUILLON'
            ]);

            // Sauvegarder le justificatif
            if ($request->hasFile('justificatif')) {
                $path = $request->file('justificatif')->store(
                    "justificatifs/od/{$od->id}",
                    'public'
                );
                $od->update(['justificatif_path' => $path]);
            }

            // Enregistrer l'historique de création
            if (method_exists($od, 'enregistrerHistorique')) {
                $od->enregistrerHistorique('CREATION', null, 'BROUILLON');
            }

            DB::commit();
            
            // Réponse JSON pour l'API
            return response()->json([
                'success' => true,
                'message' => 'Opération diverse créée avec succès',
                'data' => $od->load(['agence', 'compteDebit', 'compteCredit', 'saisiPar'])
            ], 201);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'opération diverse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une OD (API)
     */
    public function show($id)
    {
        try {
            $operationDiverse = OperationDiverse::with([
                'agence',
                'saisiPar',
                'validePar',
                'comptabilisePar',
                'compteDebit',
                'compteCredit',
                'compteClientDebiteur',
                'compteClientCrediteur',
                'historique.user',
                'signatures.validateur',
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $operationDiverse,
                'message' => 'OD récupérée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OD non trouvée.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une OD (API)
     */
    public function update(Request $request, $id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'date_operation' => 'sometimes|date',
                'date_valeur' => 'nullable|date|after_or_equal:date_operation',
                'libelle' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'montant' => 'sometimes|numeric|min:0.01',
                'compte_debit_id' => 'sometimes|exists:plan_comptable,id',
                'compte_credit_id' => 'sometimes|exists:plan_comptable,id',
                'justificatif_type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE',
                'justificatif_numero' => 'nullable|string|max:100',
                'justificatif_date' => 'nullable|date',
                'reference_client' => 'nullable|string|max:100',
                'nom_tiers' => 'nullable|string|max:255',
                'est_urgence' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            
            $operationDiverse->update($validator->validated());
            
            if (method_exists($operationDiverse, 'enregistrerHistorique')) {
                $operationDiverse->enregistrerHistorique('MODIFICATION');
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD mise à jour avec succès',
                'data' => $operationDiverse->fresh()->load(['agence', 'compteDebit', 'compteCredit'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une OD (API)
     */
    public function destroy($id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            // Vérifier si l'OD peut être supprimée
            if ($operationDiverse->statut === 'VALIDE' || $operationDiverse->est_comptabilise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une OD validée ou comptabilisée.'
                ], 400);
            }

            DB::beginTransaction();
            
            // Supprimer les fichiers justificatifs
            if ($operationDiverse->justificatif_path && Storage::disk('public')->exists($operationDiverse->justificatif_path)) {
                Storage::disk('public')->delete($operationDiverse->justificatif_path);
            }

            // Supprimer l'historique et les signatures
            $operationDiverse->historique()->delete();
            $operationDiverse->signatures()->delete();
            
            // Supprimer l'OD
            $operationDiverse->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'OD supprimée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider une OD (API)
     */
    public function valider(Request $request, $id)
    {
        try {
            $request->validate([
                'commentaire' => 'nullable|string|max:500',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->valider(Auth::user(), $request->commentaire)) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD validée avec succès.',
                    'data' => $operationDiverse->fresh()->load(['validePar', 'signatures.validateur'])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de valider cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter une OD (API)
     */
    public function rejeter(Request $request, $id)
    {
        try {
            $request->validate([
                'motif' => 'required|string|max:1000',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->rejeter(Auth::user(), $request->motif)) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD rejetée avec succès.',
                    'data' => $operationDiverse->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de rejeter cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comptabiliser une OD (API)
     */
    public function comptabiliser(Request $request, $id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            if ($operationDiverse->comptabiliser(Auth::user())) {
                return response()->json([
                    'success' => true,
                    'message' => 'OD comptabilisée avec succès.',
                    'data' => $operationDiverse->fresh()->load(['comptabilisePar'])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de comptabiliser cette OD.'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la comptabilisation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uploader un justificatif (API)
     */
    public function uploadJustificatif(Request $request, $id)
    {
        try {
            $request->validate([
                'justificatif' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'type' => 'nullable|in:FACTURE,QUITTANCE,BON,TICKET,AUTRE',
            ]);

            $operationDiverse = OperationDiverse::findOrFail($id);
            
            $path = $request->file('justificatif')->store(
                "justificatifs/od/{$operationDiverse->id}",
                'public'
            );
            
            $operationDiverse->update([
                'justificatif_path' => $path,
                'justificatif_type' => $request->type ?? $operationDiverse->justificatif_type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Justificatif uploadé avec succès.',
                'data' => [
                    'path' => $path,
                    'od' => $operationDiverse->fresh()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du justificatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le justificatif (API)
     */
    public function telechargerJustificatif($id)
    {
        try {
            $operationDiverse = OperationDiverse::findOrFail($id);

            if (!$operationDiverse->justificatif_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun justificatif disponible.'
                ], 404);
            }

            $path = storage_path('app/public/' . $operationDiverse->justificatif_path);
            
            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier justificatif introuvable.'
                ], 404);
            }

            return response()->download($path);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du justificatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une OD (API)
     */
    public function annuler(Request $request, $id)
    {
        try {
            $request->validate(['motif' => 'required|string|max:255']);
            
            $operationDiverse = OperationDiverse::findOrFail($id);
            
            $operationDiverse->update([
                'statut' => 'ANNULE',
                'motif_rejet' => $request->motif
            ]);

            if (method_exists($operationDiverse, 'enregistrerHistorique')) {
                $operationDiverse->enregistrerHistorique('ANNULATION', $operationDiverse->getOriginal('statut'), 'ANNULE');
            }

            return response()->json([
                'success' => true,
                'message' => 'OD annulée avec succès',
                'data' => $operationDiverse->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'OD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD en attente de validation (API)
     */
    public function enAttenteValidation(Request $request)
    {
        try {
            $query = OperationDiverse::whereIn('statut', ['BROUILLON', 'SAISI'])
                ->with(['agence', 'saisiPar', 'compteDebit', 'compteCredit']);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD en attente de validation récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des OD à comptabiliser (API)
     */
    public function aComptabiliser(Request $request)
    {
        try {
            $query = OperationDiverse::where('statut', 'VALIDE')
                ->where('est_comptabilise', false)
                ->with(['agence', 'validePar', 'compteDebit', 'compteCredit']);
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->latest('date_validation')->get();

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'count' => $operationDiverses->count(),
                'message' => 'OD à comptabiliser récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des OD à comptabiliser',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche avancée des OD (API)
     */
    public function rechercheAvancee(Request $request)
    {
        try {
            $query = OperationDiverse::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_od', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('nom_tiers', 'like', "%{$search}%")
                      ->orWhere('reference_client', 'like', "%{$search}%");
                });
            }

            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }

            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }

            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            if ($request->filled('est_urgence')) {
                $query->where('est_urgence', $request->boolean('est_urgence'));
            }

            $operationDiverses = $query->with(['agence', 'saisiPar', 'validePar'])
                ->latest()
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'message' => 'Résultats de recherche récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Journal des OD (API)
     */
    public function journal(Request $request)
    {
        try {
            $query = OperationDiverse::with(['agence', 'saisiPar', 'compteDebit', 'compteCredit'])
                ->where('est_comptabilise', true);
            
            if ($request->filled('date')) {
                $query->where('date_operation', $request->date);
            } elseif ($request->filled(['date_debut', 'date_fin'])) {
                $query->whereBetween('date_operation', [$request->date_debut, $request->date_fin]);
            } else {
                $query->where('date_operation', today());
            }
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }
            
            $operationDiverses = $query->orderBy('date_operation')->get();
            
            // Calcul des totaux
            $total = $operationDiverses->sum('montant');
            $totalParType = $operationDiverses->groupBy('type_operation')->map->sum('montant');

            return response()->json([
                'success' => true,
                'data' => [
                    'operationDiverses' => $operationDiverses,
                    'total' => $total,
                    'total_par_type' => $totalParType,
                    'count' => $operationDiverses->count()
                ],
                'message' => 'Journal des OD généré avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des OD (API)
     */
    public function statistiques(Request $request)
    {
        try {
            $query = OperationDiverse::query();
            
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }
            
            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }
            
            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            // Statistiques par statut
            $parStatut = $query->clone()
                ->selectRaw('statut, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('statut')
                ->get()
                ->keyBy('statut');

            // Statistiques par type
            $parType = $query->clone()
                ->selectRaw('type_operation, COUNT(*) as count, SUM(montant) as total')
                ->groupBy('type_operation')
                ->get()
                ->keyBy('type_operation');

            // Total général
            $totalGeneral = $query->clone()->sum('montant');
            $countGeneral = $query->clone()->count();

            // OD en urgence
            $urgences = $query->clone()->where('est_urgence', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_general' => $totalGeneral,
                    'nombre_total' => $countGeneral,
                    'par_statut' => $parStatut,
                    'par_type' => $parType,
                    'urgences' => $urgences
                ],
                'message' => 'Statistiques récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les données des OD (API)
     */
    public function export(Request $request)
    {
        try {
            $query = OperationDiverse::query();

            // Appliquer les filtres
            if ($request->filled('date_debut')) {
                $query->where('date_operation', '>=', $request->date_debut);
            }

            if ($request->filled('date_fin')) {
                $query->where('date_operation', '<=', $request->date_fin);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('type_operation')) {
                $query->where('type_operation', $request->type_operation);
            }

            if ($request->filled('agence_id')) {
                $query->where('agence_id', $request->agence_id);
            }

            $operationDiverses = $query->with(['agence', 'saisiPar', 'validePar', 'compteDebit', 'compteCredit'])
                ->get()
                ->map(function ($od) {
                    return [
                        'Numéro OD' => $od->numero_od,
                        'Date opération' => Carbon::parse($od->date_operation)->format('d/m/Y'),
                        'Libellé' => $od->libelle,
                        'Type' => $od->type_operation,
                        'Montant' => $od->montant ? number_format((float)$od->montant, 2, ',', ' ') . ' ' . $od->devise : '0,00 ' . $od->devise,
                        'Compte débit' => $od->compteDebit ? $od->compteDebit->code . ' - ' . $od->compteDebit->libelle : '',
                        'Compte crédit' => $od->compteCredit ? $od->compteCredit->code . ' - ' . $od->compteCredit->libelle : '',
                        'Statut' => $od->statut,
                        'Saisi par' => $od->saisiPar ? $od->saisiPar->name : '',
                        'Validé par' => $od->validePar ? $od->validePar->name : '',
                        'Date validation' => $od->date_validation ? $od->date_validation->format('d/m/Y H:i') : '',
                        'Agence' => $od->agence ? $od->agence->nom : '',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $operationDiverses,
                'total' => $operationDiverses->count(),
                'message' => 'Données exportées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique d'une OD (API)
     */
    public function historique($id)
    {
        try {
            $operationDiverse = OperationDiverse::with(['historique.user'])->findOrFail($id);
            
            $historique = $operationDiverse->historique->map(function ($item) {
                return [
                    'action' => $item->action,
                    'description' => $item->description,
                    'donnees_modifiees' => $item->donnees_modifiees_formatees ?? 'Aucune donnée modifiée',
                    'user' => $item->user->name ?? 'Système',
                    'created_at' => $item->created_at->format('d/m/Y H:i'),
                    'ip_address' => $item->ip_address
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'od' => [
                        'id' => $operationDiverse->id,
                        'numero_od' => $operationDiverse->numero_od,
                        'libelle' => $operationDiverse->libelle
                    ],
                    'historique' => $historique
                ],
                'message' => 'Historique récupéré avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les agences (API)
     */
    public function getAgences()
    {
        try {
            $agences = Agency::all();
            
            return response()->json([
                'success' => true,
                'data' => $agences,
                'message' => 'Agences récupérées avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des agences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les comptes plan comptable (API)
     */
    public function getComptesPlan()
    {
        try {
            $comptes = PlanComptable::where('est_actif', true)->orderBy('code')->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes plan récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les comptes clients (API)
     */
    public function getComptesClients()
    {
        try {
            $comptes = Compte::where('statut', 'actif')->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes,
                'message' => 'Comptes clients récupérés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compte\StoreAccountRequest;
use App\Http\Requests\Compte\UpdateAccountRequest;
use App\Http\Requests\Compte\ValidateAccountRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\Mandataire;
use App\Models\DocumentsCompte;
use App\Services\Compte\CompteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompteController extends Controller
{
    public function __construct(
        private CompteService $accountService
    ) {}

    /**
     * Liste des comptes avec filtres
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Compte::class);

        $filters = $request->only([
            'numero_compte',
            'client_id',
            'agency_id',
            'account_type_id',
            'statut',
            'statut_validation',
            'category',
            'date_debut',
            'date_fin',
        ]);

        $accounts = $this->accountService->search($filters, $request->input('per_page', 15));

        return CompteResource::collection($accounts);
    }

    /**
     * Création d'un nouveau compte
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $account = DB::transaction(function () use ($validated, $request) {
                // Création du compte
                $account = $this->accountService->create($validated, $request->user());

                // Ajout des mandataires
                if (!empty($validated['mandataire_1'])) {
                    $this->createMandatary($account, $validated['mandataire_1'], 'mandataire_1', $request);
                }

                if (!empty($validated['mandataire_2'])) {
                    $this->createMandatary($account, $validated['mandataire_2'], 'mandataire_2', $request);
                }

                // Upload des documents
                if (!empty($validated['documents'])) {
                    foreach ($validated['documents'] as $doc) {
                        $this->uploadDocument($account, $doc, $request->user()->id);
                    }
                }

                return $account;
            });

            return response()->json([
                'message' => 'Compte créé avec succès. En attente de validation.',
                'data' => new CompteResource($account->load([
                    'client',
                    'accountType',
                    'agency',
                    'creator',
                    'mandataries',
                    'documents',
                ])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du compte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affichage d'un compte
     */
    public function show(Compte $account): CompteResource
    {
        $this->authorize('view', $account);

        return new CompteResource($account->load([
            'client.clientPhysique',
            'client.clientMorale',
            'accountType',
            'agency',
            'creator',
            'validatorCa',
            'validatorAj',
            'mandataries',
            'documents',
            'transactions' => fn($q) => $q->limit(20),
            'commissions' => fn($q) => $q->latest()->limit(10),
        ]));
    }

    /**
     * Mise à jour d'un compte
     */
    public function update(UpdateAccountRequest $request, Compte $account): JsonResponse
    {
        $this->authorize('update', $account);

        try {
            $account = $this->accountService->update($account, $request->validated(), $request->user());

            return response()->json([
                'message' => 'Compte mis à jour avec succès.',
                'data' => new CompteResource($account),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validation d'un compte (CA ou AJ)
     */
    public function validate(ValidateAccountRequest $request, Compte $account): JsonResponse
    {
        $action = $request->input('action');
        $user = $request->user();

        try {
            $account = match ($action) {
                'validate_ca' => $this->accountService->validateByChefAgence($account, $user),
                'validate_aj' => $this->accountService->validateByAssistantJuridique($account, $user),
                'reject' => $this->accountService->reject($account, $user, $request->input('motif')),
            };

            $messages = [
                'validate_ca' => 'Compte validé par le Chef d\'Agence. En attente de validation juridique.',
                'validate_aj' => 'Compte validé et activé avec succès.',
                'reject' => 'Compte rejeté.',
            ];

            return response()->json([
                'message' => $messages[$action],
                'data' => new CompteResource($account->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la validation.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mise en opposition
     */
    public function opposition(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'type' => ['required', 'in:debit,credit,total'],
            'motif' => ['required', 'string', 'max:500'],
        ]);

        try {
            $account = $this->accountService->mettreEnOpposition(
                $account,
                $request->input('type'),
                $request->input('motif'),
                $request->user()
            );

            return response()->json([
                'message' => 'Opposition mise en place avec succès.',
                'data' => new CompteResource($account),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise en opposition.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clôture d'un compte
     */
    public function cloturer(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        try {
            $account = $this->accountService->cloturer($account, $request->user(), $request->input('motif'));

            return response()->json([
                'message' => 'Compte clôturé avec succès.',
                'data' => new CompteResource($account),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Impossible de clôturer le compte.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Liste des comptes en attente de validation
     */
    public function enAttenteValidation(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Compte::with(['client', 'accountType', 'agency', 'creator']);

        if ($user->hasRole('Chef d\'Agence (CA)')) {
            $query->where('statut_validation', 'en_attente');
        } elseif ($user->hasRole('Assistant Juridique (AJ)')) {
            $query->where('statut_validation', 'valide_ca');
        }

        // Filtrer par agence si l'utilisateur n'est pas DG
        if (!$user->hasRole(['DG', 'Admin']) && $user->agency_id) {
            $query->where('agency_id', $user->agency_id);
        }

        return CompteResource::collection(
            $query->orderBy('created_at', 'desc')->paginate(15)
        );
    }

    /**
     * Historique des transactions d'un compte
     */
    public function transactions(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('view', $account);

        $transactions = $account->transactions()
            ->with(['creator', 'validator'])
            ->when($request->filled('date_debut'), fn($q) => $q->where('created_at', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn($q) => $q->where('created_at', '<=', $request->date_fin))
            ->when($request->filled('type'), fn($q) => $q->where('type_transaction', $request->type))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $transactions,
            'solde_actuel' => $account->solde,
        ]);
    }

    /**
     * Création d'un mandataire
     */
    private function createMandatary(Compte $account, array $data, string $type, Request $request): Mandataire
    {
        $mandataryData = array_merge($data, [
            'account_id' => $account->id,
            'type_mandataire' => $type,
        ]);

        // Upload signature si présente
        if ($request->hasFile("$type.signature")) {
            $path = $request->file("$type.signature")->store("mandataries/{$account->id}/signatures", 'public');
            $mandataryData['signature_path'] = $path;
        }

        // Upload photo si présente
        if ($request->hasFile("$type.photo")) {
            $path = $request->file("$type.photo")->store("mandataries/{$account->id}/photos", 'public');
            $mandataryData['photo_path'] = $path;
        }

        return Mandataire::create($mandataryData);
    }

    /**
     * Upload d'un document
     */
    private function uploadDocument(Compte $account, array $data, int $userId): DocumentsCompte
    {
        $file = $data['fichier'];
        $path = $file->store("accounts/{$account->id}/documents", 'public');

        return DocumentsCompte::create([
            'account_id' => $account->id,
            'uploaded_by' => $userId,
            'type_document' => $data['type'],
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $path,
            'mime_type' => $file->getMimeType(),
            'taille' => $file->getSize(),
        ]);
    }
}
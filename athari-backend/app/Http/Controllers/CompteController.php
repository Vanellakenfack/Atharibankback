<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compte\CreateAccountRequest;
use App\Http\Requests\Compte\UpdateAccountRequest;
use App\Http\Requests\Compte\ValidateAccountRequest;
use App\Http\Resources\CompteResource;
use App\Http\Resources\CompteCollection;
use App\Models\Compte;
use App\Services\CompteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    public function __construct(
        protected CompteService $accountService
    ) {}

    public function index(Request $request): CompteCollection
    {
        $this->authorize('viewAny', Compte::class);

        $query = Compte::with(['client', 'accountType', 'agency', 'collector'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('account_type_id'), fn($q) => $q->where('account_type_id', $request->account_type_id))
            ->when($request->filled('agency_id'), fn($q) => $q->where('agency_id', $request->agency_id))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('account_number', 'like', "%{$search}%")
                        ->orWhere('full_account_number', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q) use ($search) {
                            $q->where('last_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $accounts = $query->paginate($request->get('per_page', 15));

        return new CompteCollection($accounts);
    }

    public function store(CreateAccountRequest $request): JsonResponse
    {
        $account = $this->accountService->createAccount(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Compte créé avec succès. En attente de validation.',
            'data' => new CompteResource($account),
        ], 201);
    }

    public function show(Compte $account): CompteResource
    {
        $this->authorize('view', $account);

        $account->load([
            'client',
            'accountType.accountingChapter',
            'agency',
            'collector',
            'mandataires',
            'documents',
            'createdBy',
            'validatedByCA',
            'validatedByAJ',
        ]);

        return new CompteResource($account);
    }

    public function update(UpdateAccountRequest $request, Compte $account): JsonResponse
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return response()->json([
            'message' => 'Compte mis à jour avec succès.',
            'data' => new CompteResource($account->fresh()),
        ]);
    }

    public function destroy(Compte $account): JsonResponse
    {
        $this->authorize('delete', $account);

        if ($account->balance != 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un compte avec un solde non nul.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès.',
        ]);
    }

    public function validate(ValidateAccountRequest $request, Compte $account): JsonResponse
    {
        $validationType = $request->validation_type;
        $approved = $request->approved;
        $comments = $request->comments;
        $userId = $request->user()->id;

        try {
            if ($validationType === 'ca') {
                $this->authorize('validateAsCA', $account);
                $account = $this->accountService->validateByCA($account, $userId, $approved, $comments);
                $message = $approved
                    ? 'Compte validé par le Chef d\'Agence. Clé du compte générée.'
                    : 'Validation refusée par le Chef d\'Agence.';
            } else {
                $this->authorize('validateAsAJ', $account);
                $account = $this->accountService->validateByAJ($account, $userId, $approved, $comments);
                $message = $approved
                    ? 'Compte activé avec succès. Le blocage sur débit a été levé.'
                    : 'Validation refusée par l\'Assistant Juridique.';
            }

            return response()->json([
                'message' => $message,
                'data' => new CompteResource($account),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function close(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('close', $account);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $account = $this->accountService->closeAccount(
                $account,
                $request->user()->id,
                $request->reason
            );

            return response()->json([
                'message' => 'Compte clôturé avec succès.',
                'data' => new CompteResource($account),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function block(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('block', $account);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'end_date' => ['nullable', 'date', 'after:today'],
        ]);

        $account = $this->accountService->blockAccount(
            $account,
            $request->reason,
            $request->end_date
        );

        return response()->json([
            'message' => 'Compte bloqué avec succès.',
            'data' => new CompteResource($account),
        ]);
    }

    public function unblock(Compte $account): JsonResponse
    {
        $this->authorize('unblock', $account);

        $account = $this->accountService->unblockAccount($account);

        return response()->json([
            'message' => 'Compte débloqué avec succès.',
            'data' => new CompteResource($account),
        ]);
    }

    public function pendingValidation(Request $request): CompteCollection
    {
        $this->authorize('viewPending', Compte::class);

        $user = $request->user();
        
        $query = Compte::with(['client', 'accountType', 'agency'])
            ->pending();

        // Filtrer selon le rôle
        if ($user->hasRole('Chef d\'Agence (CA)')) {
            $query->where('status', Compte::STATUS_PENDING)
                ->whereNull('validated_by_ca');
        } elseif ($user->hasRole('Assistant Juridique (AJ)')) {
            $query->where('status', Compte::STATUS_PENDING_VALIDATION)
                ->whereNotNull('validated_by_ca')
                ->whereNull('validated_by_aj');
        }

        $accounts = $query->latest()->paginate($request->get('per_page', 15));

        return new CompteCollection($accounts);
    }

    public function statement(Request $request, Compte $account): JsonResponse
    {
        $this->authorize('view', $account);

        // Implémentation de l'extrait de compte
        // À compléter avec les transactions

        return response()->json([
            'account' => new CompteResource($account),
            'transactions' => [],
            'opening_balance' => 0,
            'closing_balance' => $account->balance,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountTypeResource;
use App\Models\TypesCompte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TypesCompteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TypesCompte::with('accountingChapter')
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('category')
            ->orderBy('name');

        $accountTypes = $request->boolean('paginate', false)
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'data' => AccountTypeResource::collection($accountTypes),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TypesCompte::class);

        $validated = $request->validate([
            'accounting_chapter_id' => ['required', 'exists:accounting_chapters,id'],
            'code' => ['required', 'string', 'max:20', 'unique:account_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(TypesCompte::CATEGORIES))],
            'sub_category' => ['nullable', Rule::in(array_keys(TypesCompte::SUB_CATEGORIES))],
            'opening_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_commission' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_fee' => ['nullable', 'numeric', 'min:0'],
            'sms_fee' => ['nullable', 'numeric', 'min:0'],
            'minimum_balance' => ['nullable', 'numeric', 'min:0'],
            'unblocking_fee' => ['nullable', 'numeric', 'min:0'],
            'early_withdrawal_penalty_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'blocking_duration_days' => ['nullable', 'integer', 'min:1'],
            'is_remunerated' => ['nullable', 'boolean'],
            'requires_checkbook' => ['nullable', 'boolean'],
            'mata_boost_sections' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $accountType = TypesCompte::create($validated);

        return response()->json([
            'message' => 'Type de compte créé avec succès.',
            'data' => new AccountTypeResource($accountType),
        ], 201);
    }

    public function show(TypesCompte $accountType): AccountTypeResource
    {
        $accountType->load('accountingChapter');
        return new AccountTypeResource($accountType);
    }

    public function update(Request $request, TypesCompte $accountType): JsonResponse
    {
        $this->authorize('update', $accountType);

        $validated = $request->validate([
            'accounting_chapter_id' => ['sometimes', 'exists:accounting_chapters,id'],
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('account_types')->ignore($accountType)],
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', Rule::in(array_keys(TypesCompte::CATEGORIES))],
            'sub_category' => ['nullable', Rule::in(array_keys(TypesCompte::SUB_CATEGORIES))],
            'opening_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_commission' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_fee' => ['nullable', 'numeric', 'min:0'],
            'sms_fee' => ['nullable', 'numeric', 'min:0'],
            'minimum_balance' => ['nullable', 'numeric', 'min:0'],
            'unblocking_fee' => ['nullable', 'numeric', 'min:0'],
            'early_withdrawal_penalty_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'blocking_duration_days' => ['nullable', 'integer', 'min:1'],
            'is_remunerated' => ['nullable', 'boolean'],
            'requires_checkbook' => ['nullable', 'boolean'],
            'mata_boost_sections' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $accountType->update($validated);

        return response()->json([
            'message' => 'Type de compte mis à jour avec succès.',
            'data' => new AccountTypeResource($accountType->fresh()),
        ]);
    }

    public function destroy(TypesCompte $accountType): JsonResponse
    {
        $this->authorize('delete', $accountType);

        if ($accountType->accounts()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un type de compte utilisé.',
            ], 422);
        }

        $accountType->delete();

        return response()->json([
            'message' => 'Type de compte supprimé avec succès.',
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => TypesCompte::CATEGORIES,
            'sub_categories' => TypesCompte::SUB_CATEGORIES,
            'mata_boost_sections' => TypesCompte::MATA_BOOST_SECTIONS,
        ]);
    }
}
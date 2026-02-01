<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Services\CreditWorkflowService;
use Illuminate\Http\Request;

class CreditApprovalController extends Controller
{
    protected CreditWorkflowService $workflow;

    public function __construct(CreditWorkflowService $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * Ajouter un avis
     */
    public function store(Request $request, $id)
    {
        $data = $request->validate([
            'avis' => 'required|in:favorable,defavorable',
            'role' => 'required|string',
            'niveau' => 'required|in:agence,siege',
            'commentaire' => 'nullable|string',
        ]);

        $credit = CreditApplication::findOrFail($id);

        $approval = $this->workflow->addApproval(
            $credit,
            $data['avis'],
            $data['role'],
            $data['niveau'],
            $data['commentaire'] ?? null
        );

        return response()->json($approval, 201);
    }
}

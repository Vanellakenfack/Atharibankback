<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Services\CreditWorkflowService;
use App\Services\PVGeneratorService;

class CreditCommitteeController extends Controller
{
    protected CreditWorkflowService $workflow;
    protected PVGeneratorService $pvService;

    public function __construct(
        CreditWorkflowService $workflow,
        PVGeneratorService $pvService
    ) {
        $this->workflow = $workflow;
        $this->pvService = $pvService;
    }

    /**
     * Comité de crédit Agence
     */
    public function agence($id)
    {
        $credit = CreditApplication::with('approvals')->findOrFail($id);

        if (! $this->workflow->isAgenceApproved($credit)) {
            return response()->json([
                'message' => 'Comité agence défavorable'
            ], 403);
        }

        // Génération PV agence
        $pv = $this->pvService->generate($credit, 'agence');

        // Passage au siège
        $this->workflow->updateStatus($credit, 'ENVOYE_SIEGE');

        return response()->json([
            'message' => 'Comité agence validé',
            'pv' => $pv
        ]);
    }

    /**
     * Comité de crédit Siège
     */
    public function siege($id)
    {
        $credit = CreditApplication::with('approvals')->findOrFail($id);

        if (! $this->workflow->isSiegeApproved($credit)) {
            return response()->json([
                'message' => 'Décision DG défavorable'
            ], 403);
        }

        // Génération PV siège
        $pv = $this->pvService->generate($credit, 'siege');

        // Code mise en place
        $code = $this->workflow->generateMiseEnPlaceCode($credit);

        $credit->update([
            'statut' => 'VALIDE',
            'code_mise_en_place' => $code,
            'note_credit' => 'Client solvable – avis favorable du comité siège'
        ]);

        return response()->json([
            'message' => 'Crédit validé par le siège',
            'code_mise_en_place' => $code,
            'pv' => $pv
        ]);
    }
}

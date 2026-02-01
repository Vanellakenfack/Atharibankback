<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Services\CreditScheduleService;

class CreditScheduleController extends Controller
{
    protected CreditScheduleService $scheduleService;

    public function __construct(CreditScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Récupérer l'échéancier complet
     */
    public function show($creditId)
    {
        $credit = CreditApplication::findOrFail($creditId);
        $schedule = $this->scheduleService->generate($credit);

        return response()->json($schedule);
    }

    /**
     * Ajouter pénalité pour un mois donné
     */
    public function penalize($creditId, $mois)
    {
        $credit = CreditApplication::findOrFail($creditId);
        $schedule = $this->scheduleService->generate($credit);

        $this->scheduleService->applyPenalty($schedule, $mois, $credit->product->penalite_retard ?? 10);

        return response()->json($schedule);
    }
}

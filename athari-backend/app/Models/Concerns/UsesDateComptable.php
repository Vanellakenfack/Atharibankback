<?php
namespace App\Models\Concerns;

use App\Services\ComptabiliteService;

trait UsesDateComptable
{
    public static function bootUsesDateComptable()
    {
        static::creating(function ($model) {
            // priorité : session fournie par la requête (middleware)
            $request = request();
            $session = $request->get('active_session');

            if (! $session) {
                $agenceId = $model->agence_id ?? ($request->agence_id ?? auth()->user()->agence_id ?? null);
                if ($agenceId) {
                    $session = ComptabiliteService::getActiveSessionOrFail($agenceId);
                }
            }

            if ($session) {
                $model->jours_comptable_id = $session->jourComptable->id;
                $model->date_comptable = $session->jourComptable->date_du_jour;
            } else {
                throw new \Exception('Impossible de déterminer la journée comptable ouverte.');
            }
        });
    }
}
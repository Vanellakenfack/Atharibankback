<?php
namespace App\Services;

use App\Models\SessionAgence\AgenceSession;
use Exception;

class ComptabiliteService
{
    public static function getSessionActive(int $agenceId)
    {
        $session = AgenceSession::where('agence_id', $agenceId)
            ->where('statut', 'OU')
            ->with('jourComptable')
            ->first();

        if (!$session || !$session->jourComptable || $session->jourComptable->statut !== 'OUVERT') {
            throw new Exception("L'agence est fermée. Aucune opération autorisée.");
        }

        return $session;
    }

    /**
     * Alias pour getSessionActive() - méthode conventionnelle
     */
    public static function getActiveSessionOrFail(int $agenceId)
    {
        return self::getSessionActive($agenceId);
    }

    public static function getActiveDateForAgence(int $agenceId)
    {
        return self::getActiveSessionOrFail($agenceId)->jourComptable->date_du_jour;
    }
}
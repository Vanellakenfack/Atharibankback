<?php

namespace App\Services\Comptabilite\Balance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\compte\Compte;
use App\Models\chapitre\PlanComptable;

class BalanceService
{
    /**
     * BALANCE GÉNÉRALE
     */
    public function getBalanceGenerale($dateDebut, $dateFin, $agenceId = null)
    {
        // 1. On récupère les mouvements groupés par chapitre du plan comptable
        $resultats = PlanComptable::orderBy('code', 'asc')
            ->get()
            ->map(function ($chapitre) use ($dateDebut, $dateFin, $agenceId) {
                // Calcul des reports et périodes
                $chapitre->report_debit = $this->sumMouvement($chapitre->id, 'montant_debit', '<', $dateDebut, $agenceId);
                $chapitre->report_credit = $this->sumMouvement($chapitre->id, 'montant_credit', '<', $dateDebut, $agenceId);
                $chapitre->periode_debit = $this->sumMouvement($chapitre->id, 'montant_debit', 'between', [$dateDebut, $dateFin], $agenceId);
                $chapitre->periode_credit = $this->sumMouvement($chapitre->id, 'montant_credit', 'between', [$dateDebut, $dateFin], $agenceId);

                $totalD = (float)$chapitre->report_debit + (float)$chapitre->periode_debit;
                $totalC = (float)$chapitre->report_credit + (float)$chapitre->periode_credit;
                $solde = $totalD - $totalC;

                $chapitre->solde_debit = $solde > 0 ? $solde : 0;
                $chapitre->solde_credit = $solde < 0 ? abs($solde) : 0;
                $chapitre->classe = substr((string)$chapitre->code, 0, 1);

                return $chapitre;
            })
            // Filtrer les comptes sans aucun mouvement
            ->filter(fn($item) => (
                $item->report_debit != 0 || $item->report_credit != 0 || 
                $item->periode_debit != 0 || $item->periode_credit != 0
            ));

        // 2. On formate pour obtenir les statistiques et le groupement par classe
        return $this->formaterGenerale($resultats);
    }

    /**
     * MÉTHODE DE FORMATEUR (Celle qui manquait dans votre erreur 500)
     */
    protected function formaterGenerale($resultats)
    {
        // Groupement par code chapitre pour la vue
        $donneesGroupées = $resultats->groupBy('code')->map(function ($items, $code) {
            $libelle = $items->first()->libelle ?? $code;
            return [
                'code' => $code,
                'libelle' => $libelle,
                'comptes' => $items,
                'sous_total_debit' => $items->sum('solde_debit'),
                'sous_total_credit' => $items->sum('solde_credit'),
            ];
        })->sortKeys();

        // Calcul des totaux généraux récapitulatifs
        $stats = [
            'total_general_debit_report'  => $resultats->sum('report_debit'),
            'total_general_credit_report' => $resultats->sum('report_credit'),
            'total_general_debit_periode' => $resultats->sum('periode_debit'),
            'total_general_credit_periode' => $resultats->sum('periode_credit'),
            'total_general_debit'         => $resultats->sum('solde_debit'),
            'total_general_credit'        => $resultats->sum('solde_credit'),
        ];

        // Vérification de l'équilibre comptable
        $stats['est_equilibree'] = abs($stats['total_general_debit'] - $stats['total_general_credit']) < 0.001;

        return [
            'donnees'      => $donneesGroupées,
            'statistiques' => $stats,
            'agence_nom'   => null // À remplir si vous récupérez le nom de l'agence
        ];
    }

    /**
     * Fonction de somme unifiée pour la Balance Générale
     */
    private function sumMouvement($planId, $column, $operator, $date, $agenceId)
    {
        $q = DB::table('mouvements_comptables')
            ->where('statut', 'COMPTABILISE');

        if ($agenceId) {
            $q->where('agence_id', $agenceId);
        }

        if ($operator === 'between') {
            $q->whereBetween('date_mouvement', $date);
        } else {
            $q->where('date_mouvement', $operator, $date);
        }

        if ($column === 'montant_debit') {
            $q->where('compte_debit_id', $planId);
        } else {
            $q->where('compte_credit_id', $planId);
        }

        return (float) $q->sum($column);
    }

    /**
     * BALANCE AUXILIAIRE
     */
    /**
     * BALANCE AUXILIAIRE COMPLÈTE
     * Conforme au format PDF : Compte | Intitulé | Reports | Mouvements | Soldes
     */
   public function getBalanceAuxiliaire($dateDebut, $dateFin, $agenceId = null)
    {
        Log::info('DEBUT DIAGNOSTIC BALANCE AUXILIAIRE', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'agenceId' => $agenceId
        ]);

        // 1. Récupération des comptes
          $comptes = Compte::with(['client', 'planComptable','client.physique', 
         'client.morale'])->get();
         
        
        Log::info('NB COMPTES TROUVES EN BASE : ' . $comptes->count());

        $resultats = $comptes->map(function ($compte) use ($dateDebut, $dateFin, $agenceId) {


        $intitule = 'SANS NOM';
            if ($compte->client) {
                if ($compte->client->type_client === 'physique' && $compte->client->clientPhysique) {
                    $intitule = $compte->client->clientPhysique->nom_prenoms;
                } elseif ($compte->client->type_client === 'morale' && $compte->client->clientMorale) {
                    $intitule = $compte->client->clientMorale->raison_sociale;
                } else {
                    $intitule = $compte->client->nom_complet ?? 'CLIENT INCONNU';
                }
            }
            $compte->intitule_client = $intitule; // On attache le nom à l'objet
            
            // Calcul des Reports et Mouvements
            $compte->report_debit = $this->getSubQueryAuxiliaire('montant_debit', '<', $dateDebut, $compte->id, $agenceId);
            $compte->report_credit = $this->getSubQueryAuxiliaire('montant_credit', '<', $dateDebut, $compte->id, $agenceId);
            $compte->periode_debit = $this->getSubQueryAuxiliaire('montant_debit', 'between', [$dateDebut, $dateFin], $compte->id, $agenceId);
            $compte->periode_credit = $this->getSubQueryAuxiliaire('montant_credit', 'between', [$dateDebut, $dateFin], $compte->id, $agenceId);

            $totalD = (float)$compte->report_debit + (float)$compte->periode_debit;
            $totalC = (float)$compte->report_credit + (float)$compte->periode_credit;
            $solde = $totalD - $totalC;

            $compte->solde_debit = $solde > 0 ? $solde : 0;
            $compte->solde_credit = $solde < 0 ? abs($solde) : 0;
            $compte->code_chapitre = $compte->planComptable ? $compte->planComptable->code : 'N/A';
            $compte->libelle_chapitre = $compte->planComptable ? $compte->planComptable->libelle : 'SANS CHAPITRE';

            return $compte;
        });

        // 2. Log avant filtrage pour voir si des données existent
        $sommeTotaleMouvements = $resultats->sum(fn($c) => $c->periode_debit + $c->periode_credit + $c->report_debit + $c->report_credit);
        Log::info('SOMME TOTALE DES VALEURS CALCULEES (AVANT FILTRE) : ' . $sommeTotaleMouvements);

        // 3. Filtrage
        $filtered = $resultats->filter(function($c) {
            $hasData = ($c->report_debit != 0 || $c->report_credit != 0 || $c->periode_debit != 0 || $c->periode_credit != 0);
            if ($hasData) {
                Log::info("COMPTE AVEC MOUVEMENT TROUVE", [
                    'id' => $c->id, 
                    'numero' => $c->numero_compte, 
                    'debit' => $c->periode_debit, 
                    'credit' => $c->periode_credit
                ]);
            }
            return $hasData;
        });

        Log::info('NB COMPTES APRES FILTRAGE : ' . $filtered->count());

        // 4. Groupement
        $final = $filtered->groupBy('code_chapitre')->map(function ($items, $code) {
            return [
                'code_chapitre'    => $code,
                'libelle_chapitre' => $items->first()->libelle_chapitre,
                'comptes'          => $items->sortBy('numero_compte'),
                'total_report_debit'  => $items->sum('report_debit'),
                'total_report_credit' => $items->sum('report_credit'),
                'total_debit'         => $items->sum('periode_debit'),
                'total_credit'        => $items->sum('periode_credit'),
                'total_solde_debit'   => $items->sum('solde_debit'),
                'total_solde_credit'  => $items->sum('solde_credit'),
            ];
        })->sortKeys();

        Log::info('NB CHAPITRES FINAUX : ' . $final->count());

        return $final;
    }

    private function getSubQueryAuxiliaire($col, $operator, $date, $compteId, $agenceId = null)
    {
        $q = DB::table('mouvements_comptables')
            ->where('compte_id', $compteId)
            ->where('statut', 'COMPTABILISE'); // ATTENTION : Vérifiez l'orthographe ici (COMPTABILISE ou COMPTAMILISE ?)

        if ($agenceId) {
            $q->where('agence_id', $agenceId);
        }

        if ($operator === 'between') {
            $q->whereBetween('date_mouvement', $date);
        } else {
            $q->where('date_mouvement', $operator, $date);
        }

        $result = (float) $q->sum($col);

        // Log uniquement si on trouve quelque chose pour ne pas inonder les logs
        if ($result > 0) {
            Log::info('VALEUR TROUVÉE', [
                'compte_id' => $compteId,
                'col' => $col,
                'montant' => $result
            ]);
        }

        return $result;
    }
}
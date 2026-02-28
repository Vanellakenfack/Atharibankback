<?php

namespace App\Services;

use App\Models\SessionAgence\JourComptable;
use App\Models\SessionAgence\AgenceSession;
use App\Models\SessionAgence\GuichetSession;
use App\Models\SessionAgence\CaisseSession;
use App\Models\Caisse\Caisse;
use App\Models\Caisse\Guichet;
use Illuminate\Support\Facades\DB;
use App\Models\SessionAgence\BilanJournalierAgence;
use Illuminate\Support\Facades\Log;
use Exception;

class SessionBancaireService
{
    /**
     * ÉTAPE 1 : OUVERTURE DE LA JOURNÉE COMPTABLE
     */
    
   public function ouvrirJourneeComptable($agenceId, $dateSaisie, $utilisateurId, $forceRattrapage = false)
{
    // 1. On transforme la chaîne en objet Carbon immédiatement
    $dateAUtiliser = \Carbon\Carbon::parse($dateSaisie)->startOfDay();

    // 2. On passe $dateAUtiliser dans le 'use'
    return DB::transaction(function () use ($agenceId, $dateAUtiliser, $utilisateurId, $forceRattrapage) {
        
        // Vérification si une journée est déjà ouverte
        $journeeActive = JourComptable::where('agence_id', $agenceId)
            ->where('statut', 'OUVERT')
            ->first();

        if ($journeeActive) {
            throw new Exception("Une journée comptable est déjà en cours pour cette agence.");
        }

        // Récupération de la dernière journée pour la chronologie
        $derniereJournee = JourComptable::where('agence_id', $agenceId)
            ->orderBy('date_du_jour', 'desc')
            ->first();

        // Logique de rattrapage
        if ($derniereJournee && $dateAUtiliser->lte(\Carbon\Carbon::parse($derniereJournee->date_du_jour))) {
            
            if (!$forceRattrapage) {
                throw new Exception("La nouvelle date doit être supérieure à la dernière date clôturée (" . $derniereJournee->date_du_jour . ").");
            }

            // Log d'audit
            \Illuminate\Support\Facades\Log::warning("RATTRAPAGE : Utilisateur ID {$utilisateurId} a forcé le {$dateAUtiliser->toDateString()} sur l'agence {$agenceId}.");
        }

        // Création avec la variable validée
        return JourComptable::create([
            'agence_id' => $agenceId,
            'date_du_jour' => $dateAUtiliser->toDateString(), 
            'date_precedente' => $derniereJournee?->date_du_jour,
            'statut' => 'OUVERT',
            'ouvert_at' => now(),
            'execute_par' => $utilisateurId
        ]);
    });
}    /**
     * ÉTAPE 2 : OUVERTURE DE L'AGENCE
     */
    public function ouvrirAgenceSession($agenceId, $jourComptableId, $utilisateurId)
    {
        $jour = JourComptable::findOrFail($jourComptableId);

        if ($jour->statut !== 'OUVERT') {
            throw new Exception("Impossible d'ouvrir l'agence : la journée comptable est fermée.");
        }

        return AgenceSession::create([
            'agence_id' => $agenceId,
            'jours_comptable_id'  => $jour->id, // <--- AJOUTE OU VÉRIFIE CECI
            'date_comptable' => $jour->date_du_jour,
            'statut' => 'OU',
            'heure_ouverture' => now(),
            'ouvert_par' => $utilisateurId
        ]);
    }

    /**
     * ÉTAPE 3 : OUVERTURE DU GUICHET
     */
    public function ouvrirGuichetSession($agenceSessionId, $guichetId)
    {
        $agenceSession = AgenceSession::findOrFail($agenceSessionId);
        $guichetPhysique = Guichet::findOrFail($guichetId);

        if ($agenceSession->statut !== 'OU') {
            throw new Exception("L'agence doit être ouverte avant d'ouvrir un guichet.");
        }

        $dejaOuvert = GuichetSession::where('agence_session_id', $agenceSessionId)
            ->where('guichet_id', $guichetId)
            ->where('statut', 'OU')
            ->first();

        if ($dejaOuvert) {
            throw new Exception("Ce guichet est déjà ouvert.");
        }

        return GuichetSession::create([
            'agence_session_id' => $agenceSessionId,
            'guichet_id' => $guichetId, 
            'statut'            => 'OU', 
            'heure_ouverture' => now()
        ]);
    }

    /**
     * ÉTAPE 4 : OUVERTURE DE LA CAISSE (Avec ajustage strict)
     */
    public function ouvrirCaisseSession($guichetSessionId, $caissierId, $caisseId, $soldeOuvertureSaisi, array $detailsBilletage)
    {
        return DB::transaction(function () use ($guichetSessionId, $caissierId, $caisseId, $soldeOuvertureSaisi, $detailsBilletage) {
            
            $guichet = GuichetSession::findOrFail($guichetSessionId);
            if ($guichet->statut !== 'OU') {
                throw new Exception("Le guichet doit être ouvert avant l'ouverture de la caisse.");
            }

            $caissePhysique = Caisse::lockForUpdate()->findOrFail($caisseId);
        
           $soldeTheoriqueInitial = (float)$caissePhysique->solde_actuel; // Ce que le système croit avoir

            // Calcul et vérification du billetage
            $montantBillete = $this->calculerMontantBilletage($detailsBilletage);

            if ((float)$soldeOuvertureSaisi !== (float)$montantBillete) {
                throw new Exception("Le montant billeté ($montantBillete) ne correspond pas au solde saisi ($soldeOuvertureSaisi).");
            }

            if ((float)$soldeOuvertureSaisi !== $soldeTheoriqueInitial) {
                throw new Exception("Erreur d'ajustage : Le solde déclaré diffère du solde informatique en coffre ($soldeTheoriqueInitial).");
            }

            return CaisseSession::create([
                'guichet_session_id' => $guichetSessionId,
                'caissier_id'        => $caissierId,
                'caisse_id'          => $caisseId,
                'solde_ouverture' => $soldeTheoriqueInitial, // La photo au départ
                'solde_physique'     => $soldeOuvertureSaisi,    //              
                  'billetage_ouverture'=> $detailsBilletage,
                  'solde_informatique' => 0,                       // Le flux net commence à 0
                'statut'             => 'OU',
                'heure_ouverture'    => now()
            ]);
        });
    }

    /**
     * FERMETURE DE LA CAISSE
     */
    public function fermerCaisseSession($caisseSessionId, $soldeFermetureSaisi, array $billetageFermeture) {
        return DB::transaction(function () use ($caisseSessionId, $soldeFermetureSaisi, $billetageFermeture) {
            $session = CaisseSession::findOrFail($caisseSessionId);
            
            if ($session->statut === 'FE') throw new Exception("Caisse déjà fermée.");

            // Vérification du billetage de fermeture
            $montantBillete = $this->calculerMontantBilletage($billetageFermeture);
            if ((float)$soldeFermetureSaisi !== (float)$montantBillete) {
                throw new Exception("Le billetage de fermeture ne correspond pas au solde saisi.");
            }

            $session->update([
                'solde_fermeture' => $soldeFermetureSaisi,
                'billetage_fermeture' => $billetageFermeture,
                'statut' => 'FE',
                'heure_fermeture' => now()
            ]);

            // Mise à jour du solde permanent de la caisse physique
            $session->caisse->update(['solde_actuel' => $soldeFermetureSaisi]);

            return $session;
        });
    }

    /**
     * FERMETURE DU GUICHET
     */
    public function fermerGuichetSession($guichetSessionId) {
        $guichet = GuichetSession::findOrFail($guichetSessionId);
        
        $caissesOuvertes = CaisseSession::where('guichet_session_id', $guichetSessionId)
            ->where('statut', 'OU')->exists();

        if ($caissesOuvertes) throw new Exception("Fermez toutes les caisses avant de fermer le guichet.");

        $guichet->update(['statut' => 'FE', 'heure_fermeture' => now()]);
        return $guichet;
    }

    /**
     * FERMETURE DE L'AGENCE ET DE LA JOURNÉE
     */

  public function traiterBilanFinJournee($agenceSessionId, $jourComptableId)
{
    return DB::transaction(function () use ($agenceSessionId, $jourComptableId) {
        
        // --- 1. VÉRIFICATIONS PRÉALABLES ---
        $caissesOuvertes = CaisseSession::whereHas('guichetSession', function($q) use ($agenceSessionId) {
            $q->where('agence_session_id', $agenceSessionId);
        })->where('statut', '!=', 'FE')->exists();

        if ($caissesOuvertes) {
            throw new \Exception("Erreur : Fermez toutes les caisses avant de générer le bilan global.");
        }

        // --- 2. AGRÉGATION DES FLUX DE CAISSE (ESPECES) ---
        $sessionsCaisses = CaisseSession::whereHas('guichetSession', function($q) use ($agenceSessionId) {
            $q->where('agence_session_id', $agenceSessionId);
        })->get();

        $bilanEspeces = ['entrees' => 0, 'sorties' => 0, 'theorique' => 0, 'reel' => 0, 'details' => []];

        foreach ($sessionsCaisses as $s) {
            $stats = $this->genererBilanCaisse($s->id); 
            $bilanEspeces['entrees']   += $stats['total_entrees'];
            $bilanEspeces['sorties']   += $stats['total_sorties'];
            $bilanEspeces['theorique'] += $stats['solde_theorique'];
            $bilanEspeces['reel']      += $stats['solde_reel'];
            $bilanEspeces['details'][] = [
                'caisse_id' => $s->caisse_id,
                'libelle'   => $s->caisse->libelle ?? 'Caisse '.$s->caisse_id,
                'entrees'   => $stats['total_entrees'],
                'sorties'   => $stats['total_sorties'],
                'solde'     => $stats['solde_reel'],
                'ecart'     => $stats['ecart']
            ];
        }

        // --- 3. SYNTHÈSE DES OPÉRATIONS (Inclut OD, Collectes, Frais, etc.) ---
        // On utilise COALESCE pour regrouper les écritures sans journal sous "DIVERS"
        $syntheseComptable = DB::table('mouvements_comptables')
            ->select(
                DB::raw('COALESCE(journal, "DIVERS") as type_operation'), 
                DB::raw('SUM(montant_debit) as total_debit'), 
                DB::raw('SUM(montant_credit) as total_credit'),
                DB::raw('COUNT(*) as nbr_transactions')
            )
            ->where('jours_comptable_id', $jourComptableId)
            ->where('statut', 'COMPTABILISE')
            ->groupBy('journal')
            ->get();

        // --- 4. CALCUL DES TOTAUX ---
        $totalDebitGlobal  = $syntheseComptable->sum('total_debit');
        $totalCreditGlobal = $syntheseComptable->sum('total_credit');

        // --- 5. ENREGISTREMENT OU MISE À JOUR ---
        return DB::table('bilan_journalier_agences')->updateOrInsert(
            ['jours_comptable_id' => $jourComptableId],
            [
                'date_comptable'         => now()->toDateString(),
                'total_especes_entree'   => $bilanEspeces['entrees'],
                'total_especes_sortie'   => $bilanEspeces['sorties'],
                'solde_theorique_global' => $bilanEspeces['theorique'],
                'solde_reel_global'      => $bilanEspeces['reel'],
                'ecart_global'           => $bilanEspeces['reel'] - $bilanEspeces['theorique'],
                
                // Ici, tu auras un JSON structuré par tes journaux d'OD (MATA_BOOST, CHARGES, etc.)
                'details_operations'     => json_encode($syntheseComptable), 
                'total_debit_journalier' => $totalDebitGlobal,
                'total_credit_journalier'=> $totalCreditGlobal,
                
                'resume_caisses'         => json_encode($bilanEspeces['details']),
                // Équilibre strict (Débit == Crédit)
                'statut_cloture'         => (abs($totalDebitGlobal - $totalCreditGlobal) < 0.01) ? 'EQUILIBRE' : 'DESEQUILIBRE',
                'created_at'             => now(),
                'updated_at'             => now()
            ]
        );
    });
}
    public function fermerAgenceEtJournee($agenceSessionId, $jourComptableId) {
        return DB::transaction(function () use ($agenceSessionId, $jourComptableId) {

            $guichetsOuverts = GuichetSession::where('agence_session_id', $agenceSessionId)
                ->whereIn('statut', ['OU'])->exists();
            
            if ($guichetsOuverts) throw new Exception("Des guichets sont encore ouverts.");
                        $bilanExiste = BilanJournalierAgence::where('jours_comptable_id',$jourComptableId)->exists();
                
                if (!$bilanExiste) {
                    throw new Exception("Impossible de clôturer : Le traitement des bilans (TFJ) n'a pas été effectué.");
                }
            AgenceSession::where('id', $agenceSessionId)->update([
                'statut' => 'FE', 
                'heure_fermeture' => now()
            ]);

            return JourComptable::where('id', $jourComptableId)->update([
                'statut' => 'FERME',
                'ferme_at' => now()
            ]);
        });
    }

    /**
     * BILAN DE CLÔTURE (Calcul des écarts)
     */
 public function genererBilanCaisse($caisseSessionId)
{
    // On charge la session avec sa caisse liée
    $caisse = CaisseSession::findOrFail($caisseSessionId);

    // Calcul des flux : On filtre par l'ID de la session pour être précis
    $totalFlux = DB::table('caisse_transactions')
        ->where('session_id', $caisseSessionId) // Filtre de précision chirurgicale
        ->selectRaw("SUM(CASE WHEN type_flux IN ('VERSEMENT', 'ENTREE_CAISSE') THEN montant_brut ELSE 0 END) as total_entrees")
        ->selectRaw("SUM(CASE WHEN type_flux IN ('RETRAIT', 'SORTIE_CAISSE') THEN montant_brut ELSE 0 END) as total_sorties")
        ->first();

    // L'équation de contrôle bancaire
    $soldeTheorique = (float)$caisse->solde_ouverture + (float)$totalFlux->total_entrees - (float)$totalFlux->total_sorties;
    
    // Si la caisse est encore ouverte, on compare avec le solde_actuel informatique
    // Si elle est fermée, on compare avec le solde_fermeture saisi
    $soldeReel = ($caisse->statut === 'FE') ? (float)$caisse->solde_fermeture : (float)$caisse->caisse->solde_actuel;
    
    $ecart = $soldeReel - $soldeTheorique;

    return [
        'ouverture'       => (float)$caisse->solde_ouverture,
        'total_entrees'   => (float)$totalFlux->total_entrees,
        'total_sorties'   => (float)$totalFlux->total_sorties,
        'solde_theorique' => $soldeTheorique,
        'solde_reel'      => $soldeReel,
        'ecart'           => $ecart,
        'statut_bilan'    => (abs($ecart) < 0.01) ? 'ÉQUILIBRÉ' : (($ecart > 0) ? 'SURPLUS' : 'DÉFICIT')
    ];
}

    /**
     * RÉOUVERTURE (Correction)
     */
    public function reouvrirCaisseSession($caisseSessionId)
    {
        return DB::transaction(function () use ($caisseSessionId) {
            $caisse = CaisseSession::findOrFail($caisseSessionId);

            if ($caisse->statut !== 'FE') throw new Exception("Seule une caisse fermée peut être rouverte.");
            if ($caisse->guichetSession->statut !== 'OU') throw new Exception("Le guichet est déjà fermé.");

            $caisse->update([
                'statut' => 'RE',
                'solde_fermeture' => null,
                'heure_fermeture' => null
            ]);

            return $caisse;
        });
    }

    private function calculerMontantBilletage(array $billetage)
    {
        $total = 0;
        foreach ($billetage as $coupure => $quantite) {
            $total += ((int)$coupure * (int)$quantite);
        }
        return $total;
    }
}
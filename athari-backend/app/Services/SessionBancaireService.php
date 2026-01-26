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
use Exception;

class SessionBancaireService
{
    /**
     * ÉTAPE 1 : OUVERTURE DE LA JOURNÉE COMPTABLE
     */
    public function ouvrirJourneeComptable($agenceId, $dateSaisie, $utilisateurId)
    {
        return DB::transaction(function () use ($agenceId, $dateSaisie, $utilisateurId) {
            
            $journeeActive = JourComptable::where('agence_id', $agenceId)
                ->where('statut', 'OUVERT')
                ->first();

            if ($journeeActive) {
                throw new Exception("Une journée comptable est déjà en cours pour cette agence.");
            }

            $derniereJournee = JourComptable::where('agence_id', $agenceId)
                ->orderBy('date_du_jour', 'desc')
                ->first();

            if ($derniereJournee && $dateSaisie <= $derniereJournee->date_du_jour) {
                throw new Exception("La nouvelle date doit être supérieure à la dernière date clôturée.");
            }

            return JourComptable::create([
                'agence_id' => $agenceId,
                'date_du_jour' => $dateSaisie,
                'date_precedente' => $derniereJournee?->date_du_jour,
                'statut' => 'OUVERT',
                'ouvert_at' => now(),
                'execute_par' => $utilisateurId
            ]);
        });
    }

    /**
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
            
            // 1. Vérification : Toutes les caisses de l'agence doivent être fermées
            $caissesOuvertes = CaisseSession::whereHas('guichetSession', function($q) use ($agenceSessionId) {
                $q->where('agence_session_id', $agenceSessionId);
            })->where('statut', '!=', 'FE')->exists();

            if ($caissesOuvertes) {
                throw new Exception("Le traitement est impossible : toutes les caisses ne sont pas fermées.");
            }

            // 2. Calcul des agrégats pour toutes les caisses
            $sessionsCaisses = CaisseSession::whereHas('guichetSession', function($q) use ($agenceSessionId) {
                $q->where('agence_session_id', $agenceSessionId);
            })->get();

            $bilanGlobal = [
                'entrees' => 0, 'sorties' => 0, 'theorique' => 0, 'reel' => 0, 'details' => []
            ];

            foreach ($sessionsCaisses as $s) {
                $stats = $this->genererBilanCaisse($s->id);
                $bilanGlobal['entrees']   += $stats['total_entrees'];
                $bilanGlobal['sorties']   += $stats['total_sorties'];
                $bilanGlobal['theorique'] += $stats['solde_theorique'];
                $bilanGlobal['reel']      += $stats['solde_reel'];
                $bilanGlobal['details'][] = [
                    'caisse_id' => $s->caisse_id,
                    'libelle'   => $s->caisse->libelle,
                    'caissier'  => $s->caissier_id,
                    'ecart'     => $stats['ecart'],
                    'statut'    => $stats['statut_bilan']
                ];
            }

            // 3. Enregistrement ou Mise à jour du Snapshot (Bilan Journalier)
            // On utilise updateOrInsert pour pouvoir relancer le traitement si besoin avant fermeture
            return DB::table('bilan_journalier_agences')->updateOrInsert(
                ['jour_comptable_id' => $jourComptableId],
                [
                    'date_comptable'         => now()->toDateString(),
                    'total_especes_entree'   => $bilanGlobal['entrees'],
                    'total_especes_sortie'   => $bilanGlobal['sorties'],
                    'solde_theorique_global' => $bilanGlobal['theorique'],
                    'solde_reel_global'      => $bilanGlobal['reel'],
                    'ecart_global'           => $bilanGlobal['reel'] - $bilanGlobal['theorique'],
                    'resume_caisses'         => json_encode($bilanGlobal['details']),
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
                        $bilanExiste = BilanJournalierAgence::where('jour_comptable_id',$jourComptableId)->exists();
                
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
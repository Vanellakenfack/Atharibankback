<?php

namespace App\Services;

use App\Models\SessionAgence\JourComptable;
use App\Models\SessionAgence\AgenceSession;
use App\Models\SessionAgence\GuichetSession;
use App\Models\SessionAgence\CaisseSession;
use Illuminate\Support\Facades\DB;
use Exception;

class SessionBancaireService
{
    /**
     * ÉTAPE 1 : OUVERTURE DE LA JOURNÉE COMPTABLE
     * Définit la date de référence pour tout le système.
     */
    public function ouvrirJourneeComptable($agenceId, $dateSaisie, $utilisateurId)
    {
        return DB::transaction(function () use ($agenceId, $dateSaisie, $utilisateurId) {
            
            // Vérifier si une journée est déjà ouverte
            $journeeActive = JourComptable::where('agence_id', $agenceId)
                ->where('statut', 'OUVERT')
                ->first();

            if ($journeeActive) {
                throw new Exception("Une journée comptable est déjà en cours pour cette agence.");
            }

            // Cohérence de la date (interdire le retour dans le passé)
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
     * Autorise l'agence physique à opérer pour la journée comptable.
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
     * Active un guichet spécifique au sein de l'agence ouverte.
     */
    public function ouvrirGuichetSession($agenceSessionId, $codeGuichet)
    {
        $agenceSession = AgenceSession::findOrFail($agenceSessionId);

        if ($agenceSession->statut !== 'OU') {
            throw new Exception("L'agence doit être ouverte (statut OU) avant d'ouvrir un guichet.");
        }

        // Vérifier si ce guichet n'est pas déjà ouvert
        $dejaOuvert = GuichetSession::where('agence_session_id', $agenceSessionId)
            ->where('code_guichet', $codeGuichet)
            ->where('statut', 'OU')
            ->first();

        if ($dejaOuvert) {
            throw new Exception("Ce guichet est déjà ouvert pour cette session.");
        }

        return GuichetSession::create([
            'agence_session_id' => $agenceSessionId,
            'code_guichet' => $codeGuichet,
            'statut' => 'OU',
            'heure_ouverture' => now()
        ]);
    }

    /**
     * ÉTAPE 4 : OUVERTURE DE LA CAISSE
     * Responsabilise le caissier et valide le solde initial.
     */
   public function ouvrirCaisseSession($guichetSessionId, $caissierId, $soldeOuvertureSaisi, array $detailsBilletage)
{
    return DB::transaction(function () use ($guichetSessionId, $caissierId, $soldeOuvertureSaisi, $detailsBilletage) {
        
        // 1. Vérification du Guichet (Condition obligatoire du manuel)
        $guichet = GuichetSession::findOrFail($guichetSessionId);
        if ($guichet->statut !== 'OU') {
            throw new Exception("Le guichet doit être ouvert avant l'ouverture de la caisse.");
        }

        // 2. Récupération du Solde Informatique (Dernière clôture FE)
        $derniereSession = CaisseSession::where('caissier_id', $caissierId)
            ->where('statut', 'FE')
            ->orderBy('id', 'desc')
            ->first();

        $soldeInformatique = $derniereSession ? (float)$derniereSession->solde_fermeture : 0;

        // 3. Calcul du montant total du billetage (Montant Billeté)
        $montantBillete = 0;
        foreach ($detailsBilletage as $coupure => $quantite) {
            $montantBillete += ($coupure * $quantite);
        }

        // 4. VERIFICATION DE L'AJUSTAGE (Règle d'or du manuel)
        // Le montant saisi doit être égal au montant billeté
        if ((float)$soldeOuvertureSaisi !== (float)$montantBillete) {
            throw new Exception("Le billetage n'est pas correct : le montant billeté ne correspond pas au solde saisi.");
        }

        // Le solde saisi doit être égal au solde informatique
        if ((float)$soldeOuvertureSaisi !== $soldeInformatique) {
            throw new Exception("Erreur d'ajustage : Le solde saisi ($soldeOuvertureSaisi) diffère du solde informatique ($soldeInformatique).");
        }

        // 5. Création de la session (La caisse est ajustée)
        return CaisseSession::create([
            'guichet_session_id' => $guichetSessionId,
            'caissier_id'        => $caissierId,
            'solde_ouverture'    => $soldeOuvertureSaisi,
            'solde_informatique' => $soldeInformatique, // On garde une trace des deux
            'billetage_json'     => json_encode($detailsBilletage), // Stockage du détail F4
            'statut'             => 'OU'
        ]);
    });
}

    /**
 * FERMETURE DE LA CAISSE
 */
public function fermerCaisseSession($caisseSessionId, $soldeFermetureSaisi) {
    return DB::transaction(function () use ($caisseSessionId, $soldeFermetureSaisi) {
        $caisse = CaisseSession::findOrFail($caisseSessionId);
        
        if ($caisse->statut === 'FE') throw new Exception("Caisse déjà fermée.");

        $caisse->update([
            'solde_fermeture' => $soldeFermetureSaisi,
            'statut' => 'FE',
        ]);

        return $caisse;
    });
}

/**
 * FERMETURE DU GUICHET
 */
public function fermerGuichetSession($guichetSessionId) {
    $guichet = GuichetSession::findOrFail($guichetSessionId);
    
    // Vérifier si des caisses sont encore ouvertes au guichet
    $caissesOuvertes = CaisseSession::where('guichet_session_id', $guichetSessionId)
        ->where('statut', 'OU')->exists();

    if ($caissesOuvertes) throw new Exception("Fermez toutes les caisses avant de fermer le guichet.");

    $guichet->update(['statut' => 'FE', 'heure_fermeture' => now()]);
    return $guichet;
}

/**
 * FERMETURE DE L'AGENCE ET DE LA JOURNÉE (Traitement de fin de journée)
 */
public function fermerAgenceEtJournee($agenceSessionId, $jourComptableId) {
    return DB::transaction(function () use ($agenceSessionId, $jourComptableId) {
        // 1. Vérifier les guichets
        $guichetsOuverts = GuichetSession::where('agence_session_id', $agenceSessionId)
            ->where('statut', 'OU')->exists();
        if ($guichetsOuverts) throw new Exception("Des guichets sont encore ouverts.");

        // 2. Fermer l'agence
        AgenceSession::where('id', $agenceSessionId)->update([
            'statut' => 'FE', 
            'heure_fermeture' => now()
        ]);

        // 3. Fermer le jour comptable (Irréversible)
        return JourComptable::where('id', $jourComptableId)->update([
            'statut' => 'FERME',
            'ferme_at' => now()
        ]);
    });
}

/**
 * Générer le bilan de clôture d'une caisse
 */
public function genererBilanCaisse($caisseSessionId)
{
    $caisse = CaisseSession::findOrFail($caisseSessionId);

    // Simulation du calcul des mouvements (à lier avec votre future table transactions)
    // Pour l'instant, nous supposons des méthodes sommeDepots() et sommeRetraits()
    $totalDepots = DB::table('transactions')
        ->where('caisse_session_id', $caisseSessionId)
        ->where('type', 'DEPOT')
        ->sum('montant');

    $totalRetraits = DB::table('transactions')
        ->where('caisse_session_id', $caisseSessionId)
        ->where('type', 'RETRAIT')
        ->sum('montant');

    $soldeTheorique = $caisse->solde_ouverture + $totalDepots - $totalRetraits;
    $ecart = $caisse->solde_fermeture - $soldeTheorique;

    return [
        'caissier' => $caisse->caissier_id,
        'ouverture' => $caisse->solde_ouverture,
        'total_depots' => $totalDepots,
        'total_retraits' => $totalRetraits,
        'solde_theorique' => $soldeTheorique,
        'solde_reel_declare' => $caisse->solde_fermeture,
        'ecart' => $ecart,
        'statut_bilan' => ($ecart == 0) ? 'ÉQUILIBRÉ' : 'ERREUR',
    ];
}

/**
 * REOUVERTURE DE CAISSE (Passage de FE à RE)
 */
public function reouvrirCaisseSession($caisseSessionId)
{
    return DB::transaction(function () use ($caisseSessionId) {
        $caisse = CaisseSession::findOrFail($caisseSessionId);

        // 1. Vérifier si la caisse est bien fermée (on ne réouvre que ce qui est fermé)
        if ($caisse->statut !== 'FE') {
            throw new Exception("Seule une caisse fermée peut être rouverte.");
        }

        // 2. Vérifier si le guichet est toujours ouvert
        if ($caisse->guichetSession->statut !== 'OU') {
            throw new Exception("Impossible de rouvrir : le guichet associé est déjà fermé.");
        }

        // 3. Changement de statut vers 'RE' (Réouvert)
        $caisse->update([
            'statut' => 'RE',
            'solde_fermeture' => null, // On réinitialise le solde de fermeture
            'heure_fermeture' => null
        ]);

        return $caisse;
    });
}
/**
 * Récupère le solde de la dernière clôture pour une caisse précise
 */
public function getDernierSoldeFermeture($codeCaisse)
{
    // On cherche la dernière session fermée (FE) pour ce code de caisse
    $derniereSession = CaisseSession::where('code_caisse', $codeCaisse)
        ->where('statut', 'FE')
        ->orderBy('id', 'desc')
        ->first();

    // Si c'est une nouvelle caisse (pas d'historique), le solde est 0
    return $derniereSession ? (float) $derniereSession->solde_fermeture : 0.00;
}

/**
 * Calcule le montant total à partir d'un tableau de billetage
 * Exemple de $billetage : [10000 => 50, 5000 => 20, 2000 => 0...]
 */
private function calculerMontantBilletage(array $billetage)
{
    $total = 0;
    foreach ($billetage as $coupure => $quantite) {
        $total += ($coupure * $quantite);
    }
    return $total;
}


}
<?php

namespace App\Services;

use App\Models\Caisse\CaisseTransaction;
use App\Models\Compte\MouvementComptable;
use App\Models\Compte\Compte;
use App\Models\Caisse\TransactionTier;
use App\Models\Caisse\TransactionBilletage;
use App\Models\Caisse\Caisse;
use App\Models\SessionAgence\CaisseSession;
use Illuminate\Support\Facades\DB;
use App\Models\Caisse\CaisseDemandeValidation;
use Illuminate\Support\Facades\Notification; 
use App\Models\User;
use App\Notifications\RetraitDepassementPlafond; 
use Illuminate\Support\Facades\Log;            
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class CaisseService
{
    /**
     * Traite toute opération de caisse avec triple validation
     */
   public function traiterOperation(string $type, array $data, array $billetage)
{
    return DB::transaction(function () use ($type, $data, $billetage) {
        $user = auth()->user();
        
        // --- RÉCUPÉRATION DU TYPE DE VERSEMENT ---
        // On récupère le mode (ESPECE, ORANGE_MONEY, MOBILE_MONEY)
        $typeVersement = $data['type_versement'] ?? 'ESPECE';

        // --- RÉCUPÉRATION DE LA SESSION ---
        $session = CaisseSession::with(['caisse.guichet.agence'])
            ->where('caissier_id', $user->id)
            ->where('statut', 'OU') 
            ->first();

        if (!$session) {
            throw new Exception("Session de caisse introuvable ou déjà fermée.");
        }

        // --- CONTRÔLE DES PLAFONDS ---
        $plafondCaisse = $session->caisse->plafond_autonomie_caissiere ?? 500000;
        $montant = $data['montant_brut'];

        if ($type === 'RETRAIT' && $montant > $plafondCaisse) {
            if (!isset($data['code_validation'])) {
                return $this->creerDemandeValidation($type, $data, $billetage, $user);
            }
            $this->verifierCodeApprouve($data['code_validation'], $user->id, $montant);
        }

        // 1. Validation du Jour Comptable
        $jourComptable = DB::table('jours_comptables')->where('statut', 'OUVERT')->first();
        if (!$jourComptable) throw new Exception("Le jour comptable est fermé.");
        $dateBancaire = $jourComptable->date_du_jour;

        // 3. Gestion du Compte Client & Verrouillage
        $compte = null;
        if (isset($data['compte_id'])) {
            $compte = Compte::with('typeCompte')
                ->where('id', $data['compte_id'])
                ->lockForUpdate()
                ->first();
                
            $this->verifierStatutCompte($compte);
        }

        // 4. Contrôle de Provision
        $this->validerEligibilite($type, $compte, $data['montant_brut']);

        // 5. Enregistrement Transaction Caisse
        // La fonction enregistrerTransactionCaisse utilisera désormais $typeVersement
        $transaction = $this->enregistrerTransactionCaisse($type, $data, $dateBancaire, $session);

        // 6. Enregistrement des informations du tiers
        if (isset($data['tiers'])) {
            $nom = $data['tiers']['nom_complet'];
            $piece = $data['tiers']['numero_piece'];
        } else {
            $nom = $data['porteur_nom'] ?? 'N/A';
            $piece = $data['piece_identite_ref'] ?? 'N/A';
        }

        TransactionTier::create([
            'transaction_id' => $transaction->id,
            'nom_complet'    => $nom,
            'type_piece'     => $data['tiers']['type_piece'] ?? 'CNI',
            'numero_piece'   => $piece,
        ]);

        // 7. ENREGISTREMENT BILLETAGE (Conditionnel)
        // On n'enregistre le billetage QUE si c'est de l'ESPECE
        if ($typeVersement === 'ESPECE' && !empty($billetage)) {
            $this->validerBilletage($billetage, $data['montant_brut']); // Sécurité supplémentaire
            $this->enregistrerBilletage($transaction->id, $billetage);
        }

        // 8. Mouvement Comptable
        // Cette fonction utilise désormais l'ENUM type_versement pour choisir les comptes UV ou Marchand
        $this->genererEcritureComptable($type, $transaction, $compte, $dateBancaire, $session);

        // 9. Mise à jour du Solde Client
        $this->actualiserSoldeCompte($type, $compte, $data['net_a_percevoir_payer'] ?? $data['montant_brut']);

       
        if ($typeVersement === 'ESPECE') {
            $this->actualiserSoldesFinanciers($type, $session, $data['montant_brut']);
        }

        return $transaction;
    });
}
        private function enregistrerTransactionCaisse($type, $data, $dateBancaire, $session) {
            $caisse = $session->caisse;
            $guichet = $caisse->guichet;
            $agence = $guichet->agence; 
            $typeVersement = $data['type_versement'] ?? 'ESPECE';
            return CaisseTransaction::create([
                'reference_unique' => $this->generateReference($type),
                'compte_id'        => $data['compte_id'] ?? null,
                'code_agence'      => $agence->code ?? $guichet->agence_id,
                'code_guichet'     => $guichet->code_guichet ?? $guichet->id, 
                'code_caisse'      => $caisse->code_caisse ?? $caisse->id,
                'type_versement'   => $typeVersement, 
               'reference_externe'=> $data['reference_externe'] ?? null, // ID Orange/MTN
                'origine_fonds'    => $data['origine_fonds'] ?? null,
                'numero_bordereau' => $data['numero_bordereau'] ,
                'type_bordereau'   => $data['type_bordereau'] ,
                'type_flux'        => $type,
                'montant_brut'     => $data['montant_brut'],
                'commissions'      => $data['commissions'] ?? 0,
                'taxes'            => $data['taxes'] ?? 0,

                // MODIFICATION : Conversion explicite en booléen
                // On vérifie si la clé existe, sinon on met 'true' par défaut
                'frais_en_compte'  => filter_var($data['frais_en_compte'] ?? true, FILTER_VALIDATE_BOOLEAN),

                'date_operation'   => $dateBancaire,
                'date_valeur'      => $data['date_valeur'] ?? $dateBancaire,
                'caissier_id'      => auth()->id(),
                'statut'           => 'VALIDE'
            ]);
        }
    /**
     * Met à jour à la fois le coffre physique et la session du caissier
     */
    private function actualiserSoldesFinanciers($type, $session, $montant) {
        $operation = in_array($type, ['VERSEMENT', 'ENTREE_CAISSE']) ? 'increment' : 'decrement';
        
        // 1. Mise à jour de la session informatique (pour le bilan de clôture)
        $session->$operation('solde_informatique', $montant);
        
        // 2. Mise à jour de la caisse physique (le contenu réel du coffre)
        $session->caisse->$operation('solde_actuel', $montant);
    }

    private function verifierStatutCompte($compte) {
        if (!$compte || $compte->statut !== 'actif') {
            throw new Exception("Désaccord : Compte client inexistant, bloqué ou fermé (FRME).");
        }
    }

    private function validerEligibilite($type, $compte, $montant) {
        if ($type === 'RETRAIT' && $compte) {
            $soldeDispo = (float)$compte->solde - (float)$compte->montant_indisponible + (float)$compte->autorisation_decouvert;
            if ($montant > $soldeDispo) {
                throw new Exception("Désaccord : Provision insuffisante sur le compte (SPRV).");
            }
        }
    }

        private function genererEcritureComptable($type, $transaction, $compte, $dateBancaire, $session) {
                $schema = $this->getSchemaComptable($type, $transaction, $session);

                if (!$schema['debit'] || !$schema['credit']) {
                    throw new Exception("Erreur: Impossible de trouver l'ID pour les codes comptables fournis.");
                }

                // Écriture principale
                MouvementComptable::create([
                    'compte_id'           => $compte->id,
                    'date_mouvement'      => $dateBancaire,
                    'libelle_mouvement'   => "[" . $transaction->type_versement . "] " . $type . " - " . $transaction->reference_unique,
                    'compte_debit_id'     => $schema['debit'],
                    'compte_credit_id'    => $schema['credit'],
                    'montant_debit'       => $transaction->montant_brut,
                    'montant_credit'      => $transaction->montant_brut,
                    'reference_operation' => $transaction->reference_unique,
                    'statut'              => 'COMPTABILISE',  
                    'journal'             => 'CAISSE',
                    'auteur_id'           => auth()->id(),
                ]);

                // Écriture de commission si applicable (Lignes 5 et 6 de votre image)
                if ($schema['commission_account'] && $transaction->commissions > 0) {
                    MouvementComptable::create([
                        'compte_id'           => $compte->id,
                        'date_mouvement'      => $dateBancaire,
                        'libelle_mouvement'   => "COMMISSION MARCHAND " . $transaction->type_versement,
                        'compte_debit_id'     => $schema['commission_account'],
                        'compte_credit_id'    => $schema['credit'],
                        'montant_debit'       => $transaction->commissions,
                        'montant_credit'      => $transaction->commissions,
                        'reference_operation' => $transaction->reference_unique,
                        'journal'             => 'CAISSE',
                        'auteur_id'           => auth()->id(),
                    ]);
                }
            }
            private function getSchemaComptable($type, $transaction, $session) {
                $typeVersement = $transaction->type_versement ?? 'ESPECE';
                
                // On définit le CODE du compte de trésorerie selon votre image
                $codeTresorerie = match ($typeVersement) {
                    'ORANGE_MONEY' => '57112000', // CAISSE ORANGE MARCHAND (UV)
                    'MOBILE_MONEY' => '57113001', // CAISSE MTN MOBILE MONEY (UV)
                    default        => null,       // Sera géré par l'ID par défaut de la caisse
                };

                // Récupération de l'ID à partir du code (On cherche dans la table plan_comptable)
                $pc_tresorerie_id = $codeTresorerie 
                    ? DB::table('plan_comptable')->where('code', $codeTresorerie)->value('id')
                    : $session->caisse->compte_comptable_id; //

                // Récupération dynamique du compte client
                $pc_client_id = $transaction->compte->typeCompte->chapitre_defaut_id;

                // Codes de commissions selon votre image
                $codeCommission = match ($typeVersement) {
                    'MOBILE_MONEY' => '67100001', // COMMISSION MTN
                    'ORANGE_MONEY' => '67100002', // COMMISSION ORANGE
                    default        => null,
                };

                $pc_commission_id = $codeCommission 
                    ? DB::table('plan_comptable')->where('code', $codeCommission)->value('id')
                    : null;

                return match ($type) {
                    'VERSEMENT' => [
                        'debit'  => $pc_tresorerie_id, 
                        'credit' => $pc_client_id,
                        'commission_account' => $pc_commission_id
                    ],
                    'RETRAIT' => [
                        'debit'  => $pc_client_id, 
                        'credit' => $pc_tresorerie_id,
                        'commission_account' => null
                    ],
                    default => throw new Exception("Type d'opération inconnu."),
                };
            }

       private function actualiserSoldeCompte($type, $compte, $montant) {
                if (!$compte) return;

                $montant = abs($montant);

                if (in_array($type, ['VERSEMENT', 'ENTREE_CAISSE'])) {
                    // SQL: UPDATE comptes SET solde = solde + montant WHERE id = ...
                    $compte->increment('solde', $montant);
                } 
                elseif (in_array($type, ['RETRAIT', 'SORTIE_CAISSE'])) {
                    // SQL: UPDATE comptes SET solde = solde - montant WHERE id = ...
                    $compte->decrement('solde', $montant);
                }
                
                
            }

    private function enregistrerBilletage($transactionId, $billetage) {
        foreach ($billetage as $item) {
            TransactionBilletage::create([
                'transaction_id' => $transactionId,
                'valeur_coupure' => $item['valeur'],
                'quantite'       => $item['quantite'],
                'sous_total'     => $item['valeur'] * $item['quantite']
            ]);
        }
    }

    private function generateReference($type) {
        return substr($type, 0, 3) . '-' . date('YmdHis') . '-' . strtoupper(str()->random(4));
    }


    /**
 * Met l'opération en attente et génère une réponse pour le Front-end
 */
private function creerDemandeValidation($type, $data, $billetage, $user)
{
    // On s'assure que reference_unique existe dans le payload pour le futur reçu
    if (!isset($data['reference_unique'])) {
        $data['reference_unique'] = $this->generateReference($type);
    }

    $payload = array_merge($data, ['billetage' => $billetage]);

    $demande = CaisseDemandeValidation::create([
        'type_operation' => $type,
        'payload_data'   => $payload,
        'montant'        => $data['montant_brut'],
        'caissiere_id'   => $user->id,
        'statut'         => 'EN_ATTENTE'
    ]);

    $assistants = User::whereHas('roles', function($q) {
        $q->where('name', 'Assistant Comptable ');
    })->get();

    // On utilise try/catch pour que la caissière reçoive quand même son message 
    // même si l'envoi de notification bugge
    try {
        Notification::send($assistants, new RetraitDepassementPlafond($demande));
    } catch (\Exception $e) {
        // Loggez l'erreur mais ne bloquez pas l'utilisateur
        \Log::error("Erreur notification: " . $e->getMessage());
    }

    return [
        'requires_validation' => true,
        'demande_id' => $demande->id,
        'message' => "Le montant (" . number_format($data['montant_brut'], 0, ',', ' ') . " FCFA) dépasse votre plafond. Demande envoyée."
    ];
}

/**
 * Vérifie si le code saisi par la caissière est valide et lié à une demande approuvée
 */
private function verifierCodeApprouve($code, $caissiereId, $montant)
{
    $demande = CaisseDemandeValidation::where('code_validation', $code)
        ->where('caissiere_id', $caissiereId)
        ->where('montant', $montant)
        ->where('statut', 'APPROUVE')
        ->first();

    if (!$demande) {
        throw new Exception("Code de validation invalide ou l'opération n'a pas encore été approuvée par l'assistant.");
    }

    // Très important : Marquer comme EXECUTE pour ne pas réutiliser le même code
    $demande->update(['statut' => 'EXECUTE']);
}

public function genererRecu($transactionId)
{
    $transaction = CaisseTransaction::with(['compte.client', 'tier', 'demandeValidation.assistant'])
        ->findOrFail($transactionId);

   

    return view('recus.transaction', compact('transaction'));
}


public function obtenirRecapitulatifCloture($sessionId)
{
    return CaisseTransaction::where('session_id', $sessionId)
        ->where('statut', 'VALIDE')
        ->select('type_versement', 'type_flux')
        ->selectRaw('SUM(montant_brut) as total')
        ->groupBy('type_versement', 'type_flux')
        ->get()
        ->groupBy('type_versement');
}

public function obtenirJournalCaisseComplet($filtres)
{
    // 1. Calcul du solde d'ouverture (Sécurisé)
    $caisse = DB::table('caisses')->where('id', $filtres['caisse_id'])->first();
    
    // On cherche d'abord 'solde_initial', sinon 'solde_ouverture', sinon 0
    $soldeOuverture = 0;
    if ($caisse) {
        if (isset($caisse->solde_initial)) {
            $soldeOuverture = $caisse->solde_initial;
        } elseif (isset($caisse->solde_ouverture)) {
            $soldeOuverture = $caisse->solde_ouverture;
        }
    }

    $mouvements = DB::table('mouvements_comptables as mc')
        // Jointure comptabilité -> transaction
        ->join('caisse_transactions as ct', 'mc.reference_operation', '=', 'ct.reference_unique')
        
        // Jointure vers la caisse par le code (plus fiable selon votre structure)
        ->join('caisses as ca', 'ct.code_caisse', '=', 'ca.code_caisse')
        
        // Jointure vers les comptes
        ->join('comptes as c', 'mc.compte_id', '=', 'c.id')
        
        // Jointure vers le tiers (via transaction_id selon vos contraintes étrangères)
        ->leftJoin('transaction_tiers as tt', 'ct.id', '=', 'tt.transaction_id') 
        
        ->select([
            'c.numero_compte',
            'tt.nom_complet as tiers_nom', 
            'mc.libelle_mouvement',
            'mc.reference_operation',
            'mc.montant_debit',
            'mc.montant_credit',
            'ct.type_versement',
            'ca.code_caisse',
            'ct.code_agence',
            'mc.date_mouvement'
        ])
        ->where('ca.id', $filtres['caisse_id'])
        ->where('ct.code_agence', $filtres['code_agence'])
        ->whereBetween('mc.date_mouvement', [$filtres['date_debut'], $filtres['date_fin']])
        
        // Exclusion des frais/commissions (Comptes commençant par 671)
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('plan_comptable as pc')
                  ->whereRaw('(pc.id = mc.compte_debit_id OR pc.id = mc.compte_credit_id)')
                  ->whereRaw('pc.code LIKE "671%"');
        })
        ->orderBy('mc.date_mouvement', 'asc')
        ->get();

    // 3. Retour des données avec calculs
    return [
        'solde_ouverture' => (float)$soldeOuverture,
        'mouvements'      => $mouvements,
        'total_debit'     => (float)$mouvements->sum('montant_debit'),
        'total_credit'    => (float)$mouvements->sum('montant_credit'),
        'solde_cloture'   => (float)($soldeOuverture + $mouvements->sum('montant_debit') - $mouvements->sum('montant_credit'))
    ];
}
}
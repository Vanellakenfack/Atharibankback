<?php

namespace App\Services;

use App\Models\Caisse\CaisseTransaction;
use App\Models\Compte\MouvementComptable;
use App\Models\Compte\Compte;
use App\Models\Caisse\TransactionTier;
use App\Models\Caisse\TransactionBilletage;
use App\Models\Caisse\Caisse;
use App\Models\Caisse\CaisseDigitale;
use App\Models\SessionAgence\CaisseSession;
use Illuminate\Support\Facades\DB;
use App\Models\Caisse\CaisseDemandeValidation;
use Illuminate\Support\Facades\Notification; 
use App\Models\User;
use App\Notifications\RetraitDepassementPlafond; 
use Illuminate\Support\Facades\Log;            
use Exception;
use App\Models\compte\FraisEnAttente;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Caisse\CaisseTransactionDigitale;

class CaisseService
{
    /**
     * Traite toute opération de caisse avec triple validation
     */
public function traiterOperation(string $type, array $data, array $billetage)
{
    return DB::transaction(function () use ($type, $data, $billetage) {
        $user = auth()->user();

       
        
        $typeVersement = $data['type_versement'] ?? 'ESPECE';
        $montant = (float) $data['montant_brut']; // Forcer le type float pour la comparaison
        // 1. Session et Plafond (Contrôle bloquant)
        $session = CaisseSession::with(['caisse.guichet.agence'])->where('caissier_id', $user->id)->where('statut', 'OU')->first();
        if (!$session) throw new Exception("Session de caisse introuvable.");

       $seuilCaissiere = 500000;
        
        if ($type === 'RETRAIT' && $montant > $seuilCaissiere) {
            // Si pas de code de validation fourni par le front
            if (!isset($data['code_validation']) || empty($data['code_validation'])) {
                
                // On détermine le rôle cible
               

                 if ($montant > 10000000) {
                        $roleCible = 'DG';
                    } elseif ($montant > 5000000) {
                        $roleCible = 'Chef Comptable';
                    } elseif ($montant > 500000) {
                        $roleCible = 'Assistant Comptable (AC)';
                    }else {
                        $roleCible = 'Chef d\'Agence';
                    }

                // STOP : On génère la demande et on arrête tout
                return $this->creerDemandeValidation($type, $data, $billetage, $user, $roleCible);
            }

            // Si un code est présent, on vérifie qu'il est valide
            $this->verifierCodeApprouve($data['code_validation'], $user->id, $montant);
        }

        //flux digital sans plafond
        if (in_array($typeVersement, ['ORANGE_MONEY', 'MOBILE_MONEY'])) {
            return $this->traiterFluxDigital($type, $data, $billetage, $session);
        }

         // plafond de 500 000 FCFA pour les opérations en espèces


         // --- Gestion des seuils de validation ---
      

        // 2. Préparation des données (Calcul commission AVANT traitement)
        $compte = Compte::with('typeCompte')->where('id', $data['compte_id'])->lockForUpdate()->first();
        $this->verifierStatutCompte($type, $compte);

        $commission = 0;
        if ($type === 'RETRAIT' && $compte && $compte->typeCompte->chapitre_commission_retrait_id) {
            $commission = $this->calculerCommissionRetrait($compte, $montant);
        }
        $data['commissions'] = $commission; // Injecté avant l'enregistrement

        // 3. Validation éligibilité (Provision)
        $this->validerEligibilite($type, $compte, $montant);

        // 4. Enregistrements (Transaction & Tiers)
        $jourComptable = DB::table('jours_comptables')->where('statut', 'OUVERT')->first();
        $dateBancaire = $jourComptable->date_du_jour;
        
        $transaction = $this->enregistrerTransactionCaisse($type, $data, $dateBancaire, $session);
        
        // Enregistrement Tiers... (votre code ici)

        $this->enregistrerTiers($transaction->id, $data);

        // 5. Gestion de l'argent physique (UNE SEULE FOIS)
        if ($typeVersement === 'ESPECE') {
            if (!empty($billetage)) {
                $this->validerBilletage($billetage, $montant);
                $this->enregistrerBilletage($transaction->id, $billetage);
            }
            $this->actualiserSoldesFinanciers($type, $session, $montant);
        }

        // 6. Écritures Comptables
        $this->genererEcritureComptable($type, $transaction, $compte, $dateBancaire, $session);

        // 7. Mise à jour Solde Client (Net à percevoir)
        $montantImpactClient = $data['net_a_percevoir_payer'] ?? $montant;
        $this->actualiserSoldeCompte($type, $compte, $montantImpactClient);
        
        $compte->refresh(); // Très important pour la suite

        // 8. Traitements automatiques
        if ($type === 'VERSEMENT') {
            $this->apurerFraisEnAttente($compte, $dateBancaire);
        }

        if ($type === 'RETRAIT' && !$compte->typeCompte->a_vue) {
            $this->gererRenouvellementBlocage($compte);
        }

        

        return $transaction;
    });
}



private function traiterFluxDigital($type, $data, $billetage, $session)
{
    $operateur = $data['type_versement']; // ORANGE_MONEY ou MOBILE_MONEY
    $isPassage = empty($data['compte_id']); 
    $montant = (float) $data['montant_brut'];

    // 1. GESTION COMPTE BANCAIRE (Si client banque)
    $compte = null;
    $commission = 0;
    if (!$isPassage) {
        $compte = Compte::with('typeCompte')->where('id', $data['compte_id'])->lockForUpdate()->first();
        $this->verifierStatutCompte($type, $compte);
        
        if ($type === 'RETRAIT' && $compte->typeCompte->chapitre_commission_retrait_id) {
            $commission = $this->calculerCommissionRetrait($compte, $montant);
        }
        $this->validerEligibilite($type, $compte, $montant);
        
        // Mise à jour du solde bancaire (informatique) uniquement
        $this->actualiserSoldeCompte($type, $compte, $montant);
    }
    $data['commissions'] = $commission;

    // 2. ENREGISTREMENT DANS LA TABLE DIGITALE DÉDIÉE
    $transaction = $this->enregistrerSeuleTransactionDigitale($type, $data, $session);
    
    // 3. ACTUALISATION DE LA CAISSE DIGITALE (Exclusivement)
    // C'est ici que l'argent physique est mouvementé dans la "pochette"
    $this->actualiserCaisseDigitale($type, $session->caisse_id, $operateur, $montant);

    // 4. ÉCRITURES COMPTABLES
    $schema = $this->getSchemaComptableDigital($type, $operateur, $isPassage);
    $this->genererEcrituresDigitales($transaction, $schema, $data, $session);

    // 5. BILLETAGE (Si retrait, le caissier prend les billets dans sa pochette digitale)
    if ($type === 'RETRAIT' && !empty($billetage)) {
        $this->validerBilletage($billetage, $montant);
        $this->enregistrerBilletage($transaction->id, $billetage, 'DIGITAL');
    }

    // 6. SOLDE INFORMATIQUE DE SESSION (Pour le pointage de fin de journée)
    $operation = in_array($type, ['VERSEMENT', 'ENTREE_CAISSE']) ? 'increment' : 'decrement';
    $session->$operation('solde_informatique', $montant);

    return $transaction;
}

private function getSchemaComptableDigital($type, $operateur, $isPassage)
{
    $map = [
        'ORANGE_MONEY' => [
            'espece'   => '57111000', // CAISSE OM (Espece)
            'uv'       => '57111001', // CAISSE OM (UV)
            'marchand' => '57112000', // CAISSE MARCHAND (UV)
        ],
        'MOBILE_MONEY' => [
            'espece'   => '57113000', // CAISSE MTN (Espece)
            'uv'       => '57113001', // CAISSE MTN (UV)
            'marchand' => '57114001', // CAISSE MARCHAND (UV)
        ]
    ];

    $comptes = $map[$operateur];
    
    // Détermination Débit/Crédit
    if ($isPassage) {
        // Client de rue : Cash contre UV
        return [
            'debit'  => ($type === 'VERSEMENT') ? $comptes['espece'] : $comptes['uv'],
            'credit' => ($type === 'VERSEMENT') ? $comptes['uv'] : $comptes['espece'],
            'commission' => $comptes['marchand']
        ];
    } else {
        // Client Banque : Compte contre UV
        return [
            'debit'  => ($type === 'VERSEMENT') ? $comptes['uv'] : 'CLIENT',
            'credit' => ($type === 'VERSEMENT') ? 'CLIENT' : $comptes['uv'],
            'commission' => $comptes['marchand']
        ];
    }
}

private function actualiserCaisseDigitale($type, $caisseId, $operateur, $montant)
{
    $caisseDigitale = CaisseDigitale::where('caisse_id', $caisseId)
        ->where('operateur', $operateur)
        ->lockForUpdate()
        ->first();

    if (!$caisseDigitale) {
        throw new \Exception("Caisse digitale non configurée pour l'opérateur $operateur");
    }

    if (in_array($type, ['VERSEMENT', 'ENTREE_CAISSE'])) {
        // VERSEMENT (Dépôt) : 
        // Le caissier reçoit du CASH (+) dans sa pochette
        // Le caissier perd des UV (-) sur son téléphone
        $caisseDigitale->increment('solde_espece', $montant);
        $caisseDigitale->decrement('solde_virtuel_uv', $montant);
    } else {
        // RETRAIT : 
        // Le caissier donne du CASH (-) de sa pochette
        // Le caissier reçoit des UV (+) sur son téléphone
        $caisseDigitale->decrement('solde_espece', $montant);
        $caisseDigitale->increment('solde_virtuel_uv', $montant);
    }
}

private function enregistrerSeuleTransactionDigitale($type, $data, $session) 
{
    // Récupération de la hiérarchie pour l'audit
    $caisse = $session->caisse()->with('guichet.agence')->first();
    
    return DB::transaction(function () use ($type, $data, $session, $caisse) {
        return CaisseTransactionDigitale::create([
            'reference_unique'    => $this->generateReference($type . '_DIG'),
            'session_id'          => $session->id,
            'compte_id'           => $data['compte_id'] ?? null,
            
            'code_agence'         => $caisse->guichet->agence->code ?? $caisse->agence_id,
            'code_guichet'        => $caisse->guichet->code_guichet ?? $caisse->guichet_id,
            'code_caisse'         => $caisse->id,
            
            'type_flux'           => $type,
            'operateur'           => $data['type_versement'], // OM ou MOMO
            'montant_brut'        => $data['montant_brut'],
            'commissions'         => $data['commissions'] ?? 0,
            
            'reference_operateur' => $data['reference_externe'],
            'telephone_client'    => $data['telephone_client'] ?? null,
            
            'date_operation'      => now(),
            'date_valeur'         => now(),
            'caissier_id'         => auth()->id(),
            'statut'              => 'VALIDE'
        ]);
    });
}

private function genererEcrituresDigitales($transaction, $schema, $data, $session)
{
    $dateOp = DB::table('jours_comptables')->where('statut', 'OUVERT')->value('date_du_jour');
    
    $agenceId = $session->caisse->agence_id 
                                ?? $session->caisse->guichet->agence_id 
                                ?? $session->agence_id;


   
    // 2. RÉCUPÉRER LES IDS DU PLAN COMPTABLE via leurs codes
    // Débit
    $compteDebitPlanId = ($schema['debit'] === 'CLIENT') 
        ? Compte::findOrFail($data['compte_id'])->typeCompte->chapitre_defaut_id
        : DB::table('plan_comptable')->where('code', $schema['debit'])->value('id');

    // Crédit
    $compteCreditPlanId = ($schema['credit'] === 'CLIENT') 
        ? Compte::findOrFail($data['compte_id'])->typeCompte->chapitre_defaut_id
        : DB::table('plan_comptable')->where('code', $schema['credit'])->value('id');

    // 3. LOGIQUE DU COMPTE CLIENT (L'ID de la table 'comptes')
    $clientDebitId = ($schema['debit'] === 'CLIENT') ? $data['compte_id'] : null;
    $clientCreditId = ($schema['credit'] === 'CLIENT') ? $data['compte_id'] : null;

    // --- A. ÉCRITURE DE DÉBIT ---
    MouvementComptable::create([
        'agence_id'           => $agenceId,           // ID numérique
        'compte_id'           => $clientDebitId,      // ID numérique du client ou null
        'compte_debit_id'     => $compteDebitPlanId,  // ID numérique du plan comptable
        'compte_credit_id'    => $compteCreditPlanId,
        'montant_debit'       => $transaction->montant_brut,
        'montant_credit'      => 0,
        'reference_operation' => $transaction->reference_unique,
        'libelle_mouvement'   => "FLUX DIGITAL {$transaction->operateur} (DEBIT)",
        'date_mouvement'      => $dateOp,
        'journal'             => 'CAISSE_DIGITALE',
        'statut'              => 'COMPTABILISE',

        'auteur_id'           => auth()->id(),
    ]);

    // --- B. ÉCRITURE DE CRÉDIT ---
    MouvementComptable::create([
        'agence_id'           => $agenceId,
        'compte_id'           => $clientCreditId,
        'compte_debit_id'     => $compteDebitPlanId,
        'compte_credit_id'    => $compteCreditPlanId,
        'montant_debit'       => 0,
        'montant_credit'      => $transaction->montant_brut,
        'reference_operation' => $transaction->reference_unique,
        'libelle_mouvement'   => "FLUX DIGITAL {$transaction->operateur} (CREDIT)",
        'date_mouvement'      => $dateOp,
        'journal'             => 'CAISSE_DIGITALE',
        'auteur_id'           => auth()->id(),
        'statut'              => 'COMPTABILISE'
    ]);

    // --- C. GESTION DES COMMISSIONS ---
    if ($transaction->commissions > 0 && $schema['commission']) {
        $compteCommPlanId = DB::table('plan_comptable')->where('code', $schema['commission'])->value('id');
        
        // La commission est une écriture séparée (Produit pour la banque)
        //$this->enregistrerCommissionComptable($transaction, $agenceId, $compteCommPlanId, $compteCreditPlanId, $dateOp);
    }
}
private function enregistrerTransactionCaisse($type, $data, $dateBancaire, $session) {
            $caisse = $session->caisse;
            $guichet = $caisse->guichet;
            $agence = $guichet->agence; 
            $typeVersement = $data['type_versement'] ?? 'ESPECE';
            return CaisseTransaction::create([
                'reference_unique' => $this->generateReference($type),
                'compte_id'        => $data['compte_id'] ?? null,
                'session_id'       => $session->id,
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
                'origine_fonds'    => $data['origine_fonds'] ?? null,
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

 private function verifierStatutCompte($type,$compte) {
    if (!$compte) throw new Exception("Compte inexistant.");
    if ($type === 'RETRAIT' ) {
    // Bloque si le compte est encore "en attente" ou "sous opposition"
    if ($compte->statut === 'en_attente' || $compte->est_en_opposition) {
        throw new Exception(
            "Opération refusée : Compte en attente de validation juridique (NUI) " . 
            "ou accord du Chef d'Agence."
        );
    }
    }

    if ($compte->statut !== 'actif') {
        throw new Exception("Désaccord : Compte non actif.");
    }
}

   private function validerEligibilite($type, $compte, $montant) {
    if ($type === 'RETRAIT' && $compte) {
        $commission = 0;
        // Si le type de compte prévoit une commission de retrait
        if ($compte->typeCompte->chapitre_commission_retrait_id) {
            // Exemple : calcul dynamique (à adapter selon vos colonnes taux/fixe)
            $commission = $this->calculerCommissionRetrait($compte, $montant);
        }

        $totalNecessaire = $montant + $commission;
        $soldeDispo = (float)$compte->solde - (float)$compte->montant_indisponible + (float)$compte->autorisation_decouvert;
        
        if ($totalNecessaire > $soldeDispo) {
            throw new Exception("Désaccord : Provision insuffisante (Frais de retrait inclus).");
        }
    }
}
/**
 * Calcule la commission de retrait basée sur la configuration du type de compte
 */
private function calculerCommissionRetrait(Compte $compte, float $montantBrut): float
{
    $typeCompte = $compte->typeCompte;
    $commissionTotal = 0;

    // 1. Frais fixes (ex: 500 FCFA par retrait peu importe le montant)
    if (isset($typeCompte->commission_retrait_fixe)) {
        $commissionTotal += (float) $typeCompte->commission_retrait_fixe;
    }

    // 2. Frais proportionnels (ex: 1% du montant)
    if (isset($typeCompte->commission_retrait_taux) && $typeCompte->commission_retrait_taux > 0) {
        // Supposons que le taux est stocké en entier (1 pour 1%) ou en décimal (0.01)
        $taux = (float) $typeCompte->commission_retrait_taux;
        $commissionTotal += ($montantBrut * ($taux / 100));
    }

    return $commissionTotal;
}
        private function genererEcritureComptable($type, $transaction, $compte, $dateBancaire, $session) {
                $schema = $this->getSchemaComptable($type, $transaction, $session);
                // Dans genererEcritureComptable
                    $agenceId = $session->caisse->agence_id 
                                ?? $session->caisse->guichet->agence_id 
                                ?? $session->agence_id;

                    \Log::info("ID Agence détecté : " . $agenceId); // Vérifie si cette fois il y a un chiffre

                                    if (!$schema['debit'] || !$schema['credit']) {
                    throw new Exception("Erreur: Impossible de trouver l'ID pour les codes comptables fournis.");
                }

                // Écriture principale
               

                // --- 1. ENREGISTREMENT DU DÉBIT ---
                    MouvementComptable::create([
                        'compte_id'           => ($type === 'RETRAIT') ? $compte->id : null,                    
                        'date_mouvement'      => $dateBancaire,
                        'libelle_mouvement'   =>   " " . $type . " -client " . $compte->client->nom_complet,
                        'compte_debit_id'     => $schema['debit'],
                        'compte_credit_id'    => $schema['credit'], 
                        'montant_debit'       => $transaction->montant_brut,
                        'montant_credit'      => 0, // On force le crédit à 0 ici
                        'reference_operation' => $transaction->reference_unique,
                        'statut'              => 'COMPTABILISE',
                        'journal'             => 'CAISSE',
                        'agence_id'           => $agenceId, // Important pour tes filtres de balance !
                        'auteur_id'           => auth()->id(),
                    ]);

                    // --- 2. ENREGISTREMENT DU CRÉDIT ---
                    MouvementComptable::create([
                        'compte_id'   => ($type === 'VERSEMENT') ? $compte->id : null,
                        'date_mouvement'      => $dateBancaire,
                        'libelle_mouvement'   =>   " " . $type . " -client " . $compte->client->nom_complet,
                        'compte_debit_id'     => $schema['debit'],
                        'compte_credit_id'    => $schema['credit'],
                        'montant_debit'       => 0, // On force le débit à 0 ici
                        'montant_credit'      => $transaction->montant_brut,
                        'reference_operation' => $transaction->reference_unique,
                        'statut'              => 'COMPTABILISE',
                        'agence_id'           => $agenceId, // Important pour tes filtres de balance !

                        'journal'             => 'CAISSE',
                        'auteur_id'           => auth()->id(),
                    ]);

                // Écriture de commission si applicable (Lignes 5 et 6 de votre image)
                if ($schema['commission_account'] && $transaction->commissions > 0) {
                // Débit de la commission
                        MouvementComptable::create([
                            'compte_id'           => $compte->id,
                            'date_mouvement'      => $dateBancaire,
                            'libelle_mouvement'   => "COMMISSION " . $transaction->type_versement . " (D)",
                            'compte_debit_id'     => $schema['commission_account'],
                            'compte_credit_id'    => $schema['credit'],
                            'montant_debit'       => $transaction->commissions,
                            'montant_credit'      => 0,
                            'reference_operation' => $transaction->reference_unique,
                            'agence_id'           => $agenceId, // Important pour tes filtres de balance !

                            'journal'             => 'CAISSE',
                            'auteur_id'           => auth()->id(),
                        ]);

                        // Crédit de la commission
                        MouvementComptable::create([
                            'compte_id'           => $compte->id,
                            'date_mouvement'      => $dateBancaire,
                            'libelle_mouvement'   => "COMMISSION " . $transaction->type_versement . " (C)",
                            'compte_debit_id'     => $schema['commission_account'],
                            'compte_credit_id'    => $schema['credit'],
                            'montant_debit'       => 0,
                            'montant_credit'      => $transaction->commissions,
                            'reference_operation' => $transaction->reference_unique,
                            'journal'             => 'CAISSE',
                            'agence_id'           => $agenceId, // <--- AJOUT ICI
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

    /**
     * Valider la cohérence du billetage avec le montant attendu
     */
    private function validerBilletage(array $billetage, float $montantAttendu): void
    {
        try {
            // Calculer le total du billetage
            $totalBilletage = 0;
            $details = [];
            
            foreach ($billetage as $item) {
                $valeur = (int) ($item['valeur'] ?? 0);
                $quantite = (int) ($item['quantite'] ?? 0);
                
                // Validation des données de base
                if ($valeur <= 0) {
                    throw new Exception("Valeur de coupure invalide: {$valeur}");
                }
                
                if ($quantite < 0) {
                    throw new Exception("Quantité négative non autorisée: {$quantite}");
                }
                
                $sousTotal = $valeur * $quantite;
                $totalBilletage += $sousTotal;
                
                if ($quantite > 0) {
                    $details[] = [
                        'valeur' => $valeur,
                        'quantite' => $quantite,
                        'sous_total' => $sousTotal
                    ];
                }
            }
            
            // Vérifier si le billetage correspond au montant attendu
            // Tolérance de 1 FCFA pour les arrondis
            $difference = abs($totalBilletage - $montantAttendu);
            if ($difference > 1) {
                $formattedTotal = number_format($totalBilletage, 0, ',', ' ');
                $formattedExpected = number_format($montantAttendu, 0, ',', ' ');
                $formattedDiff = number_format($difference, 0, ',', ' ');
                
                throw new Exception(
                    "Désaccord billetage : Total billetage = {$formattedTotal} FCFA, " .
                    "Montant attendu = {$formattedExpected} FCFA, " .
                    "Différence = {$formattedDiff} FCFA"
                );
            }
            
            // Vérifier qu'il y a au moins une coupure
            if (empty($details)) {
                throw new Exception("Aucune coupure saisie dans le billetage");
            }
            
            // Log pour audit
            Log::info('Billetage validé', [
                'total_billetage' => $totalBilletage,
                'montant_attendu' => $montantAttendu,
                'difference' => $difference,
                'details' => $details
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur validation billetage', [
                'billetage' => $billetage,
                'montant_attendu' => $montantAttendu,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception("Erreur validation billetage: " . $e->getMessage());
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


    
private function enregistrerTiers($transactionId, $data)
{
    if (!isset($data['nom_complet'])) {
        return; // Pas de tiers fourni
    }

    TransactionTier::create([
        'transaction_id' => $transactionId,
        'nom_complet'    => $data['nom_complet'],
        'type_piece'     => $data['type_piece'] ?? null,
        'numero_piece'   => $data['numero_piece'] ?? null,
    ]);
}
    private function generateReference($type) {
        return substr($type, 0, 3) . '-' . date('YmdHis') . '-' . strtoupper(str()->random(4));
    }


    /**
 * Met l'opération en attente et génère une réponse pour le Front-end
*/
private function creerDemandeValidation($type, $data, $billetage, $user, $roleCible)
{
    // Sécurité : s'assurer que le montant est récupéré proprement
    $montant = data_get($data, 'montant_brut', 0);

    // 1. Détermination du rôle (Correction : on utilise l'argument passé ou on recalcule)
    if ($montant > 10000000) { $roleCible = 'DG'; }
    elseif ($montant > 5000000) { $roleCible = 'Chef Comptable'; }
    else { $roleCible = 'Assistant Comptable (AC)'; }

    // 2. Génération de la référence
    $ref = $data['reference_unique'] ?? $this->generateReference($type);
    $data['reference_unique'] = $ref;

    $payload = array_merge($data, ['billetage' => $billetage]);

    // 3. Création de la demande
    $demande = CaisseDemandeValidation::create([
        'type_operation'   => $type,
        'payload_data'     => $payload, // Laravel s'occupe du json_encode si le cast est mis dans le modèle
        'montant'          => $montant,
        'caissiere_id'     => data_get($user, 'id'),
        'role_destination' => $roleCible,
        'statut'           => 'EN_ATTENTE',
        'reference_unique' => $ref, // On stocke la référence unique pour le lien avec la transaction
    ]);

    // 4. Notification (C'est ici que ça plante souvent)
    // Remplacez votre bloc de notification par celui-ci, plus robuste :
try {
    $destinataires = User::whereHas('roles', function($q) use ($roleCible) {
        $q->where('name', $roleCible);
    })->get();
    
    if ($destinataires->isNotEmpty()) {
        // On passe un TABLEAU de données à la notification au lieu de l'objet seul
        // Cela évite que la notification ne cherche des propriétés inexistantes
        $donneesNotification = [
            'id'               => $demande->id,
            'montant'          => $montant,
            'reference_unique' => $ref, // La référence générée plus haut
            'caissiere_nom'    => $user->name
        ];
        
        Notification::send($destinataires, new RetraitDepassementPlafond($donneesNotification));
    }
} catch (\Exception $e) {
    \Log::error("Erreur Notification: " . $e->getMessage());
}
    // 5. RETOUR HARMONISÉ
    return [
        'id'                  => $demande->id,
        'requires_validation' => true,
        'role_destination'    => $roleCible,
        'reference_unique'    => $ref, // On renvoie la variable $ref directe
        'montant_brut'        => $montant,
        'message'             => "Validation requise par le " . $roleCible
    ];
}
/**
 * Vérifie si le code saisi par la caissière est valide et lié à une demande approuvée
 */
    private function verifierCodeApprouve($code, $caissiereId, $montant)
{
    // On cherche la demande qui correspond au code, à la caissière et au montant exact
    $demande = CaisseDemandeValidation::where('code_validation', $code)
        ->where('caissiere_id', $caissiereId)
        ->where('montant', $montant)
        ->where('statut', 'APPROUVE')
        ->first();

    if (!$demande) {
        // Log de sécurité pour tracer les tentatives de codes erronés
        \Log::warning("Tentative de validation échouée", [
            'caissiere_id' => $caissiereId,
            'montant' => $montant,
            'code_saisi' => $code
        ]);
        
        throw new Exception("Code de validation invalide, expiré ou l'opération n'a pas encore été approuvée par le responsable compétent.");
    }

    // Sécurité anti-rejeu : On marque immédiatement comme EXECUTE
    // On peut aussi enregistrer l'heure de l'exécution
    $demande->update([
        'statut' => 'EXECUTE',
        'executed_at' => now()
    ]);
}

public function genererRecu($transactionId)
{
    $transaction = CaisseTransaction::with(['compte.client', 'tier', 'demandeValidation.assistant'])
        ->findOrFail($transactionId);

    return view('recus.transaction', compact('transaction'));
}


public function obtenirJournalCaisseComplet($filtres)
{
    // 1. RÉCUPÉRER LE VRAI SOLDE D'OUVERTURE depuis la dernière session clôturée
    $derniereSessionFermee = DB::table('caisse_sessions')
        ->where('caisse_id', $filtres['caisse_id'])
        ->where('statut', 'FE') // Session fermée
       ->where('updated_at', '<', $filtres['date_debut'])   
            ->orderBy('heure_fermeture', 'desc')
        ->first();

    // Le solde d'ouverture = le solde de fermeture de la session précédente
    // Si c'est la première journée, pas de session précédente => solde_ouverture = 0
    $soldeOuverture = $derniereSessionFermee ? $derniereSessionFermee->solde_fermeture : 0;

    // 2. RÉCUPÉRATION DES MOUVEMENTS AVEC IDENTIFICATION DU FLUX
   $mouvementsRaw = DB::table('mouvements_comptables as mc')
    ->join('caisse_transactions as ct', 'mc.reference_operation', '=', 'ct.reference_unique')
    // On récupère les infos de la caisse via la session liée à la transaction
    ->join('caisse_sessions as cs', 'ct.session_id', '=', 'cs.id')
    ->join('caisses as ca', 'cs.caisse_id', '=', 'ca.id') 
    ->join('comptes as c', 'mc.compte_id', '=', 'c.id')
    ->leftJoin('clients as cl', 'c.client_id', '=', 'cl.id')
        ->leftJoin('clients_physiques as cp', 'cl.id', '=', 'cp.client_id')
        ->leftJoin('clients_morales as cm', 'cl.id', '=', 'cm.client_id')
    ->leftJoin('transaction_tiers as tt', 'ct.id', '=', 'tt.transaction_id') 
    ->select([
        'c.numero_compte',
        'tt.nom_complet as tiers_nom',
        'cp.nom_prenoms as nom_physique',   // Nom si personne physique
            'cm.raison_sociale as nom_morale', // Nom si entreprise/association 
        'mc.libelle_mouvement',
        'mc.reference_operation',
        'mc.montant_debit',
        'mc.montant_credit',
        'ct.type_versement',
        'ct.type_flux',
        'ca.code_caisse',
        'mc.date_mouvement'
    ])
    // On filtre sur l'ID technique de la caisse passée en paramètre
    ->where('ca.id', $filtres['caisse_id'])
    ->whereBetween('mc.date_mouvement', [$filtres['date_debut'], $filtres['date_fin']])
    ->orderBy('mc.date_mouvement', 'asc')
    ->get();

    $mouvementsTraites = $mouvementsRaw->map(function ($mvt) {
        if (!empty($mvt->tiers_nom)) {
            $mvt->tiers_final = $mvt->tiers_nom;
        } elseif (!empty($mvt->nom_physique)) {
            $mvt->tiers_final = $mvt->nom_physique;
        } elseif (!empty($mvt->nom_morale)) {
            $mvt->tiers_final = $mvt->nom_morale;
        } else {
            $mvt->tiers_final = "Client Interne";
        }
        return $mvt;
    });

    // 3. GROUPEMENT DES OPÉRATIONS
    $journalGroupe = [
        'VERSEMENTS' => $mouvementsTraites->where('type_flux', 'VERSEMENT')->values(),
        'RETRAITS'   => $mouvementsTraites->where('type_flux', 'RETRAIT')->values(),
    ];

    // 4. CALCUL DES TOTAUX
    $totalDebit = (float)$mouvementsRaw->sum('montant_debit');
    $totalCredit = (float)$mouvementsRaw->sum('montant_credit');
    $soldeCloture = $soldeOuverture + $totalDebit - $totalCredit;

    return [
        'solde_ouverture' => (float)$soldeOuverture,
        'journal_groupe'  => $journalGroupe,
        'total_debit'     => $totalDebit,
        'total_credit'    => $totalCredit,
        'solde_cloture'   => (float)$soldeCloture,
        'date_debut'      => $filtres['date_debut'],
        'date_fin'        => $filtres['date_fin']
    ];
}

/**
 * Initialise un retrait à distance (Saisie des informations et pièces jointes)
 */
public function initierRetraitDistance(array $data)
{
    return DB::transaction(function () use ($data) {
        $user = auth()->user();
        if (!$user) throw new Exception("Utilisateur non authentifié.");

        // 1. Gestion des fichiers
        $pathDemande = isset($data['pj_demande_retrait']) ? $data['pj_demande_retrait']->store('caisse/retraits_distance', 'public') : null;
        $pathProcuration = isset($data['pj_procuration']) ? $data['pj_procuration']->store('caisse/procurations', 'public') : null;
        $bordereau_retrait = isset($data['bordereau_retrait']) ? $data['bordereau_retrait']->store('caisse/bordereaux_retraits', 'public') : null;
        // 2. Récupération de la session avec chargement forcé des relations
        $session = CaisseSession::with(['caisse.guichet'])
            ->where('caissier_id', $user->id)
            ->where('statut', 'OU')
            ->first();
        $dateComptable = DB::table('jours_comptables')->where('statut', 'OUVERT')->value('date_du_jour');
        if (!$session) throw new Exception("Session de caisse fermée ou introuvable pour cet utilisateur.");

        // 3. Récupération sécurisée du code agence
        // On essaie d'abord l'agence du user, sinon celle du guichet, sinon celle de la caisse
        $agenceId = $user->agence_id 
                    ?? ($session->caisse->guichet->agence_id ?? $session->caisse->agence_id ?? null);

        if (!$agenceId) throw new Exception("Impossible de déterminer l'agence (ID null).");

        // 4. Récupération de la date
        $dateOp = DB::table('jours_comptables')->where('statut', 'OUVERT')->value('date_du_jour');
        if (!$dateOp) $dateOp = now()->toDateString(); // Backup si le jour comptable bugge

        // 5. Création de la transaction
 return CaisseTransaction::create([
    'reference_unique'    => $this->generateReference('RDIST'),
    'compte_id'           => $data['compte_id'],
    'session_id'          => $session->id,
    
    // Hiérarchie
    'code_agence'         => $agenceId, 
    'code_guichet'        => $session->caisse->guichet->code ?? $session->caisse->guichet_id,
    // APRÈS : 
   'code_caisse' => (string) ($session->caisse->code_caisse ?? $session->caisse->id),
    // Dates (LE FIX ICI)
    'date_operation'      => $dateOp,
    'date_valeur'         => $dateOp, // On utilise la même date pour la valeur

    // Flux et Montants
    'type_versement'      => 'ESPECE',
    'type_flux'           => 'RETRAIT',
    'montant_brut'        => $data['montant_brut'],
    'commissions'         => $data['commissions'] ?? 0,
    'taxes'               => $data['taxes'] ?? 0,
    
    // Autres infos
    'numero_bordereau'    => $data['numero_bordereau'],
    'type_bordereau'      => $data['type_bordereau'],
    'reference_externe'   => $data['reference_externe'] ?? null,
    'origine_fonds'       => $data['origine_fonds'] ?? null,
    'caissier_id'         => $user->id,
    'gestionnaire_id'     => $data['gestionnaire_id'],
    'statut'              => 'EN_ATTENTE',
    'statut_workflow'     => 'EN_ATTENTE_CA',
    'is_retrait_distance' => true,
    'pj_demande_retrait'  => $pathDemande,
    'pj_procuration'      => $pathProcuration,
    'bordereau_retrait'     => $bordereau_retrait ,

]);
$this->enregistrerTiers($transaction->id, $data);

    });
}
/**
 * Validation finale par le Chef d'Agence et déclenchement des écritures
 */
public function approuverChefAgence($transactionId)
{

/*if (!auth()->user()->hasRole('Chef d\'Agence')) { // Adaptez le nom du rôle
        throw new Exception("Accès refusé : Seul le Chef d'Agence peut approuver les retraits à distance.");
    }*/
    return DB::transaction(function () use ($transactionId) {
        $transaction = CaisseTransaction::findOrFail($transactionId);

     // Dans approuverChefAgence

        // Génération d'un code unique à 6 chiffres
        $codeValidation = rand(100000, 999999);

        $transaction->update([
            'statut_workflow'    => 'APPROUVE_CA', // Nouveau statut
            'code_validation'    => $codeValidation, // AJOUTER CETTE COLONNE EN BD
            'chef_agence_id'     => auth()->id(),
            'date_approbation_ca'=> now(),
        ]);

        // Optionnel : Envoyer le code par SMS au client ici
        
        return [
            'message' => 'Approuvé. Le code de validation est : ' . $codeValidation,
            'transaction' => $transaction,
            'code_validation' => $codeValidation // <-- C'EST CETTE LIGNE QUI MANQUE
        ];
    });
}

public function rejeterRetraitDistance($transactionId, $motif)
{
    $transaction = CaisseTransaction::findOrFail($transactionId);
    
    $transaction->update([
        'statut_workflow' => 'REJETE_CA',
        'chef_agence_id'  => auth()->id(),
        'motif_rejet_ca'  => $motif,
        'statut'          => 'ANNULE'
    ]);

    return $transaction;
    
}

public function confirmerRetraitCaissiere($transactionId, $codeSaisi)
{
    return DB::transaction(function () use ($transactionId, $codeSaisi) {
        $transaction = CaisseTransaction::with(['session.caisse', 'compte.typeCompte'])
            ->lockForUpdate()
            ->findOrFail($transactionId);

        // 1. Vérifications de sécurité
        if ($transaction->statut_workflow !== 'APPROUVE_CA') {
            throw new Exception("Ce retrait n'a pas encore été approuvé par le Chef d'Agence.");
        }

        if ($transaction->code_validation !== $codeSaisi) {
            throw new Exception("Code de validation incorrect.");
        }

        $compte = $transaction->compte;
        $session = $transaction->session;
        $dateBancaire = $transaction->date_operation;

        // 2. Contrôles métier (on revérifie au cas où le solde a changé entre temps)
        $this->verifierStatutCompte('RETRAIT', $compte);
        $this->validerEligibilite('RETRAIT', $compte, $transaction->montant_brut);

        // 3. MOUVEMENTS FINANCIERS (C'est ici que ça bouge !)
        $this->genererEcritureComptable('RETRAIT', $transaction, $compte, $dateBancaire, $session);
        $this->actualiserSoldeCompte('RETRAIT', $compte, $transaction->montant_brut);
        $this->actualiserSoldesFinanciers('RETRAIT', $session, $transaction->montant_brut);

        // 4. Clôture définitive
        $transaction->update([
            'statut'          => 'VALIDE',
            'statut_workflow' => 'TERMINE',
            'caissier_id'     => auth()->id(), // On enregistre qui a décaissé
            'date_operation'   => $dateBancaire, // On met à jour la date de la transaction pour le journal
            'date_decaissement'=> now(),
        ]);

        return $transaction;
    });
}
    //
    /**
 * Parcourt et prélève les frais impayés si le solde le permet
 */
private function apurerFraisEnAttente($compte, $dateBancaire)
{
    $dettes = FraisEnAttente::where('compte_id', $compte->id)
        ->where('statut', 'en_attente')
        ->orderBy('annee', 'asc')
        ->orderBy('mois', 'asc')
        ->get();

    foreach ($dettes as $dette) {
        if ($compte->solde >= $dette->montant) {
            // 1. Déduction du solde
            $compte->decrement('solde', $dette->montant);
            
            // 2. Marquage de la dette
            $dette->update(['statut' => 'recupere']);

            // Préparation des IDs de comptes
            $compteClient_id = $compte->typeCompte->chapitre_defaut_id;
            $compteProduit_id = DB::table('plan_comptable')->where('code', '72021001')->value('id');

            // 3. ÉCRITURE COMPTABLE - LIGNE DÉBIT (Le Client paie)
            MouvementComptable::create([
                'compte_id'           => $compte->id,
                'date_mouvement'      => $dateBancaire,
                'libelle_mouvement'   => "RECUP. FRAIS IMPAYES (D) - " . $dette->mois . "/" . $dette->annee,
                'compte_debit_id'     => $compteClient_id,
                'compte_credit_id'    => $compteProduit_id,
                'montant_debit'       => $dette->montant,
                'montant_credit'      => 0, // Zéro car c'est la ligne de débit
                'reference_operation' => 'RECUP-'.$dette->id,
                'journal'             => 'CAISSE',
                'auteur_id'           => auth()->id(),
                'statut'              => 'COMPTABILISE',
            ]);

            // 4. ÉCRITURE COMPTABLE - LIGNE CRÉDIT (Le Produit augmente)
            MouvementComptable::create([
                'compte_id'           => $compte->id,
                'date_mouvement'      => $dateBancaire,
                'libelle_mouvement'   => "RECUP. FRAIS IMPAYES (C) - " . $dette->mois . "/" . $dette->annee,
                'compte_debit_id'     => $compteClient_id,
                'compte_credit_id'    => $compteProduit_id,
                'montant_debit'       => 0, // Zéro car c'est la ligne de crédit
                'montant_credit'      => $dette->montant,
                'reference_operation' => 'RECUP-'.$dette->id,
                'journal'             => 'CAISSE',
                'auteur_id'           => auth()->id(),
                'statut'              => 'COMPTABILISE',
            ]);
        }
    }
}
/**
 * Recalcule la date d'échéance si le compte est de type bloqué
 */
private function gererRenouvellementBlocage($compte)
{
    if ($compte->duree_blocage_mois > 0) {
        $compte->update([
            'date_echeance' => now()->addMonths($compte->duree_blocage_mois)
        ]);
    }
}
}
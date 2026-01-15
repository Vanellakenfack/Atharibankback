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

        // --- DÉPLACEMENT : On récupère la session AVANT le contrôle du plafond ---
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
                // IMPORTANT : On retourne ici, donc le reste du code ne s'exécute pas
                return $this->creerDemandeValidation($type, $data, $billetage, $user);
            }
            // Si code présent, on vérifie
            $this->verifierCodeApprouve($data['code_validation'], $user->id, $montant);
        }

        // 1. Validation du Jour Comptable
        $jourComptable = DB::table('jours_comptables')->where('statut', 'OUVERT')->first();
        if (!$jourComptable) throw new Exception("Le jour comptable est fermé.");
        $dateBancaire = $jourComptable->date_du_jour;

           

            // 3. Gestion du Compte Client & Verrouillage
            $compte = null;
         if (isset($data['compte_id'])) {
            $compte = Compte::with('typeCompte') // IMPORTANT pour la scalabilité
                ->where('id', $data['compte_id'])
                ->lockForUpdate()
                ->first();
                
            $this->verifierStatutCompte($compte);
        }

            // 4. Contrôle de Provision (SPRV)
            $this->validerEligibilite($type, $compte, $data['montant_brut']);

            // 5. Enregistrement Transaction Caisse
            $transaction = $this->enregistrerTransactionCaisse($type, $data, $dateBancaire, $session);

            // 6. Enregistrement des informations du tiers (Identité)
           if (isset($data['tiers'])) {
                    // Cas Versement
                    $nom = $data['tiers']['nom_complet'];
                    $piece = $data['tiers']['numero_piece'];
                } else {
                    // Cas Retrait (porteur)
                    $nom = $data['porteur_nom'] ?? 'N/A';
                    $piece = $data['piece_identite_ref'] ?? 'N/A';
                }

                TransactionTier::create([
                    'transaction_id' => $transaction->id,
                    'nom_complet'    => $nom,
                    'type_piece'     => $data['tiers']['type_piece'] ?? 'CNI',
                    'numero_piece'   => $piece,
                ]);

            // 7. Enregistrement Billetage granulaire
            if ($typeVersement === 'ESPECE' && !empty($billetage)) {
            $this->enregistrerBilletage($transaction->id, $billetage);
        }
          // 8. Mouvement Comptable
            $this->genererEcritureComptable($type, $transaction, $compte, $dateBancaire, $session);

            // 9. Mise à jour du Solde Client
            $this->actualiserSoldeCompte($type, $compte, $data['net_a_percevoir_payer'] ?? $data['montant_brut']);

            // 10. Mise à jour des Soldes Caisse
            $this->actualiserSoldesFinanciers($type, $session, $data['montant_brut']);

            return $transaction;
        });
    }

        private function enregistrerTransactionCaisse($type, $data, $dateBancaire, $session) {
            $caisse = $session->caisse;
            $guichet = $caisse->guichet;
            $agence = $guichet->agence; 
            
            return CaisseTransaction::create([
                'reference_unique' => $this->generateReference($type),
                'compte_id'        => $data['compte_id'] ?? null,
                'code_agence'      => $agence->code ?? $guichet->agence_id,
                'code_guichet'     => $guichet->code_guichet ?? $guichet->id, 
                'code_caisse'      => $caisse->code_caisse ?? $caisse->id,
                'origine_fonds'    => $data['origine_fonds'] ?? null,
                'numero_bordereau' => $data['numero_bordereau'] ?? null,
                'type_bordereau'   => $data['type_bordereau'] ?? null,
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
    // 1. Récupération dynamique du compte de la caisse (depuis la nouvelle colonne)
    $pc_caisse_id = $session->caisse->compte_comptable_id;

    // 2. Récupération dynamique du compte client (via le type de compte)
    $pc_client_id = $compte->typeCompte->chapitre_defaut_id;

    if (!$pc_caisse_id) throw new Exception("Paramétrage manquant : Compte comptable de la CAISSE non défini.");
    if (!$pc_client_id) throw new Exception("Paramétrage manquant : Compte comptable du TYPE DE COMPTE non défini.");

        // 3. Détermination du sens selon le type d'opération
        if ($type === 'VERSEMENT') {
            $debitId  = $pc_caisse_id;  // L'argent entre en caisse
            $creditId = $pc_client_id;  // Augmentation de la dette client
        } else {
            $debitId  = $pc_client_id;  // Diminution de la dette client
            $creditId = $pc_caisse_id;  // L'argent sort de la caisse
        }

        $chapitre = $compte ? "CHAP " . ($compte->typeCompte->code_chapitre ?? 'CLI') : "INTERNE";
        $montantFinal = $transaction->montant_brut;

        return MouvementComptable::create([
            'compte_id'           => $compte->id,
            'date_mouvement'      => $dateBancaire,
            'date_valeur'         => $transaction->date_valeur,
        'libelle_mouvement'   => "[$chapitre] " . $type . " - " . ($transaction->reference_unique ?? $transaction['reference_unique']),
            // Utilisation des IDs récupérés dynamiquement
            'compte_debit_id'     => $debitId,
            'compte_credit_id'    => $creditId,
            'montant_debit'       => $montantFinal,
            'montant_credit'      => $montantFinal,
            
            'reference_operation' => $transaction->reference_unique,
            'statut'              => 'COMPTABILISE',  
            'journal'             => 'CAISSE',
            'auteur_id'           => auth()->id(),
        ]);
}
    private function getSchemaComptable($type, $data) {
        return match ($type) {
            'VERSEMENT'    => ['debit' => $data['pc_caisse_id'], 'credit' => $data['pc_client_id']],
            'RETRAIT'      => ['debit' => $data['pc_client_id'], 'credit' => $data['pc_caisse_id']],
            'ENTREE_CAISSE'=> ['debit' => $data['pc_caisse_id'], 'credit' => $data['pc_correspondant_id']],
            'SORTIE_CAISSE'=> ['debit' => $data['pc_correspondant_id'], 'credit' => $data['pc_caisse_id']],
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
}
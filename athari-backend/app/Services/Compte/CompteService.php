<?php

namespace App\Services\Compte;

use App\Models\Compte;
use App\Models\TypesCompte;
use App\Models\Agency;
use App\Models\client\Client;
use App\Models\User;
use App\Events\AccountCreated;
use App\Events\AccountValidated;
use App\Exceptions\AccountException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteService
{
    public function __construct(
        private AccountNumberGenerator $numberGenerator,
        private FeeService $feeService,
        private CommissionService $commissionService
    ) {}

    /**
     * Crée un nouveau compte bancaire
     */
    public function create(array $data, User $creator): Compte
    {
        return DB::transaction(function () use ($data, $creator) {
            $client = Client::findOrFail($data['client_id']);
            $accountType = TypesCompte::findOrFail($data['account_type_id']);
            $agency = Agency::findOrFail($data['agency_id']);

            // Génération du numéro de compte
            $numeroData = $this->numberGenerator->generate($client, $accountType, $agency);

            // Création du compte
            $account = Compte::create([
                'numero_compte' => substr($numeroData['numero_compte'], 0, 13),
                'cle_compte' => $numeroData['cle_compte'],
                'client_id' => $client->id,
                'account_type_id' => $accountType->id,
                'agency_id' => $agency->id,
                'created_by' => $creator->id,
                'numero_ordre' => $numeroData['numero_ordre'],
                'minimum_compte' => $accountType->minimum_compte,
                'statut' => 'en_cours',
                'statut_validation' => 'en_attente',
                'opposition_debit' => true, // Bloqué sur débit à l'ouverture
                'devise' => 'XAF',
                'taxable' => $data['taxable'] ?? false,
                'notes' => $data['notes'] ?? null,
            ]);

            // Calcul de la date d'échéance pour les comptes bloqués
            if ($accountType->est_bloque && $accountType->duree_blocage_mois) {
                $account->date_echeance = now()->addMonths($accountType->duree_blocage_mois);
                $account->save();
            }

            // Prélèvement automatique des frais d'ouverture
            $this->feeService->prelevesFraisOuverture($account, $accountType);

            Log::info('Compte créé', [
                'account_id' => $account->id,
                'numero' => $account->numero_compte_formate,
                'client' => $client->num_client,
                'type' => $accountType->name,
                'creator' => $creator->id,
            ]);

            event(new AccountCreated($account));

            return $account->fresh(['client', 'accountType', 'agency', 'creator']);
        });
    }

    /**
     * Met à jour un compte existant
     */
    public function update(Compte $account, array $data, User $updater): Compte
    {
        return DB::transaction(function () use ($account, $data, $updater) {
            $updatableFields = [
                'taxable',
                'notes',
                'minimum_compte',
            ];

            $account->update(array_intersect_key($data, array_flip($updatableFields)));

            Log::info('Compte mis à jour', [
                'account_id' => $account->id,
                'updater' => $updater->id,
                'changes' => $account->getChanges(),
            ]);

            return $account->fresh();
        });
    }

    /**
     * Validation par le Chef d'Agence
     */
    public function validateByChefAgence(Compte $account, User $validator): Compte
    {
        if ($account->statut_validation !== 'en_attente') {
            throw new AccountException("Le compte n'est pas en attente de validation.");
        }

        return DB::transaction(function () use ($account, $validator) {
            $account->update([
                'statut_validation' => 'valide_ca',
                'validated_by_ca' => $validator->id,
                'validated_at_ca' => now(),
            ]);

            Log::info('Compte validé par Chef d\'Agence', [
                'account_id' => $account->id,
                'validator' => $validator->id,
            ]);

            return $account->fresh();
        });
    }

    /**
     * Validation par l'Assistant Juridique (lève le blocage sur débit)
     */
    public function validateByAssistantJuridique(Compte $account, User $validator): Compte
    {
        if ($account->statut_validation !== 'valide_ca') {
            throw new AccountException("Le compte doit d'abord être validé par le Chef d'Agence.");
        }

        return DB::transaction(function () use ($account, $validator) {
            $account->update([
                'statut_validation' => 'valide_aj',
                'validated_by_aj' => $validator->id,
                'validated_at_aj' => now(),
                'statut' => 'actif',
                'opposition_debit' => false, // Levée du blocage
                'date_ouverture' => now(),
            ]);

            Log::info('Compte validé par Assistant Juridique et activé', [
                'account_id' => $account->id,
                'validator' => $validator->id,
            ]);

            event(new AccountValidated($account));

            return $account->fresh();
        });
    }

    /**
     * Rejet d'un compte
     */
    public function reject(Compte $account, User $rejector, string $motif): Compte
    {
        return DB::transaction(function () use ($account, $rejector, $motif) {
            $account->update([
                'statut_validation' => 'rejete',
                'statut' => 'suspendu',
                'motif_opposition' => $motif,
            ]);

            Log::warning('Compte rejeté', [
                'account_id' => $account->id,
                'rejector' => $rejector->id,
                'motif' => $motif,
            ]);

            return $account->fresh();
        });
    }

    /**
     * Mise en opposition d'un compte
     */
    public function mettreEnOpposition(Compte $account, string $type, string $motif, User $user): Compte
    {
        return DB::transaction(function () use ($account, $type, $motif, $user) {
            $updates = ['motif_opposition' => $motif];

            if ($type === 'debit' || $type === 'total') {
                $updates['opposition_debit'] = true;
            }
            if ($type === 'credit' || $type === 'total') {
                $updates['opposition_credit'] = true;
            }

            $account->update($updates);

            Log::warning('Compte mis en opposition', [
                'account_id' => $account->id,
                'type' => $type,
                'motif' => $motif,
                'user' => $user->id,
            ]);

            return $account->fresh();
        });
    }

    /**
     * Clôture d'un compte
     */
    public function cloturer(Compte $account, User $user, string $motif): Compte
    {
        if ($account->solde != 0) {
            throw new AccountException("Le solde du compte doit être à zéro pour clôturer.");
        }

        return DB::transaction(function () use ($account, $user, $motif) {
            $account->update([
                'statut' => 'cloture',
                'date_cloture' => now(),
                'notes' => ($account->notes ? $account->notes . "\n" : '') . "Clôturé le " . now()->format('d/m/Y') . ": " . $motif,
            ]);

            Log::info('Compte clôturé', [
                'account_id' => $account->id,
                'user' => $user->id,
                'motif' => $motif,
            ]);

            return $account->fresh();
        });
    }

    /**
     * Recherche de comptes avec filtres
     */
    public function search(array $filters, int $perPage = 15)
    {
        $query = Compte::with(['client', 'accountType', 'agency', 'creator']);

        if (!empty($filters['numero_compte'])) {
            $query->where('numero_compte', 'like', '%' . $filters['numero_compte'] . '%');
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (!empty($filters['account_type_id'])) {
            $query->where('account_type_id', $filters['account_type_id']);
        }

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['statut_validation'])) {
            $query->where('statut_validation', $filters['statut_validation']);
        }

        if (!empty($filters['category'])) {
            $query->whereHas('accountType', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $query->whereBetween('created_at', [$filters['date_debut'], $filters['date_fin']]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
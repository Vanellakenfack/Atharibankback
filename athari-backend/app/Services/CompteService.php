<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\DocumentsCompte;
use App\Models\TypesCompte;
use App\Models\Client;
use App\Models\Mandataire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CompteService
{
    public function createAccount(array $data, int $userId): Compte
    {
        return DB::transaction(function () use ($data, $userId) {
            $accountType = TypesCompte::findOrFail($data['account_type_id']);
            
            $account = Compte::create([
                'client_id' => $data['client_id'],
                'account_type_id' => $data['account_type_id'],
                'agency_id' => $data['agency_id'],
                'collector_id' => $data['collector_id'] ?? null,
                'created_by' => $userId,
                'status' => Compte::STATUS_PENDING,
                'debit_blocked' => true,
                'credit_blocked' => false,
                'notice_accepted' => $data['notice_accepted'] ?? false,
                'minimum_balance_amount' => $accountType->minimum_balance,
            ]);

            // Créer les mandataires
            if (!empty($data['mandataires'])) {
                foreach ($data['mandataires'] as $index => $mandataireData) {
                    $this->createMandataire($account, $mandataireData, $index + 1);
                }
            }

            // Gérer les documents
            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $documentData) {
                    $this->uploadDocument($account, $documentData, $userId);
                }
            }

            // Appliquer les frais d'ouverture automatiquement
            if ($accountType->opening_fee > 0) {
                $this->applyOpeningFee($account, $accountType->opening_fee);
            }

            return $account->fresh(['client', 'accountType', 'mandataires', 'documents']);
        });
    }

    protected function createMandataire(Compte $account, array $data, int $order): Mandataire
    {
        $signaturePath = null;
        if (!empty($data['signature'])) {
            $signaturePath = $this->saveSignature($data['signature'], $account->id, $order);
        }

        return Mandataire::create([
            'account_id' => $account->id,
            'order' => $order,
            'gender' => $data['gender'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'birth_date' => $data['birth_date'],
            'birth_place' => $data['birth_place'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'nationality' => $data['nationality'] ?? 'Camerounaise',
            'profession' => $data['profession'] ?? null,
            'mother_maiden_name' => $data['mother_maiden_name'] ?? null,
            'cni_number' => $data['cni_number'],
            'cni_issue_date' => $data['cni_issue_date'] ?? null,
            'cni_expiry_date' => $data['cni_expiry_date'] ?? null,
            'marital_status' => $data['marital_status'],
            'spouse_name' => $data['spouse_name'] ?? null,
            'spouse_birth_date' => $data['spouse_birth_date'] ?? null,
            'spouse_birth_place' => $data['spouse_birth_place'] ?? null,
            'spouse_cni' => $data['spouse_cni'] ?? null,
            'signature_path' => $signaturePath,
        ]);
    }

    protected function saveSignature(string $signatureData, int $accountId, int $order): string
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
        $fileName = "signatures/account_{$accountId}_mandataire_{$order}_" . time() . '.png';
        Storage::disk('private')->put($fileName, $imageData);
        return $fileName;
    }

    public function uploadDocument(Compte $account, array $data, int $userId): DocumentsCompte
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        
        $path = $file->store("accounts/{$account->id}/documents", 'private');
        
        return DocumentsCompte::create([
            'account_id' => $account->id,
            'document_type' => $data['document_type'],
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    protected function applyOpeningFee(Compte $account, float $fee): void
    {
        // Le compte sera débiteur des frais d'ouverture en attendant l'approvisionnement
        $account->updateBalance($fee, 'debit');
        
        // Ici on devrait aussi créer une écriture comptable vers le compte 721000001
        // Cette logique sera implémentée dans le TransactionService
    }

    public function validateByCA(Compte $account, int $userId, bool $approved, ?string $comments = null): Compte
    {
        return DB::transaction(function () use ($account, $userId, $approved, $comments) {
            if ($approved) {
                $account->update([
                    'validated_by_ca' => $userId,
                    'validated_at_ca' => now(),
                    'status' => Compte::STATUS_PENDING_VALIDATION,
                ]);
                
                // Générer la clé du compte après validation CA
                $account->generateFullAccountNumber();
            } else {
                $account->update([
                    'status' => Compte::STATUS_PENDING,
                    'blocking_reason' => $comments,
                ]);
            }

            return $account->fresh();
        });
    }

    public function validateByAJ(Compte $account, int $userId, bool $approved, ?string $comments = null): Compte
    {
        return DB::transaction(function () use ($account, $userId, $approved, $comments) {
            if (!$account->validated_by_ca) {
                throw new \Exception('Le compte doit d\'abord être validé par le Chef d\'Agence.');
            }

            if ($approved) {
                $account->update([
                    'validated_by_aj' => $userId,
                    'validated_at_aj' => now(),
                    'status' => Compte::STATUS_ACTIVE,
                    'debit_blocked' => false,
                    'opening_date' => now(),
                ]);
            } else {
                $account->update([
                    'status' => Compte::STATUS_PENDING_VALIDATION,
                    'blocking_reason' => $comments,
                ]);
            }

            return $account->fresh();
        });
    }

    public function closeAccount(Compte $account, int $userId, string $reason): Compte
    {
        return DB::transaction(function () use ($account, $userId, $reason) {
            if ($account->balance != 0) {
                throw new \Exception('Le solde du compte doit être à zéro pour la clôture.');
            }

            $account->update([
                'status' => Compte::STATUS_CLOSED,
                'closing_date' => now(),
                'blocking_reason' => $reason,
            ]);

            return $account->fresh();
        });
    }

    public function blockAccount(Compte $account, string $reason, ?string $endDate = null): Compte
    {
        return DB::transaction(function () use ($account, $reason, $endDate) {
            $account->update([
                'status' => Compte::STATUS_BLOCKED,
                'debit_blocked' => true,
                'credit_blocked' => true,
                'blocking_reason' => $reason,
                'blocking_end_date' => $endDate,
            ]);

            return $account->fresh();
        });
    }

    public function unblockAccount(Compte $account): Compte
    {
        return DB::transaction(function () use ($account) {
            $account->update([
                'status' => Compte::STATUS_ACTIVE,
                'debit_blocked' => false,
                'credit_blocked' => false,
                'blocking_reason' => null,
                'blocking_end_date' => null,
            ]);

            return $account->fresh();
        });
    }
}
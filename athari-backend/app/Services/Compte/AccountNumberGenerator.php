<?php

namespace App\Services\Compte;

use App\Models\Compte;
use App\Models\Agency;
use App\Models\client\Client;
use App\Models\TypesCompte;
use Illuminate\Support\Facades\DB;

class AccountNumberGenerator
{
    /**
     * Génère un numéro de compte unique selon la nomenclature
     * Format: [Code Agence 3 car][N° Client 6 car][Code Compte 2 car][N° Ordre 1 car][Clé 1 car]
     */
    public function generate(Client $client, TypesCompte $accountType, Agency $agency): array
    {
        return DB::transaction(function () use ($client, $accountType, $agency) {
            $codeAgence = str_pad($agency->code, 3, '0', STR_PAD_LEFT);
            $numeroClient = $this->extractNumeroClientFromNumClient($client->num_client);
            $codeCompte = str_pad($accountType->code, 2, '0', STR_PAD_LEFT);
            $numeroOrdre = $this->getNextNumeroOrdre($client->id, $accountType->id);
            
            $numeroSansCle = $codeAgence . $numeroClient . $codeCompte . $numeroOrdre;
            $cle = $this->calculerCle($numeroSansCle);
            
            return [
                'numero_compte' => $numeroSansCle . $cle,
                'cle_compte' => $cle,
                'numero_ordre' => $numeroOrdre,
            ];
        });
    }

    /**
     * Extrait les 6 derniers caractères du numéro client
     */
    private function extractNumeroClientFromNumClient(string $numClient): string
    {
        return substr($numClient, -6);
    }

    /**
     * Détermine le prochain numéro d'ordre pour un compte de même nature
     */
    private function getNextNumeroOrdre(int $clientId, int $accountTypeId): int
    {
        $maxOrdre = Compte::where('client_id', $clientId)
            ->where('account_type_id', $accountTypeId)
            ->max('numero_ordre');

        return ($maxOrdre ?? 0) + 1;
    }

    /**
     * Calcule la clé de contrôle du numéro de compte
     * Utilise l'algorithme de Luhn modifié pour générer une lettre
     */
    private function calculerCle(string $numero): string
    {
        $somme = 0;
        $longueur = strlen($numero);
        
        for ($i = 0; $i < $longueur; $i++) {
            $digit = intval($numero[$longueur - 1 - $i]);
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $somme += $digit;
        }
        
        $reste = $somme % 26;
        
        return chr(65 + $reste); // A = 65 en ASCII
    }

    /**
     * Valide un numéro de compte existant
     */
    public function valider(string $numeroComplet): bool
    {
        if (strlen($numeroComplet) !== 14) {
            return false;
        }

        $numero = substr($numeroComplet, 0, 13);
        $cleAttendue = substr($numeroComplet, -1);
        $cleCalculee = $this->calculerCle($numero);

        return $cleAttendue === $cleCalculee;
    }
}
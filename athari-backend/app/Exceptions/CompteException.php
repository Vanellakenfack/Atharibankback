<?php

namespace App\Exceptions;

use Exception;

class CompteException extends Exception
{
    public static function soldeInsuffisant(): self
    {
        return new self('Solde insuffisant pour effectuer cette opération.');
    }

    public static function compteInactif(): self
    {
        return new self('Ce compte n\'est pas actif.');
    }

    public static function oppositionDebit(): self
    {
        return new self('Ce compte est en opposition sur débit.');
    }

    public static function echeanceNonAtteinte(): self
    {
        return new self('L\'échéance de ce compte bloqué n\'est pas encore atteinte.');
    }
}
<?php
// app/Models/AccountCommission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompteCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'transaction_id',
        'type_commission',
        'montant',
        'base_calcul',
        'mois',
        'annee',
        'statut',
        'compte_produit',
        'compte_attente',
        'preleve_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'base_calcul' => 'decimal:2',
        'preleve_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionCompte::class, 'transaction_id');
    }

    public function isPreleve(): bool
    {
        return $this->statut === 'preleve';
    }

    public function isEnAttenteSolde(): bool
    {
        return $this->statut === 'en_attente_solde';
    }

    public function scopeNonPreleve($query)
    {
        return $query->whereIn('statut', ['en_attente', 'en_attente_solde']);
    }

    public function scopeByPeriode($query, int $mois, int $annee)
    {
        return $query->where('mois', $mois)->where('annee', $annee);
    }
}
<?php
// app/Models/AccountTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionCompte extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'account_id',
        'agency_id',
        'created_by',
        'validated_by',
        'type_transaction',
        'sens',
        'montant',
        'solde_avant',
        'solde_apres',
        'rubrique_mata',
        'libelle',
        'motif',
        'numero_bordereau',
        'numero_piece',
        'compte_comptable_debit',
        'compte_comptable_credit',
        'statut',
        'date_valeur',
        'validated_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'solde_avant' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'date_valeur' => 'date',
        'validated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isDebit(): bool
    {
        return $this->sens === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->sens === 'credit';
    }

    public function scopeValide($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeByPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('created_at', [$debut, $fin]);
    }
}
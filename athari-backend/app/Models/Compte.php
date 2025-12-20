<?php
// app/Models/Compte.php

namespace App\Models;

use App\Models\client\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_compte',
        'cle_compte',
        'client_id',
        'account_type_id',
        'agency_id',
        'created_by',
        'validated_by_ca',
        'validated_by_aj',
        'solde',
        'solde_disponible',
        'solde_bloque',
        'minimum_compte',
        'solde_business',
        'solde_sante',
        'solde_scolarite',
        'solde_fete',
        'solde_fournitures',
        'solde_immobilier',
        'statut',
        'statut_validation',
        'opposition_debit',
        'opposition_credit',
        'motif_opposition',
        'date_ouverture',
        'date_echeance',
        'date_cloture',
        'validated_at_ca',
        'validated_at_aj',
        'numero_ordre',
        'taxable',
        'devise',
        'notes',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'solde_disponible' => 'decimal:2',
        'solde_bloque' => 'decimal:2',
        'minimum_compte' => 'decimal:2',
        'solde_business' => 'decimal:2',
        'solde_sante' => 'decimal:2',
        'solde_scolarite' => 'decimal:2',
        'solde_fete' => 'decimal:2',
        'solde_fournitures' => 'decimal:2',
        'solde_immobilier' => 'decimal:2',
        'opposition_debit' => 'boolean',
        'opposition_credit' => 'boolean',
        'taxable' => 'boolean',
        'date_ouverture' => 'date',
        'date_echeance' => 'date',
        'date_cloture' => 'date',
        'validated_at_ca' => 'datetime',
        'validated_at_aj' => 'datetime',
    ];

    protected $appends = ['numero_compte_formate', 'solde_global_mata'];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(TypesCompte::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatorCa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_ca');
    }

    public function validatorAj(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_aj');
    }

    public function mandataries(): HasMany
    {
        return $this->hasMany(Mandataire::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentsCompte::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class)->orderBy('created_at', 'desc');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AccountCommission::class);
    }

    // Accessors
    public function getNumeroCompteFormateAttribute(): string
    {
        return $this->numero_compte . $this->cle_compte;
    }

    public function getSoldeGlobalMataAttribute(): float
    {
        if (!$this->accountType || !$this->accountType->isMataBoost()) {
            return 0;
        }

        return $this->solde_business + $this->solde_sante + $this->solde_scolarite 
            + $this->solde_fete + $this->solde_fournitures + $this->solde_immobilier;
    }

    // Business Methods
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }

    public function isFullyValidated(): bool
    {
        return $this->statut_validation === 'valide_aj';
    }

    public function canDebit(): bool
    {
        return !$this->opposition_debit && $this->isActive();
    }

    public function canCredit(): bool
    {
        return !$this->opposition_credit && $this->isActive();
    }

    public function getSoldeDisponibleReel(): float
    {
        return $this->solde - $this->minimum_compte - $this->solde_bloque;
    }

    public function isEcheanceAtteinte(): bool
    {
        if (!$this->date_echeance) {
            return true;
        }
        return now()->gte($this->date_echeance);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut_validation', 'en_attente');
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByType($query, int $accountTypeId)
    {
        return $query->where('account_type_id', $accountTypeId);
    }
}
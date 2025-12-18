<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypesCompte extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'accounting_chapter_id',
        'code',
        'name',
        'category',
        'sub_category',
        'opening_fee',
        'monthly_commission',
        'withdrawal_fee',
        'sms_fee',
        'minimum_balance',
        'unblocking_fee',
        'early_withdrawal_penalty_rate',
        'interest_rate',
        'blocking_duration_days',
        'is_remunerated',
        'requires_checkbook',
        'mata_boost_sections',
        'is_active',
    ];

    protected $casts = [
        'opening_fee' => 'decimal:2',
        'monthly_commission' => 'decimal:2',
        'withdrawal_fee' => 'decimal:2',
        'sms_fee' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'unblocking_fee' => 'decimal:2',
        'early_withdrawal_penalty_rate' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'is_remunerated' => 'boolean',
        'requires_checkbook' => 'boolean',
        'mata_boost_sections' => 'array',
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = [
        'courant' => 'Compte Courant',
        'epargne' => 'Compte Épargne',
        'mata_boost' => 'Compte MATA BOOST',
        'collecte' => 'Compte de Collecte',
        'dat' => 'Dépôt à Terme',
        'autres' => 'Autres',
    ];

    public const SUB_CATEGORIES = [
        'a_vue' => 'À Vue',
        'bloque' => 'Bloqué',
        'particulier' => 'Particulier',
        'entreprise' => 'Entreprise',
        'family' => 'Family',
        'classique' => 'Classique',
        'logement' => 'Logement',
        'participative' => 'Participative',
        'garantie' => 'Garantie',
    ];

    public const MATA_BOOST_SECTIONS = [
        'business' => 'BUSINESS',
        'sante' => 'SANTÉ',
        'scolarite' => 'SCOLARITÉ',
        'fete' => 'FÊTE',
        'fournitures' => 'FOURNITURES',
        'immobilier' => 'IMMOBILIER',
    ];

    public function accountingChapter(): BelongsTo
    {
        return $this->belongsTo(AccountingChapter::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function isMataBoost(): bool
    {
        return $this->category === 'mata_boost';
    }

    public function isBlocked(): bool
    {
        return $this->sub_category === 'bloque';
    }

    public function calculateMonthlyCommission(float $totalDeposits): float
    {
        if ($this->category === 'mata_boost' && $this->sub_category === 'a_vue') {
            return $totalDeposits >= 50000 ? 1000 : 300;
        }

        return $this->monthly_commission;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_number',
        'account_key',
        'full_account_number',
        'client_id',
        'account_type_id',
        'agency_id',
        'balance',
        'available_balance',
        'minimum_balance_amount',
        'overdraft_limit',
        'status',
        'debit_blocked',
        'credit_blocked',
        'opening_date',
        'closing_date',
        'blocking_end_date',
        'blocking_reason',
        'mata_boost_balances',
        'documents_complete',
        'notice_accepted',
        'collector_id',
        'created_by',
        'validated_by_ca',
        'validated_at_ca',
        'validated_by_aj',
        'validated_at_aj',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'minimum_balance_amount' => 'decimal:2',
        'overdraft_limit' => 'decimal:2',
        'debit_blocked' => 'boolean',
        'credit_blocked' => 'boolean',
        'documents_complete' => 'boolean',
        'notice_accepted' => 'boolean',
        'opening_date' => 'date',
        'closing_date' => 'date',
        'blocking_end_date' => 'date',
        'mata_boost_balances' => 'array',
        'validated_at_ca' => 'datetime',
        'validated_at_aj' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_VALIDATION = 'pending_validation';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_DORMANT = 'dormant';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PENDING_VALIDATION => 'En cours de validation',
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_BLOCKED => 'Bloqué',
        self::STATUS_CLOSED => 'Clôturé',
        self::STATUS_DORMANT => 'Dormant',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (empty($account->account_number)) {
                $account->account_number = self::generateAccountNumber($account->client_id);
            }
            
            if ($account->accountType && $account->accountType->isMataBoost()) {
                $account->mata_boost_balances = [
                    'business' => 0,
                    'sante' => 0,
                    'scolarite' => 0,
                    'fete' => 0,
                    'fournitures' => 0,
                    'immobilier' => 0,
                ];
            }
        });
    }

    public static function generateAccountNumber(int $clientId): string
    {
        $client = Client::findOrFail($clientId);
        
        $accountCount = self::where('client_id', $clientId)->withTrashed()->count();
        $suffix = str_pad($accountCount + 1, 4, '0', STR_PAD_LEFT);
        
        return $client->client_number . $suffix;
    }

    public static function generateAccountKey(string $accountNumber): string
    {
        $sum = 0;
        $weight = [7, 3, 1];
        
        for ($i = 0; $i < strlen($accountNumber); $i++) {
            $sum += intval($accountNumber[$i]) * $weight[$i % 3];
        }
        
        $key = 97 - ($sum % 97);
        return str_pad($key, 2, '0', STR_PAD_LEFT);
    }

    public function generateFullAccountNumber(): void
    {
        $this->account_key = self::generateAccountKey($this->account_number);
        $this->full_account_number = $this->account_number . $this->account_key;
        $this->save();
    }

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

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedByCA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_ca');
    }

    public function validatedByAJ(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_aj');
    }

    public function mandataires(): HasMany
    {
        return $this->hasMany(Mandataire::class)->orderBy('order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentsCompte::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PENDING_VALIDATION]);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PENDING_VALIDATION]);
    }

    public function canDebit(float $amount): bool
    {
        if ($this->debit_blocked) {
            return false;
        }

        $availableForDebit = $this->available_balance + $this->overdraft_limit;
        return $amount <= $availableForDebit;
    }

    public function canCredit(): bool
    {
        return !$this->credit_blocked && $this->isActive();
    }

    public function updateBalance(float $amount, string $type = 'credit'): void
    {
        DB::transaction(function () use ($amount, $type) {
            if ($type === 'credit') {
                $this->balance += $amount;
                $this->available_balance += $amount;
            } else {
                $this->balance -= $amount;
                $this->available_balance -= $amount;
            }
            $this->save();
        });
    }

    public function updateMataBoostSection(string $section, float $amount, string $type = 'credit'): void
    {
        if (!$this->accountType->isMataBoost()) {
            throw new \Exception('Ce compte n\'est pas un compte MATA BOOST');
        }

        $balances = $this->mata_boost_balances ?? [];
        
        if (!isset($balances[$section])) {
            throw new \Exception("Section MATA BOOST invalide: {$section}");
        }

        if ($type === 'credit') {
            $balances[$section] += $amount;
        } else {
            $balances[$section] -= $amount;
        }

        $this->mata_boost_balances = $balances;
        $this->balance = array_sum($balances);
        $this->available_balance = $this->balance;
        $this->save();
    }
}
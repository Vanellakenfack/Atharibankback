<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mandataire extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'order',
        'gender',
        'last_name',
        'first_name',
        'birth_date',
        'birth_place',
        'phone',
        'address',
        'nationality',
        'profession',
        'mother_maiden_name',
        'cni_number',
        'cni_issue_date',
        'cni_expiry_date',
        'marital_status',
        'spouse_name',
        'spouse_birth_date',
        'spouse_birth_place',
        'spouse_cni',
        'signature_path',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'cni_issue_date' => 'date',
        'cni_expiry_date' => 'date',
        'spouse_birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    //Obtien le Nom complet du mandataire
    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }
}
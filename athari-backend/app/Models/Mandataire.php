<?php
// app/Models/AccountMandatary.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mandataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type_mandataire',
        'sexe',
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
        'telephone',
        'adresse',
        'nationalite',
        'profession',
        'nom_jeune_fille_mere',
        'numero_cni',
        'cni_delivrance',
        'cni_expiration',
        'situation_familiale',
        'nom_conjoint',
        'date_naissance_conjoint',
        'lieu_naissance_conjoint',
        'cni_conjoint',
        'signature_path',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'cni_delivrance' => 'date',
        'cni_expiration' => 'date',
        'date_naissance_conjoint' => 'date',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' ' . $this->prenoms;
    }

    public function isCniValide(): bool
    {
        if (!$this->cni_expiration) {
            return true;
        }
        return now()->lt($this->cni_expiration);
    }
}
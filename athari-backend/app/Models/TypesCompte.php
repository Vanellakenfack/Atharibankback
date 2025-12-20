<?php
// app/Models/AccountType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypesCompte extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'category',
        'sub_category',
        'frais_ouverture',
        'frais_tenue_compte',
        'frais_carnet',
        'frais_retrait',
        'frais_sms',
        'frais_deblocage',
        'penalite_retrait_anticipe',
        'commission_mensuelle_seuil',
        'commission_mensuelle_basse',
        'commission_mensuelle_haute',
        'minimum_compte',
        'remunere',
        'taux_interet_annuel',
        'est_bloque',
        'duree_blocage_mois',
        'autorise_decouvert',
        'periodicite_arrete',
        'periodicite_extrait',
        'is_active',
    ];

    protected $casts = [
        'frais_ouverture' => 'decimal:2',
        'frais_tenue_compte' => 'decimal:2',
        'frais_carnet' => 'decimal:2',
        'frais_retrait' => 'decimal:2',
        'frais_sms' => 'decimal:2',
        'frais_deblocage' => 'decimal:2',
        'penalite_retrait_anticipe' => 'decimal:2',
        'commission_mensuelle_seuil' => 'decimal:2',
        'commission_mensuelle_basse' => 'decimal:2',
        'commission_mensuelle_haute' => 'decimal:2',
        'minimum_compte' => 'decimal:2',
        'taux_interet_annuel' => 'decimal:4',
        'remunere' => 'boolean',
        'est_bloque' => 'boolean',
        'autorise_decouvert' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Compte::class);
    }

    public function isMataBoost(): bool
    {
        return $this->category === 'mata_boost';
    }

    public function isBloque(): bool
    {
        return $this->est_bloque;
    }

    public function getCommissionMensuelle(float $totalVersements): float
    {
        if ($this->commission_mensuelle_seuil === null) {
            return $this->commission_mensuelle_basse;
        }

        return $totalVersements >= $this->commission_mensuelle_seuil
            ? $this->commission_mensuelle_haute
            : $this->commission_mensuelle_basse;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
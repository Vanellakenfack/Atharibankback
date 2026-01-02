<?php

namespace App\Models\frais;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraisApplication extends Model
{
    use HasFactory;

    protected $table = 'frais_applications';
    
    public $timestamps = false;

    protected $fillable = [
        'compte_id',
        'type_compte_id',
        'operation_id',
        'type_frais',
        'montant_base',
        'taux_applique',
        'montant_frais',
        'chapitre_debit_id',
        'chapitre_credit_id',
        'numero_piece',
        'statut',
        'date_application',
        'date_valeur',
        'periode_reference',
        'details',
        'applique_par',
    ];

    protected $casts = [
        'date_application' => 'datetime',
        'date_valeur' => 'datetime',
        'details' => 'array',
        'montant_base' => 'decimal:2',
        'taux_applique' => 'decimal:4', // Augmenté pour taux d'intérêt
        'montant_frais' => 'decimal:2',
    ];

    // ========== RELATIONS ==========
    
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function typeCompte(): BelongsTo
    {
        return $this->belongsTo(TypeCompte::class);
    }

    public function chapitreDebit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_debit_id');
    }

    public function chapitreCredit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_credit_id');
    }

    public function appliquePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applique_par');
    }

    // ========== SCOPES ==========
    
    public function scopeApplique($query)
    {
        return $query->where('statut', 'APPLIQUE');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'EN_ATTENTE');
    }

    public function scopeAnnule($query)
    {
        return $query->where('statut', 'ANNULE');
    }

    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_application', [$dateDebut, $dateFin]);
    }

    public function scopeParType($query, $typeFrais)
    {
        return $query->where('type_frais', $typeFrais);
    }

    public function scopeMois($query, $annee, $mois)
    {
        return $query->whereYear('date_application', $annee)
                    ->whereMonth('date_application', $mois);
    }

    // ========== MÉTHODES ==========
    
    /**
     * Appliquer le frais
     */
    public function appliquer(): bool
    {
        if ($this->statut !== 'EN_ATTENTE') {
            return false;
        }

        $this->statut = 'APPLIQUE';
        $this->date_application = now();
        return $this->save();
    }

    /**
     * Annuler le frais
     */
    public function annuler(): bool
    {
        if ($this->statut === 'ANNULE') {
            return false;
        }

        $this->statut = 'ANNULE';
        return $this->save();
    }

    /**
     * Générer un numéro de pièce
     */
    public static function genererNumeroPiece(string $typeFrais, $date = null): string
    {
        $date = $date ?? now();
        
        $prefix = match($typeFrais) {
            'OUVERTURE' => 'OUV',
            'COMMISSION_MENSUELLE' => 'COM',
            'COMMISSION_RETRAIT' => 'RET',
            'COMMISSION_SMS' => 'SMS',
            'INTERET_CREDITEUR' => 'INT',
            'PENALITE_RETRAIT' => 'PEN',
            'CARNET' => 'CAR',
            'RENOUVELLEMENT' => 'REN',
            'PERTE_CARNET' => 'PER',
            'FRAIS_DEBLOCAGE' => 'DEB',
            'CLOTURE_ANTICIPE' => 'CLO',
            'MINIMUM_COMPTE' => 'MIN',
            default => 'FRA',
        };

        $dateStr = $date->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$dateStr}-{$random}";
    }

    /**
     * Obtenir le libellé du type de frais
     */
    public function getLibelleTypeFrais(): string
    {
        return match($this->type_frais) {
            'OUVERTURE' => 'Frais d\'ouverture',
            'CARNET' => 'Frais de carnet',
            'RENOUVELLEMENT' => 'Renouvellement carnet',
            'PERTE_CARNET' => 'Remplacement carnet perdu',
            'COMMISSION_MENSUELLE' => 'Commission mensuelle',
            'COMMISSION_RETRAIT' => 'Commission de retrait',
            'COMMISSION_SMS' => 'Commission SMS',
            'INTERET_CREDITEUR' => 'Intérêts créditeurs',
            'FRAIS_DEBLOCAGE' => 'Frais de déblocage',
            'PENALITE_RETRAIT' => 'Pénalité retrait anticipé',
            'CLOTURE_ANTICIPE' => 'Frais clôture anticipée',
            'MINIMUM_COMPTE' => 'Minimum en compte',
            default => 'Frais divers',
        };
    }

    /**
     * Vérifier si le frais est annulable
     */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, ['EN_ATTENTE', 'APPLIQUE']);
    }

    // ========== MÉTHODES STATIQUES - CRÉATION ==========
    
    /**
     * Créer une application de frais d'ouverture
     */
    public static function creerFraisOuverture(
        Compte $compte,
        TypeCompte $typeCompte,
        $appliquePar = null
    ): self {
        return self::create([
            'compte_id' => $compte->id,
            'type_compte_id' => $typeCompte->id,
            'type_frais' => 'OUVERTURE',
            'montant_base' => $typeCompte->frais_ouverture,
            'montant_frais' => $typeCompte->frais_ouverture,
            'chapitre_debit_id' => $compte->plan_comptable_id,
            'chapitre_credit_id' => $typeCompte->chapitre_frais_ouverture_id,
            'numero_piece' => self::genererNumeroPiece('OUVERTURE', now()),
            'statut' => 'APPLIQUE',
            'date_application' => now(),
            'date_valeur' => now(),
            'applique_par' => $appliquePar,
            'details' => [
                'type_compte' => $typeCompte->libelle,
                'compte_numero' => $compte->numero_compte,
                'automatique' => true,
            ],
        ]);
    }

    /**
     * Créer une commission mensuelle
     */
    public static function creerCommissionMensuelle(
        Compte $compte,
        TypeCompte $typeCompte,
        float $montantCommission,
        float $totalVersements,
        string $periode,
        $appliquePar = null
    ): self {
        return self::create([
            'compte_id' => $compte->id,
            'type_compte_id' => $typeCompte->id,
            'type_frais' => 'COMMISSION_MENSUELLE',
            'montant_base' => $totalVersements,
            'taux_applique' => $montantCommission / max($totalVersements, 1),
            'montant_frais' => $montantCommission,
            'chapitre_debit_id' => $compte->plan_comptable_id,
            'chapitre_credit_id' => $typeCompte->chapitre_commission_mensuelle_id,
            'numero_piece' => self::genererNumeroPiece('COMMISSION_MENSUELLE', now()),
            'statut' => 'APPLIQUE',
            'date_application' => now(),
            'date_valeur' => now(),
            'periode_reference' => $periode,
            'applique_par' => $appliquePar,
            'details' => [
                'total_versements' => $totalVersements,
                'seuil' => $typeCompte->seuil_commission,
                'taux_commission_superieur' => $typeCompte->commission_si_superieur,
                'taux_commission_inferieur' => $typeCompte->commission_si_inferieur,
                'compte_numero' => $compte->numero_compte,
            ],
        ]);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class OperationDiverse extends Model
{
    use SoftDeletes;

    protected $table = 'operation_diverses';
    
    protected $fillable = [
        'numero_od',
        'agence_id',
        'date_operation',
        'date_valeur',
        'type_operation',
        'libelle',
        'description',
        'montant',
        'devise',
        'compte_debit_id',
        'compte_credit_id',
        'compte_client_debiteur_id',
        'compte_client_crediteur_id',
        'statut',
        'est_comptabilise',
        'numero_piece',
        'saisi_par',
        'valide_par',
        'comptabilise_par',
        'justificatif_type',
        'justificatif_numero',
        'justificatif_date',
        'justificatif_path',
        'reference_client',
        'nom_tiers',
        'est_urgence',
        'motif_rejet',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'est_comptabilise' => 'boolean',
        'est_urgence' => 'boolean',
        'date_operation' => 'date',
        'date_valeur' => 'date',
        'justificatif_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Générer automatiquement le numéro OD
        static::creating(function ($model) {
            if (empty($model->numero_od)) {
                $model->numero_od = self::generateNumeroOd();
            }
        });
        
        // Historique des modifications
        static::updated(function ($model) {
            $model->enregistrerHistorique('MODIFICATION');
        });
    }

    /**
     * Génère un numéro d'OD unique
     */
    public static function generateNumeroOd(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastOd = self::where('numero_od', 'like', "OD-{$year}-{$month}-%")
            ->withTrashed()
            ->orderBy('numero_od', 'desc')
            ->first();
        
        if ($lastOd) {
            $lastNumber = (int) substr($lastOd->numero_od, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return "OD-{$year}-{$month}-{$nextNumber}";
    }

    /**
     * Relation avec l'agence
     */
    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agence_id');
    }

    /**
     * Relation avec le compte débité (plan comptable)
     */
    public function compteDebit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_debit_id');
    }

    /**
     * Relation avec le compte crédité (plan comptable)
     */
    public function compteCredit(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_credit_id');
    }

    /**
     * Relation avec le compte client débiteur (si virement)
     */
    public function compteClientDebiteur(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_client_debiteur_id');
    }

    /**
     * Relation avec le compte client créditeur (si virement)
     */
    public function compteClientCrediteur(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_client_crediteur_id');
    }

    /**
     * Relation avec la personne qui a saisi
     */
    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }

    /**
     * Relation avec la personne qui a validé
     */
    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Relation avec la personne qui a comptabilisé
     */
    public function comptabilisePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comptabilise_par');
    }

    /**
     * Relation avec l'historique
     */
    public function historique(): HasMany
    {
        return $this->hasMany(OdHistorique::class);
    }

    /**
     * Relation avec les signatures
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(OdSignature::class);
    }

    /**
     * Vérifie si l'OD peut être validée
     */
    public function peutEtreValidee(): bool
    {
        return in_array($this->statut, ['BROUILLON', 'SAISI']) 
            && !empty($this->justificatif_path)
            && $this->montant > 0
            && $this->compte_debit_id
            && $this->compte_credit_id;
    }

    /**
     * Valider l'OD
     */
    public function valider(User $validateur, string $commentaire = null): bool
    {
        if (!$this->peutEtreValidee()) {
            return false;
        }

        DB::transaction(function () use ($validateur, $commentaire) {
            $ancienStatut = $this->statut;
            
            $this->update([
                'statut' => 'VALIDE',
                'valide_par' => $validateur->id,
                'date_validation' => now(),
            ]);

            // Enregistrer l'historique
            $this->enregistrerHistorique('VALIDATION', $ancienStatut, 'VALIDE');

            // Enregistrer la signature si nécessaire
            $this->signatures()->create([
                'user_id' => $validateur->id,
                'niveau_validation' => 1,
                'role_validation' => $validateur->roles->first()->name ?? 'Validateur',
                'decision' => 'APPROUVE',
                'commentaire' => $commentaire,
                'signature_date' => now(),
            ]);
        });

        return true;
    }

    /**
     * Rejeter l'OD
     */
    public function rejeter(User $rejeteur, string $motif): bool
    {
        if (!in_array($this->statut, ['BROUILLON', 'SAISI'])) {
            return false;
        }

        DB::transaction(function () use ($rejeteur, $motif) {
            $ancienStatut = $this->statut;
            
            $this->update([
                'statut' => 'REJETE',
                'motif_rejet' => $motif,
            ]);

            $this->enregistrerHistorique('REJET', $ancienStatut, 'REJETE', $rejeteur);
        });

        return true;
    }

    /**
     * Comptabiliser l'OD (créer le mouvement comptable)
     */
    public function comptabiliser(User $comptable): bool
    {
        if ($this->statut !== 'VALIDE' || $this->est_comptabilise) {
            return false;
        }

        DB::transaction(function () use ($comptable) {
            // Créer le mouvement comptable
            MouvementComptable::create([
                'compte_id' => null,
                'date_mouvement' => $this->date_operation,
                'date_valeur' => $this->date_valeur ?? $this->date_operation,
                'libelle_mouvement' => "OD {$this->numero_od}: {$this->libelle}",
                'description' => $this->description,
                'compte_debit_id' => $this->compte_debit_id,
                'compte_credit_id' => $this->compte_credit_id,
                'montant_debit' => $this->montant,
                'montant_credit' => $this->montant,
                'journal' => 'BANQUE',
                'numero_piece' => $this->numero_piece ?? $this->numero_od,
                'reference_operation' => $this->numero_od,
                'statut' => 'COMPTABILISE',
                'est_pointage' => false,
            ]);

            // Mettre à jour l'OD
            $ancienStatut = $this->statut;
            $this->update([
                'est_comptabilise' => true,
                'comptabilise_par' => $comptable->id,
                'date_comptabilisation' => now(),
            ]);

            $this->enregistrerHistorique('COMPTABILISATION', $ancienStatut, 'VALIDE', $comptable);
        });

        return true;
    }

    /**
     * Enregistrer une entrée dans l'historique
     */
    public function enregistrerHistorique(
        string $action, 
        ?string $ancienStatut = null, 
        ?string $nouveauStatut = null,
        ?User $user = null
    ): void {
        $user = $user ?? auth()->user();
        
        $this->historique()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'description' => $this->getDescriptionHistorique($action),
            'donnees_modifiees' => $this->getDonneesModifiees(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Obtenir la description pour l'historique
     */
    private function getDescriptionHistorique(string $action): string
    {
        switch ($action) {
            case 'CREATION':
                return "Création de l'OD {$this->numero_od}";
            case 'VALIDATION':
                return "Validation de l'OD {$this->numero_od}";
            case 'COMPTABILISATION':
                return "Comptabilisation de l'OD {$this->numero_od}";
            case 'REJET':
                return "Rejet de l'OD {$this->numero_od}";
            case 'ANNULATION':
                return "Annulation de l'OD {$this->numero_od}";
            default:
                return "Modification de l'OD {$this->numero_od}";
        }
    }

    /**
     * Obtenir les données modifiées pour l'historique
     */
    private function getDonneesModifiees(): ?array
    {
        $modified = $this->getDirty();
        
        if (empty($modified)) {
            return null;
        }

        $donnees = [];
        foreach ($modified as $key => $newValue) {
            $oldValue = $this->getOriginal($key);
            $donnees[$key] = [
                'ancien' => $oldValue,
                'nouveau' => $newValue,
            ];
        }

        return $donnees;
    }

    /**
     * Scope pour les OD en attente de validation
     */
    public function scopeEnAttenteValidation($query)
    {
        return $query->whereIn('statut', ['BROUILLON', 'SAISI']);
    }

    /**
     * Scope pour les OD validées
     */
    public function scopeValidees($query)
    {
        return $query->where('statut', 'VALIDE');
    }

    /**
     * Scope pour les OD comptabilisées
     */
    public function scopeComptabilisees($query)
    {
        return $query->where('est_comptabilise', true);
    }

    /**
     * Scope par agence
     */
    public function scopeParAgence($query, $agenceId)
    {
        return $query->where('agence_id', $agenceId);
    }

    /**
     * Scope par période
     */
    public function scopePeriode($query, $dateDebut, $dateFin = null)
    {
        $dateFin = $dateFin ?? $dateDebut;
        return $query->whereBetween('date_operation', [$dateDebut, $dateFin]);
    }
}
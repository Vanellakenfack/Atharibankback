<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\Compte\MouvementComptable;
use App\Models\Agency;
use App\Models\User;
use App\Models\compte\Compte;
use Carbon\Carbon;
use App\Models\Concerns\UsesDateComptable;
class OperationDiverse extends Model
{
    use SoftDeletes, UsesDateComptable;

    protected $table = 'operation_diverses';
    
    protected $fillable = [
        'numero_od',
        'agence_id',
        'date_operation',
        'date_valeur',
        'date_comptable',
        'type_operation',
        'type_collecte',
        'code_operation',
        'libelle',
        'description',
        'montant',
        'montant_total',
        'devise',
        'compte_debit_id',
        'compte_credit_id',
        'compte_debit_principal_id',
        'compte_credit_principal_id',
        'comptes_debits_json',
        'comptes_credits_json',
        'sens_operation',
        'compte_client_debiteur_id',
        'compte_client_crediteur_id',
        'statut',
        'est_comptabilise',
        'est_collecte',
        'est_bloque',
        'est_urgence',
        'numero_piece',
        'numero_bordereau',
        'numero_guichet',
        'ref_lettrage',
        'modele_id',
        'saisi_par',
        'valide_par',
        'comptabilise_par',
        'justificatif_type',
        'justificatif_numero',
        'justificatif_date',
        'justificatif_path',
        'reference_client',
        'nom_tiers',
        'motif_rejet',
        'comptes_clients_json',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'est_comptabilise' => 'boolean',
        'est_collecte' => 'boolean',
        'est_bloque' => 'boolean',
        'est_urgence' => 'boolean',
        'date_operation' => 'date',
        'date_valeur' => 'date',
        'date_comptable' => 'date',
        'justificatif_date' => 'date',
        'comptes_debits_json' => 'array',
        'comptes_credits_json' => 'array',
        'comptes_clients_json' => 'array',
    ];

    // Constantes pour les types d'opération
    const TYPE_VIREMENT = 'VIREMENT';
    const TYPE_FRAIS = 'FRAIS';
    const TYPE_COMMISSION = 'COMMISSION';
    const TYPE_REGULARISATION = 'REGULARISATION';
    const TYPE_AUTRE = 'AUTRE';

    // Constantes pour les types de collecte
    const TYPE_MATA_BOOST = 'MATA_BOOST';
    const TYPE_EPARGNE_JOURNALIERE = 'EPARGNE_JOURNALIERE';
    const TYPE_CHARGE = 'CHARGE';

    // Constantes pour les codes opération
    const CODE_MATA_BOOST_JOURNALIER = 'MATA_BOOST_JOURNALIER';
    const CODE_MATA_BOOST_BLOQUE = 'MATA_BOOST_BLOQUE';
    const CODE_EPARGNE_JOURNALIERE = 'EPARGNE_JOURNALIERE';
    const CODE_CHARGE = 'CHARGE';
    const CODE_GENERIQUE = 'GENERIQUE';

    // Constantes pour les statuts
    const STATUT_BROUILLON = 'BROUILLON';
    const STATUT_SAISI = 'SAISI';
    const STATUT_VALIDE_AGENCE = 'VALIDE_AGENCE';
    const STATUT_VALIDE_COMPTABLE = 'VALIDE_COMPTABLE';
    const STATUT_VALIDE_DG = 'VALIDE_DG';
    const STATUT_VALIDE = 'VALIDE';
    const STATUT_ANNULE = 'ANNULE';
    const STATUT_REJETE = 'REJETE';

    // Constantes pour les sens d'opération
    const SENS_DEBIT = 'DEBIT';
    const SENS_CREDIT = 'CREDIT';

    // Constantes pour les types de justificatif
    const JUSTIF_FACTURE = 'FACTURE';
    const JUSTIF_QUITTANCE = 'QUITTANCE';
    const JUSTIF_BON = 'BON';
    const JUSTIF_TICKET = 'TICKET';
    const JUSTIF_AUTRE_VIREMENT = 'AUTRE_VIREMENT';
    const JUSTIF_NOTE_CORRECTION = 'NOTE_CORRECTION';
    const JUSTIF_AUTRE = 'AUTRE';

    // Constantes pour les niveaux de workflow
    const NIVEAU_AGENCE = 1;
    const NIVEAU_COMPTABLE = 2;
    const NIVEAU_DG = 3;

    protected static function boot()
    {
        parent::boot();
        
        // Générer automatiquement le numéro OD
        static::creating(function ($model) {
            if (empty($model->numero_od)) {
                $model->numero_od = self::generateNumeroOd();
            }
            
            // Générer le numéro de pièce selon la nomenclature AAMMJJ-numéro
            if (empty($model->numero_piece)) {
                $model->numero_piece = self::generateNumeroPiece();
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
     * Génère un numéro de pièce selon la nomenclature AAMMJJ-numéro
     */
    public static function generateNumeroPiece(): string
    {
        $today = date('Ymd');
        
        $lastPiece = self::where('numero_piece', 'like', "{$today}-%")
            ->withTrashed()
            ->orderBy('numero_piece', 'desc')
            ->first();
        
        if ($lastPiece) {
            $lastNumber = (int) substr($lastPiece->numero_piece, -2);
            $nextNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '01';
        }
        
        return "{$today}-{$nextNumber}";
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
     * Relation avec le compte débit principal
     */
    public function compteDebitPrincipal(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_debit_principal_id');
    }

    /**
     * Relation avec le compte crédit principal
     */
    public function compteCreditPrincipal(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_credit_principal_id');
    }




/**
 * Relation avec les comptes clients (pour les comptes qui ont un client_id)
 */
public function compteClient()
{
    return $this->belongsTo(Compte::class, 'compte_client_id');
}

/**
 * Accessor pour obtenir les comptes débits avec détails (clients + plan)
 */
public function getComptesDebitsDetailsAttribute()
{
    if (!$this->comptes_debits_json) {
        return [];
    }
    
    $comptes = is_array($this->comptes_debits_json) ? 
        $this->comptes_debits_json : 
        json_decode($this->comptes_debits_json, true);
    
    if (!$comptes) {
        return [];
    }
    
    foreach ($comptes as &$compte) {
        // Si c'est un compte client
        if (isset($compte['compte_client_id'])) {
            $clientCompte = Compte::with('client')->find($compte['compte_client_id']);
            if ($clientCompte) {
                $compte['type'] = 'client';
                $compte['numero_compte'] = $clientCompte->numero_compte;
                $compte['libelle'] = $clientCompte->libelle;
                $compte['client_nom'] = $clientCompte->client?->nom_complet;
                $compte['code'] = $clientCompte->numero_compte;
                $compte['display_text'] = ($clientCompte->client?->nom_complet ?? 'Client') . 
                    ' - ' . $clientCompte->numero_compte . ' - ' . $clientCompte->libelle;
            }
        } 
        // Si c'est un compte plan comptable
        elseif (isset($compte['compte_id'])) {
            $planCompte = PlanComptable::find($compte['compte_id']);
            if ($planCompte) {
                $compte['type'] = 'plan';
                $compte['code'] = $planCompte->code;
                $compte['libelle'] = $planCompte->libelle;
                $compte['display_text'] = $planCompte->code . ' - ' . $planCompte->libelle;
            }
        }
    }
    
    return $comptes;
}

/**
 * Accessor pour obtenir les comptes crédits avec détails (clients + plan)
 */
public function getComptesCreditsDetailsAttribute()
{
    if (!$this->comptes_credits_json) {
        return [];
    }
    
    $comptes = is_array($this->comptes_credits_json) ? 
        $this->comptes_credits_json : 
        json_decode($this->comptes_credits_json, true);
    
    if (!$comptes) {
        return [];
    }
    
    foreach ($comptes as &$compte) {
        // Si c'est un compte client
        if (isset($compte['compte_client_id'])) {
            $clientCompte = Compte::with('client')->find($compte['compte_client_id']);
            if ($clientCompte) {
                $compte['type'] = 'client';
                $compte['numero_compte'] = $clientCompte->numero_compte;
                $compte['libelle'] = $clientCompte->libelle;
                $compte['client_nom'] = $clientCompte->client?->nom_complet;
                $compte['code'] = $clientCompte->numero_compte;
                $compte['display_text'] = ($clientCompte->client?->nom_complet ?? 'Client') . 
                    ' - ' . $clientCompte->numero_compte . ' - ' . $clientCompte->libelle;
            }
        } 
        // Si c'est un compte plan comptable
        elseif (isset($compte['compte_id'])) {
            $planCompte = PlanComptable::find($compte['compte_id']);
            if ($planCompte) {
                $compte['type'] = 'plan';
                $compte['code'] = $planCompte->code;
                $compte['libelle'] = $planCompte->libelle;
                $compte['display_text'] = $planCompte->code . ' - ' . $planCompte->libelle;
            }
        }
    }
    
    return $comptes;
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
     * Relation avec le modèle utilisé
     */
    public function modele(): BelongsTo
    {
        return $this->belongsTo(OdModele::class, 'modele_id');
    }

    /**
     * Relation avec l'historique
     */
    public function historique(): HasMany
    {
        return $this->hasMany(OdHistorique::class, 'operation_diverse_id');
    }

    /**
     * Relation avec les signatures
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(OdSignature::class, 'operation_diverse_id');
    }

    /**
     * Relation avec le workflow de validation
     */
    public function workflow(): HasMany
    {
        return $this->hasMany(OdWorkflow::class, 'operation_diverse_id');
    }

    /**
     * Accessor pour obtenir les comptes débits avec détails
     */
    public function getComptesDebitsAttribute()
    {
        if (!$this->comptes_debits_json) {
            return [];
        }
        
        $comptes = is_array($this->comptes_debits_json) ? 
            $this->comptes_debits_json : 
            json_decode($this->comptes_debits_json, true);
        
        if (!$comptes) {
            return [];
        }
        
        // Récupérer les informations des comptes
        foreach ($comptes as &$compte) {
            if (isset($compte['compte_id'])) {
                $planCompte = PlanComptable::find($compte['compte_id']);
                if ($planCompte) {
                    $compte['code'] = $planCompte->code;
                    $compte['libelle'] = $planCompte->libelle;
                    $compte['compte'] = $planCompte;
                }
            }
        }
        
        return $comptes;
    }

    /**
     * Accessor pour obtenir les comptes crédits avec détails
     */
    public function getComptesCreditsAttribute()
    {
        if (!$this->comptes_credits_json) {
            return [];
        }
        
        $comptes = is_array($this->comptes_credits_json) ? 
            $this->comptes_credits_json : 
            json_decode($this->comptes_credits_json, true);
        
        if (!$comptes) {
            return [];
        }
        
        // Récupérer les informations des comptes
        foreach ($comptes as &$compte) {
            if (isset($compte['compte_id'])) {
                $planCompte = PlanComptable::find($compte['compte_id']);
                if ($planCompte) {
                    $compte['code'] = $planCompte->code;
                    $compte['libelle'] = $planCompte->libelle;
                    $compte['compte'] = $planCompte;
                }
            }
        }
        
        return $comptes;
    }

    /**
     * Vérifie si l'OD peut être validée par le chef d'agence
     */
    public function peutEtreValideeParAgence(): bool
    {
        return in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_SAISI]) 
            && $this->montant > 0
            && $this->estEquilibree();
    }

    /**
     * Vérifie si l'OD peut être validée par le chef comptable
     */
    public function peutEtreValideeParComptable(): bool
    {
        // Le comptable peut valider si :
        // 1. L'OD est au statut "SAISI" (début) ou "VALIDE_AGENCE"
        // 2. ET que le DG n'a PAS déjà validé
        $dgAValide = $this->workflow()->where('niveau', self::NIVEAU_DG)
            ->where('decision', 'APPROUVE')
            ->exists();
            
        return in_array($this->statut, [self::STATUT_SAISI, self::STATUT_VALIDE_AGENCE]) 
            && !$dgAValide
            && $this->montant > 0
            && $this->estEquilibree();
    }

    /**
     * Vérifie si l'OD peut être validée par le DG
     */
    public function peutEtreValideeParDG(): bool
    {
        // Le DG peut TOUJOURS valider indépendamment des autres validations
        // Mais seulement si l'OD n'est pas déjà validée
        return !in_array($this->statut, [self::STATUT_VALIDE, self::STATUT_ANNULE, self::STATUT_REJETE])
            && $this->montant > 0
            && $this->estEquilibree();
    }

    /**
     * Vérifie si tous les niveaux requis ont validé
     */
    public function estCompletementValidee(): bool
    {
        // Obtenir tous les niveaux qui ont approuvé
        $niveauxApprouves = $this->workflow()
            ->where('decision', 'APPROUVE')
            ->pluck('niveau')
            ->toArray();

        // Déterminer quels niveaux sont requis
        $niveauxRequis = [];
        
        // Chef d'agence est toujours requis
        $niveauxRequis[] = self::NIVEAU_AGENCE;
        $niveauxRequis[] = self::NIVEAU_COMPTABLE;
        
        // Pour les charges, le DG est requis
        if ($this->type_collecte === self::TYPE_CHARGE) {
            $niveauxRequis[] = self::NIVEAU_DG;
        }

        // Vérifier si tous les niveaux requis ont validé
        return count(array_intersect($niveauxRequis, $niveauxApprouves)) === count($niveauxRequis);
    }

    /**
     * Mettre à jour le statut global après une validation
     */
    private function mettreAJourStatutGlobal(): void
    {
        if ($this->estCompletementValidee()) {
            $this->update(['statut' => self::STATUT_VALIDE]);
        }
    }

    /**
     * Valider l'OD par le chef d'agence
     */
    public function validerParAgence(User $validateur, string $commentaire = null): bool
    {
        if (!$this->peutEtreValideeParAgence()) {
            return false;
        }

        DB::transaction(function () use ($validateur, $commentaire) {
            $ancienStatut = $this->statut;
            
            $this->update([
                'statut' => self::STATUT_VALIDE_AGENCE,
                'valide_par' => $validateur->id,
                'date_validation' => now(),
            ]);

            // Enregistrer dans le workflow
            $this->workflow()->create([
                'niveau' => self::NIVEAU_AGENCE,
                'role_requis' => 'Chef d\'Agence (CA)',
                'user_id' => $validateur->id,
                'decision' => 'APPROUVE',
                'commentaire' => $commentaire,
                'date_decision' => now(),
            ]);

            // Vérifier si la validation est complète
            $this->mettreAJourStatutGlobal();

            $this->enregistrerHistorique('VALIDATION_AGENCE', $ancienStatut, $this->statut);
        });

        return true;
    }

    /**
     * Valider l'OD par le chef comptable
     */
    public function validerParComptable(User $validateur, string $commentaire = null): bool
    {
        if (!$this->peutEtreValideeParComptable()) {
            return false;
        }

        DB::transaction(function () use ($validateur, $commentaire) {
            $ancienStatut = $this->statut;
            
            // Si le DG a déjà validé, on passe directement à VALIDE
            $dgAValide = $this->workflow()->where('niveau', self::NIVEAU_DG)
                ->where('decision', 'APPROUVE')
                ->exists();
                
            $nouveauStatut = $dgAValide ? self::STATUT_VALIDE : self::STATUT_VALIDE_COMPTABLE;
            
            $this->update([
                'statut' => $nouveauStatut,
                'date_validation' => now(),
            ]);

            // Enregistrer dans le workflow
            $this->workflow()->create([
                'niveau' => self::NIVEAU_COMPTABLE,
                'role_requis' => 'Chef Comptable',
                'user_id' => $validateur->id,
                'decision' => 'APPROUVE',
                'commentaire' => $commentaire,
                'date_decision' => now(),
            ]);

            // Vérifier si la validation est complète
            $this->mettreAJourStatutGlobal();

            $this->enregistrerHistorique('VALIDATION_COMPTABLE', $ancienStatut, $nouveauStatut);
        });

        return true;
    }

    /**
     * Valider l'OD par le DG
     */
    public function validerParDG(User $validateur, string $commentaire = null): bool
    {
        if (!$this->peutEtreValideeParDG()) {
            return false;
        }

        DB::transaction(function () use ($validateur, $commentaire) {
            $ancienStatut = $this->statut;
            
            // Vérifier quels niveaux ont déjà validé
            $agenceAValide = $this->workflow()->where('niveau', self::NIVEAU_AGENCE)
                ->where('decision', 'APPROUVE')
                ->exists();
                
            $comptableAValide = $this->workflow()->where('niveau', self::NIVEAU_COMPTABLE)
                ->where('decision', 'APPROUVE')
                ->exists();

            // Déterminer le nouveau statut
            if ($agenceAValide && $comptableAValide) {
                // Tous ont validé, passer à VALIDE
                $nouveauStatut = self::STATUT_VALIDE;
            } elseif ($agenceAValide) {
                // Seul l'agence a validé
                $nouveauStatut = self::STATUT_VALIDE_AGENCE;
            } elseif ($comptableAValide) {
                // Seul le comptable a validé (cas rare mais possible)
                $nouveauStatut = self::STATUT_VALIDE_COMPTABLE;
            } else {
                // Personne n'a validé, on crée un statut spécial
                $nouveauStatut = self::STATUT_VALIDE_DG;
            }
            
            $this->update([
                'statut' => $nouveauStatut,
                'date_validation' => now(),
            ]);

            // Enregistrer dans le workflow
            $this->workflow()->create([
                'niveau' => self::NIVEAU_DG,
                'role_requis' => 'DG',
                'user_id' => $validateur->id,
                'decision' => 'APPROUVE',
                'commentaire' => $commentaire,
                'date_decision' => now(),
            ]);

            // Vérifier si la validation est complète
            $this->mettreAJourStatutGlobal();

            $this->enregistrerHistorique('VALIDATION_DG', $ancienStatut, $nouveauStatut);
        });

        return true;
    }

    /**
     * Vérifie si une OD est prête à être comptabilisée
     */
    public function estPretePourComptabilisation(): bool
    {
        return $this->estCompletementValidee() 
            && $this->statut === self::STATUT_VALIDE
            && !$this->est_comptabilise;
    }

    /**
     * Vérifie si l'OD est multi-comptes
     */
    public function estMultiComptes(): bool
    {
        $comptesDebits = $this->getTousComptesDebits();
        $comptesCredits = $this->getTousComptesCredits();
        
        return count($comptesDebits) > 1 || count($comptesCredits) > 1;
    }

    /**
     * Obtenir tous les comptes débits sous forme de tableau
     */
    public function getTousComptesDebits(): array
    {
        if ($this->comptes_debits_json) {
            $comptes = is_array($this->comptes_debits_json) ? 
                $this->comptes_debits_json : 
                json_decode($this->comptes_debits_json, true);
            
            if (is_array($comptes)) {
                return $comptes;
            }
        }
        
        // Sinon, utiliser le système ancien
        if ($this->compte_debit_id) {
            return [[
                'type' => 'plan',
                'compte_id' => $this->compte_debit_id,
                'montant' => $this->montant_total ?: $this->montant
            ]];
        }
        
        return [];
    }

        /**
         * Obtenir tous les comptes crédits sous forme de tableau
         */
    public function getTousComptesCredits(): array
    {
        if ($this->comptes_credits_json) {
            $comptes = is_array($this->comptes_credits_json) ? 
                $this->comptes_credits_json : 
                json_decode($this->comptes_credits_json, true);
            
            if (is_array($comptes)) {
                return $comptes;
            }
        }
        
        // Sinon, utiliser le système ancien
        if ($this->compte_credit_id) {
            return [[
                'type' => 'plan',
                'compte_id' => $this->compte_credit_id,
                'montant' => $this->montant_total ?: $this->montant
            ]];
        }
        
        return [];
    }

    /**
     * Vérifier l'équilibre des comptes
     */
    public function verifierEquilibre(): bool
    {
        $totalDebits = collect($this->getTousComptesDebits())->sum('montant');
        $totalCredits = collect($this->getTousComptesCredits())->sum('montant');
        
        return abs($totalDebits - $totalCredits) < 0.01;
    }

    /**
     * Obtenir l'état actuel des validations
     */
    public function getEtatValidations(): array
    {
        $etat = [
            'chef_agence' => false,
            'chef_comptable' => false,
            'dg' => false,
            'est_complet' => false,
            'peut_comptabiliser' => false,
        ];

        // Récupérer toutes les validations approuvées
        $validations = $this->workflow()
            ->where('decision', 'APPROUVE')
            ->get();

        foreach ($validations as $validation) {
            switch ($validation->niveau) {
                case self::NIVEAU_AGENCE:
                    $etat['chef_agence'] = true;
                    break;
                case self::NIVEAU_COMPTABLE:
                    $etat['chef_comptable'] = true;
                    break;
                case self::NIVEAU_DG:
                    $etat['dg'] = true;
                    break;
            }
        }

        $etat['est_complet'] = $this->estCompletementValidee();
        $etat['peut_comptabiliser'] = $this->estPretePourComptabilisation();

        return $etat;
    }

    /**
     * Rejeter l'OD
     */
    public function rejeter(User $rejeteur, string $motif): bool
    {
        if (!in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_SAISI, self::STATUT_VALIDE_AGENCE, self::STATUT_VALIDE_COMPTABLE, self::STATUT_VALIDE_DG])) {
            return false;
        }

        DB::transaction(function () use ($rejeteur, $motif) {
            $ancienStatut = $this->statut;
            
            $this->update([
                'statut' => self::STATUT_REJETE,
                'motif_rejet' => $motif,
            ]);

            // Déterminer le niveau de rejet selon le statut actuel
            $niveau = 1;
            $role = 'Chef d\'Agence (CA)';
            
            if ($this->statut === self::STATUT_VALIDE_AGENCE) {
                $niveau = 2;
                $role = 'Chef Comptable';
            } elseif (in_array($this->statut, [self::STATUT_VALIDE_COMPTABLE, self::STATUT_VALIDE_DG])) {
                $niveau = 3;
                $role = 'DG';
            }

            // Enregistrer dans le workflow
            $this->workflow()->create([
                'niveau' => $niveau,
                'role_requis' => $role,
                'user_id' => $rejeteur->id,
                'decision' => 'REJETE',
                'commentaire' => $motif,
                'date_decision' => now(),
            ]);

            $this->enregistrerHistorique('REJET', $ancienStatut, self::STATUT_REJETE, $rejeteur);
        });

        return true;
    }

    /**
     * Comptabiliser l'OD (créer les mouvements comptables pour tous les comptes)
     */ 
public function comptabiliser(User $comptable): bool
{
    if (!$this->estPretePourComptabilisation()) {
        \Log::warning('OD non prête pour comptabilisation', [
            'od_id' => $this->id,
            'statut' => $this->statut,
            'est_comptabilise' => $this->est_comptabilise
        ]);
        return false;
    }
    
    if (!$this->verifierEquilibre()) {
        throw new \Exception('L\'écriture comptable n\'est pas équilibrée');
    }

    DB::beginTransaction();
    
    try {
        // Récupérer les comptes depuis les JSON
        $comptesDebits = $this->parseComptesJson($this->comptes_debits_json);
        $comptesCredits = $this->parseComptesJson($this->comptes_credits_json);
        
        \Log::info('Début comptabilisation OD', [
            'od_id' => $this->id,
            'numero_od' => $this->numero_od,
            'nb_debits' => count($comptesDebits),
            'nb_credits' => count($comptesCredits)
        ]);
        
        $journal = $this->determinerJournal();
        $reference = $this->numero_od;
        $libelle = "OD {$this->numero_od}: {$this->libelle}";
        $dateComptable = $this->date_comptable ?? $this->date_operation;
        $dateValeur = $this->date_valeur ?? $dateComptable;
        
        // CRÉER LES MOUVEMENTS POUR LES COMPTES DÉBITS
        foreach ($comptesDebits as $index => $compteDebit) {
            $mouvement = $this->creerMouvementComptable(
                compteData: $compteDebit,
                type: 'debit',
                montant: $compteDebit['montant'] ?? 0,
                libelle: $libelle,
                description: $this->getDescriptionLigne('débit', $index, count($comptesDebits)),
                journal: $journal,
                reference: $reference,
                dateComptable: $dateComptable,
                dateValeur: $dateValeur,
                comptable: $comptable
            );
            
            \Log::info('Mouvement débit créé', [
                'mouvement_id' => $mouvement->id,
                'compte_id' => $mouvement->compte_id,
                'plan_comptable_id' => $mouvement->plan_comptable_id,
                'montant' => $mouvement->montant_debit
            ]);
        }
        
        // CRÉER LES MOUVEMENTS POUR LES COMPTES CRÉDITS
        foreach ($comptesCredits as $index => $compteCredit) {
            $mouvement = $this->creerMouvementComptable(
                compteData: $compteCredit,
                type: 'credit',
                montant: $compteCredit['montant'] ?? 0,
                libelle: $libelle,
                description: $this->getDescriptionLigne('crédit', $index, count($comptesCredits)),
                journal: $journal,
                reference: $reference,
                dateComptable: $dateComptable,
                dateValeur: $dateValeur,
                comptable: $comptable
            );
            
            \Log::info('Mouvement crédit créé', [
                'mouvement_id' => $mouvement->id,
                'compte_id' => $mouvement->compte_id,
                'plan_comptable_id' => $mouvement->plan_comptable_id,
                'montant' => $mouvement->montant_credit
            ]);
        }
        
        // Mettre à jour l'OD
        $ancienStatut = $this->statut;
        $this->update([
            'est_comptabilise' => true,
            'comptabilise_par' => $comptable->id,
            'date_comptabilisation' => now(),
        ]);

        $this->enregistrerHistorique('COMPTABILISATION', $ancienStatut, $this->statut, $comptable);

        DB::commit();
        
        \Log::info('OD comptabilisée avec succès', [
            'od_id' => $this->id,
            'nombre_mouvements' => count($comptesDebits) + count($comptesCredits)
        ]);
        
        return true;

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Erreur comptabilisation OD:', [
            'od_id' => $this->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

/**
 * Parse les comptes JSON quel que soit le format
 */
private function parseComptesJson($json): array
{
    if (!$json) {
        return [];
    }
    
    $comptes = is_array($json) ? $json : json_decode($json, true);
    
    if (!is_array($comptes)) {
        return [];
    }
    
    return $comptes;
}

/**
 * Crée un mouvement comptable avec gestion des différents formats
 */
private function creerMouvementComptable(
    array $compteData,
    string $type,
    float $montant,
    string $libelle,
    string $description,
    string $journal,
    string $reference,
    $dateComptable,
    $dateValeur,
    User $comptable
): MouvementComptable {
    // Déterminer les IDs
    $compteId = null;        // ID du compte client (table comptes)
    $planComptableId = null; // ID du plan comptable (table plan_comptable)
    
    // Log du compte data pour débogage
    \Log::debug('Traitement compte', ['compteData' => $compteData]);
    
    // CAS 1: Format avec 'type' explicite (nouveau format)
    if (isset($compteData['type'])) {
        if ($compteData['type'] === 'client' && isset($compteData['compte_client_id'])) {
            // C'est un compte client
            $compteClient = Compte::find($compteData['compte_client_id']);
            if ($compteClient) {
                $compteId = $compteClient->id;
                $planComptableId = $compteClient->plan_comptable_id;
                
                \Log::debug('Compte client trouvé', [
                    'compte_id' => $compteId,
                    'plan_comptable_id' => $planComptableId,
                    'numero_compte' => $compteClient->numero_compte
                ]);
            } else {
                \Log::error('Compte client non trouvé', ['id' => $compteData['compte_client_id']]);
            }
        } elseif ($compteData['type'] === 'plan' && isset($compteData['compte_id'])) {
            // C'est un compte plan comptable
            $planComptableId = $compteData['compte_id'];
            
            // Vérifier si ce plan comptable est lié à un compte client
            $compteClient = Compte::where('plan_comptable_id', $planComptableId)->first();
            if ($compteClient) {
                $compteId = $compteClient->id;
                \Log::debug('Plan comptable lié à un compte client', [
                    'plan_comptable_id' => $planComptableId,
                    'compte_id' => $compteId
                ]);
            }
        }
    }
    // CAS 2: Ancien format (compatibilité)
    else {
        if (isset($compteData['compte_client_id'])) {
            $compteClient = Compte::find($compteData['compte_client_id']);
            if ($compteClient) {
                $compteId = $compteClient->id;
                $planComptableId = $compteClient->plan_comptable_id;
            }
        } elseif (isset($compteData['compte_id'])) {
            $planComptableId = $compteData['compte_id'];
            
            // Vérifier si ce plan comptable est lié à un compte client
            $compteClient = Compte::where('plan_comptable_id', $planComptableId)->first();
            if ($compteClient) {
                $compteId = $compteClient->id;
            }
        }
    }
    
    // CAS 3: Vérifier dans les comptes_clients_json pour MATA BOOST avec répartition
    if (!$compteId && $this->comptes_clients_json) {
        $clientsData = $this->parseComptesJson($this->comptes_clients_json);
        
        // Chercher un client dont le montant correspond
        foreach ($clientsData as $clientData) {
            if (isset($clientData['montant']) && abs($clientData['montant'] - $montant) < 0.01) {
                if (isset($clientData['compte_client_id'])) {
                    $compteClient = Compte::find($clientData['compte_client_id']);
                    if ($compteClient) {
                        $compteId = $compteClient->id;
                        $planComptableId = $compteClient->plan_comptable_id;
                        \Log::debug('Client trouvé via comptes_clients_json', [
                            'compte_id' => $compteId
                        ]);
                        break;
                    }
                }
            }
        }
    }
    
    if (!$planComptableId) {
        \Log::error('Impossible de déterminer le plan comptable', ['compteData' => $compteData]);
        throw new \Exception('Compte plan comptable non trouvé');
    }
    
    // Créer le mouvement
    $data = [
        'plan_comptable_id' => $planComptableId,
        'date_mouvement' => $dateComptable,
        'date_valeur' => $dateValeur,
        'libelle_mouvement' => $libelle,
        'description' => $description,
        'journal' => $journal,
        'numero_piece' => $this->numero_piece ?? $reference,
        'reference_operation' => $reference,
        'statut' => 'COMPTABILISE',
        'est_pointage' => false,
        'validateur_id' => $comptable->id,
        'date_validation' => now(),
        'agence_id' => $this->agence_id,
        'od_id' => $this->id,
        'created_by' => $comptable->id,
    ];
    
    // Ajouter compte_id si trouvé (pour mise à jour solde)
    if ($compteId) {
        $data['compte_id'] = $compteId;
    }
    
    // Ajouter le montant selon le type
    if ($type === 'debit') {
        $data['montant_debit'] = $montant;
        $data['montant_credit'] = 0;
    } else {
        $data['montant_debit'] = 0;
        $data['montant_credit'] = $montant;
    }
    
    return MouvementComptable::create($data);
}

/**
 * Génère une description pour une ligne d'écriture
 */
private function getDescriptionLigne(string $type, int $index, int $total): string
{
    $base = $this->description ?: $this->libelle;
    
    if ($total > 1) {
        $base .= " - " . ucfirst($type) . " " . ($index + 1) . "/" . $total;
    }
    
    return $base;
}
    /**
     * Mettre à jour les soldes des comptes après comptabilisation
     */
   

    /**
     * Déterminer le journal comptable selon le type d'opération
     */
    private function determinerJournal(): string
    {
        if ($this->est_collecte) {
            if ($this->type_collecte === self::TYPE_MATA_BOOST) {
                return 'MATA_BOOST';
            } elseif ($this->type_collecte === self::TYPE_EPARGNE_JOURNALIERE) {
                return 'EPARGNE_JOURNALIERE';
            } elseif ($this->type_collecte === self::TYPE_CHARGE) {
                return 'CHARGES';
            }
        }

        $journaux = [
            'VIREMENT' => 'VIREMENT',
            'FRAIS' => 'FRAIS',
            'COMMISSION' => 'COMMISSION',
            'REGULARISATION' => 'DIVERS',
            'AUTRE' => 'DIVERS',
        ];

        return $journaux[$this->type_operation] ?? 'DIVERS';
    }

    /**
     * Créer une OD pour MATA BOOST
     */
// Dans App\Models\OD\OperationDiverse.php

/**
 * Créer une OD pour MATA BOOST avec répartition par comptes clients
 */
public static function creerMataBoostAvecClients(array $data, User $saisiPar): self
{
    DB::beginTransaction();
    
    try {
        // Prendre le premier compte comme compte principal (pour compatibilité)
        $premierCollecteur = collect($data['comptes_collecteurs'])->first();
        
        // Calculer le montant total
        $montantTotal = collect($data['comptes_collecteurs'])->sum('montant');
        
        // Vérifier que le total des collecteurs = total des crédits clients
        $totalCreditsClients = collect($data['comptes_clients'])->sum('montant');
        
        if (abs($montantTotal - $totalCreditsClients) > 0.01) {
            throw new \Exception('Le total des collecteurs doit être égal au total des crédits clients');
        }
        
        // Le compte MATA BOOST principal (générique)
        $compteMataBoostGenerique = $data['compte_mata_boost_id'];
        
        // Récupérer les informations du compte MATA BOOST générique pour le JSON
        $planCompte = PlanComptable::find($compteMataBoostGenerique);
        
        // Construction des comptes crédits : le compte MATA BOOST générique est crédité
        // et on crée des entrées de détail dans le JSON pour les comptes clients
        $comptesCredits = [[
            'compte_id' => $compteMataBoostGenerique,
            'montant' => $montantTotal,
            'libelle' => $planCompte ? $planCompte->libelle : 'MATA BOOST'
        ]];
        
        // Stocker les détails des comptes clients dans un champ additionnel
        // On va utiliser le champ 'comptes_clients_json' (à ajouter)
        $od = self::create([
            'agence_id' => $data['agence_id'],
            'date_operation' => $data['date_operation'] ?? now(),
            'date_valeur' => $data['date_valeur'] ?? now(),
            'date_comptable' => $data['date_comptable'] ?? now(),
            'type_operation' => 'AUTRE',
            'type_collecte' => self::TYPE_MATA_BOOST,
            'code_operation' => $data['est_bloque'] 
                ? self::CODE_MATA_BOOST_BLOQUE 
                : self::CODE_MATA_BOOST_JOURNALIER,
            'libelle' => $data['libelle'] ?? 'Collecte MATA BOOST avec répartition clients',
            'description' => $data['description'] ?? null,
            'montant' => $montantTotal,
            'montant_total' => $montantTotal,
            'devise' => $data['devise'] ?? 'FCFA',
            'compte_debit_id' => $premierCollecteur['compte_id'],
            'compte_credit_id' => $compteMataBoostGenerique,
            'compte_debit_principal_id' => $premierCollecteur['compte_id'],
            'compte_credit_principal_id' => $compteMataBoostGenerique,
            'sens_operation' => self::SENS_DEBIT,
            // Stocker tous les comptes en JSON
            'comptes_debits_json' => json_encode($data['comptes_collecteurs']),
            'comptes_credits_json' => json_encode($comptesCredits),
            // Stocker la répartition clients
            'comptes_clients_json' => json_encode($data['comptes_clients']),
            'est_collecte' => true,
            'est_bloque' => $data['est_bloque'] ?? false,
            'numero_guichet' => $data['numero_guichet'],
            'numero_bordereau' => $data['numero_bordereau'],
            'saisi_par' => $saisiPar->id,
            'statut' => self::STATUT_SAISI,
            'justificatif_type' => $data['justificatif_type'] ?? 'BON',
            'justificatif_numero' => $data['justificatif_numero'] ?? $data['numero_bordereau'],
            'justificatif_date' => $data['justificatif_date'] ?? $data['date_operation'] ?? now(),
            'justificatif_path' => $data['justificatif_path'] ?? null,
            'reference_client' => $data['reference_client'] ?? null,
            'nom_tiers' => $data['nom_agent'] ?? null,
        ]);

        // Enregistrer les écritures de détail pour les comptes clients
        // Cela sera fait lors de la comptabilisation
        
        $od->enregistrerHistorique('CREATION', null, self::STATUT_SAISI);

        DB::commit();
        return $od;

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

/**
 * Accessor pour obtenir les comptes clients avec détails
 */
public function getComptesClientsAttribute()
{
    if (!$this->comptes_clients_json) {
        return [];
    }
    
    $comptes = is_array($this->comptes_clients_json) ? 
        $this->comptes_clients_json : 
        json_decode($this->comptes_clients_json, true);
    
    if (!$comptes) {
        return [];
    }
    
    // Récupérer les informations des comptes clients
    foreach ($comptes as &$compte) {
        if (isset($compte['compte_client_id'])) {
            $clientCompte = Compte::with('client')->find($compte['compte_client_id']);
            if ($clientCompte) {
                $compte['numero_compte'] = $clientCompte->numero_compte;
                $compte['nom_client'] = $clientCompte->client->nom_complet ?? 'Client inconnu';
                $compte['compte'] = $clientCompte;
            }
        }
    }
    
    return $comptes;
}

//end ajout MATA BOOST avec répartition clients


/**
 * Créer une OD pour Épargne Journalière avec répartition par comptes clients
 */
public static function creerEpargneJournaliereAvecClients(array $data, User $saisiPar): self
{
    DB::beginTransaction();
    
    try {
        // Prendre le premier compte comme compte principal (pour compatibilité)
        $premierCollecteur = collect($data['comptes_collecteurs'])->first();
        
        // Calculer le montant total
        $montantTotal = collect($data['comptes_collecteurs'])->sum('montant');
        
        // Vérifier que le total des collecteurs = total des crédits clients
        $totalCreditsClients = collect($data['comptes_clients'])->sum('montant');
        
        if (abs($montantTotal - $totalCreditsClients) > 0.01) {
            throw new \Exception('Le total des collecteurs doit être égal au total des crédits clients');
        }
        
        // Le compte Épargne Journalière principal (générique)
        $compteEpargneGenerique = $data['compte_epargne_id'];
        
        // Récupérer les informations du compte Épargne générique
        $planCompte = PlanComptable::find($compteEpargneGenerique);
        
        // Construction des comptes crédits : le compte Épargne générique est crédité
        $comptesCredits = [[
            'compte_id' => $compteEpargneGenerique,
            'montant' => $montantTotal,
            'libelle' => $planCompte ? $planCompte->libelle : 'Épargne Journalière'
        ]];
        
        // Déterminer le code opération (bloqué ou journalier)
        $codeOperation = $data['est_bloque'] 
            ? self::CODE_EPARGNE_JOURNALIERE . '_BLOQUE' 
            : self::CODE_EPARGNE_JOURNALIERE;
        
        $od = self::create([
            'agence_id' => $data['agence_id'],
            'date_operation' => $data['date_operation'] ?? now(),
            'date_valeur' => $data['date_valeur'] ?? now(),
            'date_comptable' => $data['date_comptable'] ?? now(),
            'type_operation' => 'AUTRE',
            'type_collecte' => self::TYPE_EPARGNE_JOURNALIERE,
            'code_operation' => $codeOperation,
            'libelle' => $data['libelle'] ?? 'Collecte Épargne Journalière avec répartition clients',
            'description' => $data['description'] ?? null,
            'montant' => $montantTotal,
            'montant_total' => $montantTotal,
            'devise' => $data['devise'] ?? 'FCFA',
            'compte_debit_id' => $premierCollecteur['compte_id'],
            'compte_credit_id' => $compteEpargneGenerique,
            'compte_debit_principal_id' => $premierCollecteur['compte_id'],
            'compte_credit_principal_id' => $compteEpargneGenerique,
            'sens_operation' => self::SENS_DEBIT,
            // Stocker tous les comptes en JSON
            'comptes_debits_json' => json_encode($data['comptes_collecteurs']),
            'comptes_credits_json' => json_encode($comptesCredits),
            // Stocker la répartition clients
            'comptes_clients_json' => json_encode($data['comptes_clients']),
            'est_collecte' => true,
            'est_bloque' => $data['est_bloque'] ?? false,
            'numero_guichet' => $data['numero_guichet'],
            'numero_bordereau' => $data['numero_bordereau'],
            'saisi_par' => $saisiPar->id,
            'statut' => self::STATUT_SAISI,
            'justificatif_type' => $data['justificatif_type'] ?? 'BON',
            'justificatif_numero' => $data['justificatif_numero'] ?? $data['numero_bordereau'],
            'justificatif_date' => $data['justificatif_date'] ?? $data['date_operation'] ?? now(),
            'justificatif_path' => $data['justificatif_path'] ?? null,
            'reference_client' => $data['reference_client'] ?? null,
            'nom_tiers' => $data['nom_agent'] ?? null,
        ]);

        $od->enregistrerHistorique('CREATION', null, self::STATUT_SAISI);

        DB::commit();
        return $od;

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

//end ajout Épargne Journalière avec répartition clients

    /**
     * Créer une OD pour Charges
     */
    public static function creerCharge(array $data, User $saisiPar): self
    {
        DB::beginTransaction();
        
        try {
            $od = self::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'] ?? now(),
                'date_valeur' => $data['date_valeur'] ?? now(),
                'date_comptable' => $data['date_comptable'] ?? now(),
                'type_operation' => 'FRAIS',
                'type_collecte' => self::TYPE_CHARGE,
                'code_operation' => self::CODE_CHARGE,
                'libelle' => $data['libelle'] ?? 'Règlement de charge',
                'description' => $data['description'],
                'montant' => $data['montant'],
                'montant_total' => $data['montant'],
                'devise' => $data['devise'] ?? 'FCFA',
                'compte_debit_id' => $data['compte_charge_id'],
                'compte_credit_id' => $data['compte_passage_id'],
                'compte_debit_principal_id' => $data['compte_charge_id'],
                'compte_credit_principal_id' => $data['compte_passage_id'],
                'sens_operation' => self::SENS_DEBIT,
                'comptes_credits_json' => json_encode([[
                    'compte_id' => $data['compte_passage_id'],
                    'montant' => $data['montant']
                ]]),
                'est_collecte' => false,
                'numero_guichet' => $data['numero_guichet'],
                'numero_piece' => $data['numero_piece'],
                'saisi_par' => $saisiPar->id,
                'statut' => self::STATUT_SAISI,
                'justificatif_type' => $data['justificatif_type'] ?? 'FACTURE',
                'justificatif_numero' => $data['justificatif_numero'],
                'justificatif_date' => $data['justificatif_date'] ?? now(),
                'reference_client' => $data['reference_client'],
                'nom_tiers' => $data['nom_fournisseur'] ?? null,
                'est_urgence' => $data['est_urgence'] ?? false,
            ]);

            // Upload du justificatif si fourni
            if (isset($data['justificatif'])) {
                $path = $data['justificatif']->store(
                    "justificatifs/od/{$od->id}",
                    'public'
                );
                $od->update(['justificatif_path' => $path]);
            }

            $od->enregistrerHistorique('CREATION', null, self::STATUT_SAISI);

            DB::commit();
            return $od;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Créer une OD Générique avec comptes multiples
     */
    public static function creerGenerique(array $data, User $saisiPar): self
    {
        DB::beginTransaction();
        
        try {
            // Préparer les données JSON
            $comptesDebitsJson = $data['sens_operation'] === 'CREDIT' ? 
                json_encode($data['comptes_debits']) : null;
            
            $comptesCreditsJson = $data['sens_operation'] === 'DEBIT' ? 
                json_encode($data['comptes_credits']) : null;

            $compteDebitPrincipal = $data['sens_operation'] === 'DEBIT' ? 
                $data['compte_debit_id'] : null;
            
            $compteCreditPrincipal = $data['sens_operation'] === 'CREDIT' ? 
                $data['compte_credit_id'] : null;

            $od = self::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'] ?? now(),
                'date_valeur' => $data['date_valeur'] ?? now(),
                'date_comptable' => $data['date_comptable'] ?? now(),
                'type_operation' => $data['type_operation'] ?? 'REGULARISATION',
                'type_collecte' => self::TYPE_AUTRE,
                'code_operation' => self::CODE_GENERIQUE,
                'libelle' => $data['libelle'] ?? 'Opération diverse générique',
                'description' => $data['description'],
                'montant' => $data['montant_total'],
                'montant_total' => $data['montant_total'],
                'devise' => $data['devise'] ?? 'FCFA',
                'sens_operation' => $data['sens_operation'],
                
                // Comptes principaux
                'compte_debit_id' => $compteDebitPrincipal,
                'compte_credit_id' => $compteCreditPrincipal,
                'compte_debit_principal_id' => $compteDebitPrincipal,
                'compte_credit_principal_id' => $compteCreditPrincipal,
                
                // Données JSON pour les comptes multiples
                'comptes_debits_json' => $comptesDebitsJson,
                'comptes_credits_json' => $comptesCreditsJson,
                
                'est_collecte' => $data['est_collecte'] ?? false,
                'est_urgence' => $data['est_urgence'] ?? false,
                'numero_guichet' => $data['numero_guichet'],
                'numero_piece' => $data['numero_piece'] ?? self::generateNumeroPiece(),
                'numero_bordereau' => $data['numero_bordereau'] ?? null,
                'ref_lettrage' => $data['ref_lettrage'] ?? null,
                'saisi_par' => $saisiPar->id,
                'statut' => self::STATUT_SAISI,
                'justificatif_type' => $data['justificatif_type'] ?? self::JUSTIF_AUTRE,
                'justificatif_numero' => $data['justificatif_numero'] ?? null,
                'justificatif_date' => $data['justificatif_date'] ?? now(),
                'reference_client' => $data['reference_client'] ?? null,
                'nom_tiers' => $data['nom_tiers'] ?? null,
            ]);

            // Sauvegarder le justificatif en base64 si fourni
            if (!empty($data['justificatif_base64']) && !empty($data['justificatif_filename'])) {
                $fileName = uniqid() . '_' . $data['justificatif_filename'];
                $path = "justificatifs/od/{$od->id}/{$fileName}";
                
                $fileContent = base64_decode($data['justificatif_base64']);
                \Illuminate\Support\Facades\Storage::disk('public')->put($path, $fileContent);
                
                $od->update(['justificatif_path' => $path]);
            }

            $od->enregistrerHistorique('CREATION', null, self::STATUT_SAISI);

            DB::commit();
            return $od;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Enregistrer une entrée dans l'historique
     */
    public function enregistrerHistorique(
        string $action, 
        ?string $ancienStatut = null, 
        ?string $nouveauStatut = null,
        ?User $user = null,
        ?array $donneesAdditionnelles = null
    ): void {
        $user = $user ?? auth()->user();
        
        $this->historique()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'description' => $this->getDescriptionHistorique($action),
            'donnees_modifiees' => $this->getDonneesModifiees(),
            'donnees_additionnelles' => $donneesAdditionnelles,
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
            case 'VALIDATION_AGENCE':
                return "Validation par le chef d'agence de l'OD {$this->numero_od}";
            case 'VALIDATION_COMPTABLE':
                return "Validation par le chef comptable de l'OD {$this->numero_od}";
            case 'VALIDATION_DG':
                return "Validation par le DG de l'OD {$this->numero_od}";
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
     * Vérifier si l'OD est équilibrée
     */
    public function estEquilibree(): bool
    {
        if ($this->sens_operation === self::SENS_DEBIT) {
            $totalCredits = collect($this->comptes_credits)->sum('montant');
            return abs($totalCredits - $this->montant_total) < 0.01;
        } else {
            $totalDebits = collect($this->comptes_debits)->sum('montant');
            return abs($totalDebits - $this->montant_total) < 0.01;
        }
    }

    /**
     * Vérifier si l'OD peut être modifiée
     */
    public function peutEtreModifiee(): bool
    {
        return in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_SAISI, self::STATUT_REJETE]);
    }

    /**
     * Scope pour les OD en attente de validation par l'agence
     */
    public function scopeEnAttenteValidationAgence($query)
    {
        return $query->whereIn('statut', [self::STATUT_BROUILLON, self::STATUT_SAISI]);
    }

    /**
     * Scope pour les OD en attente de validation par la comptabilité
     */
    public function scopeEnAttenteValidationComptable($query)
    {
        return $query->where('statut', self::STATUT_VALIDE_AGENCE);
    }

    /**
     * Scope pour les OD en attente de validation par le DG
     */
    public function scopeEnAttenteValidationDG($query)
    {
        return $query->where('statut', self::STATUT_VALIDE_COMPTABLE)
            ->where('type_collecte', self::TYPE_CHARGE);
    }

    /**
     * Scope pour les OD validées
     */
    public function scopeValidees($query)
    {
        return $query->where('statut', self::STATUT_VALIDE);
    }

    /**
     * Scope pour les OD comptabilisées
     */
    public function scopeComptabilisees($query)
    {
        return $query->where('est_comptabilise', true);
    }

    /**
     * Scope par type de collecte
     */
    public function scopeParTypeCollecte($query, $type)
    {
        return $query->where('type_collecte', $type);
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

    /**
     * Scope par code opération
     */
    public function scopeParCodeOperation($query, $code)
    {
        return $query->where('code_operation', $code);
    }

    /**
     * Scope par sens d'opération
     */
    public function scopeParSens($query, $sens)
    {
        return $query->where('sens_operation', $sens);
    }

    /**
     * Méthode pour obtenir un aperçu de l'écriture comptable
     */
    public function getApercuEcriture(): array
    {
        $apercu = [];
        
        if ($this->sens_operation === self::SENS_DEBIT) {
            // Ligne de débit
            if ($this->compte_debit_principal_id) {
                $compte = $this->compteDebitPrincipal;
                $apercu[] = [
                    'type' => 'DEBIT',
                    'compte_id' => $this->compte_debit_principal_id,
                    'compte_code' => $compte ? $compte->code : '',
                    'compte_libelle' => $compte ? $compte->libelle : '',
                    'montant' => $this->montant_total,
                    'libelle' => $this->libelle,
                ];
            }
            
            // Lignes de crédit
            foreach ($this->comptes_credits as $compteCredit) {
                $compte = PlanComptable::find($compteCredit['compte_id']);
                $apercu[] = [
                    'type' => 'CREDIT',
                    'compte_id' => $compteCredit['compte_id'],
                    'compte_code' => $compte ? $compte->code : '',
                    'compte_libelle' => $compte ? $compte->libelle : '',
                    'montant' => $compteCredit['montant'],
                    'libelle' => $this->libelle,
                ];
            }
        } else {
            // Ligne de crédit
            if ($this->compte_credit_principal_id) {
                $compte = $this->compteCreditPrincipal;
                $apercu[] = [
                    'type' => 'CREDIT',
                    'compte_id' => $this->compte_credit_principal_id,
                    'compte_code' => $compte ? $compte->code : '',
                    'compte_libelle' => $compte ? $compte->libelle : '',
                    'montant' => $this->montant_total,
                    'libelle' => $this->libelle,
                ];
            }
            
            // Lignes de débit
            foreach ($this->comptes_debits as $compteDebit) {
                $compte = PlanComptable::find($compteDebit['compte_id']);
                $apercu[] = [
                    'type' => 'DEBIT',
                    'compte_id' => $compteDebit['compte_id'],
                    'compte_code' => $compte ? $compte->code : '',
                    'compte_libelle' => $compte ? $compte->libelle : '',
                    'montant' => $compteDebit['montant'],
                    'libelle' => $this->libelle,
                ];
            }
        }
        
        return $apercu;
    }

    /**
     * Méthode pour vérifier que les mouvements comptables ont été créés
     */
    public function mouvementsComptablesCrees(): bool
    {
        return MouvementComptable::where('od_id', $this->id)->count() > 0;
    }

    /**
     * Méthode pour obtenir les mouvements comptables liés
     */
    public function mouvementsComptables()
    {
        return $this->hasMany(MouvementComptable::class, 'od_id');
    }

    /**
     * Enregistrer le code de validation DG dans le workflow
     */
    public function enregistrerCodeValidationDG(string $code, User $validateur): bool
    {
        try {
            // Trouver la validation DG de cette OD
            $workflowDG = $this->workflow()
                ->where('niveau', self::NIVEAU_DG)
                ->where('decision', 'APPROUVE')
                ->where('user_id', $validateur->id)
                ->first();

            if (!$workflowDG) {
                throw new \Exception('Validation DG non trouvée pour cette OD');
            }

            // Enregistrer le code
            $workflowDG->update([
                'code_a_verifier' => $code
            ]);

            // Enregistrer dans l'historique
            $this->enregistrerHistorique(
                'CODE_VALIDATION_DG_ENVOYE', 
                $this->statut, 
                $this->statut,
                $validateur,
                ['code' => $code]
            );

            return true;

        } catch (\Exception $e) {
            \Log::error('Erreur enregistrement code validation DG', [
                'od_id' => $this->id,
                'validateur_id' => $validateur->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Récupérer le code de validation DG si disponible
     */
    public function getCodeValidationDG(): ?string
    {
        $workflowDG = $this->workflow()
            ->where('niveau', self::NIVEAU_DG)
            ->where('decision', 'APPROUVE')
            ->whereNotNull('code_a_verifier')
            ->first();

        return $workflowDG ? $workflowDG->code_a_verifier : null;
    }
    

}
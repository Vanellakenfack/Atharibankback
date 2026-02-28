<?php

namespace App\Models\frais;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use App\Models\Concerns\UsesDateComptable;

class CalculInteret extends Model
{
    use HasFactory, UsesDateComptable;

    protected $table = 'calculs_interets';
    
    public $timestamps = false;

    protected $fillable = [
        'compte_id',
        'type_compte_id',
        'date_calcul',
        'periode_debut',
        'periode_fin',
        'solde_debut_periode',
        'solde_fin_periode',
        'solde_moyen',
        'nombre_jours',
        'taux_annuel',
        'taux_journalier',
        'interets_bruts',
        'impots',
        'interets_nets',
        'chapitre_interet_id',
        'numero_piece',
        'statut',
        'date_versement',
        'details',
    ];

    protected $casts = [
        'date_calcul' => 'date',
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'date_versement' => 'date',
        'solde_debut_periode' => 'decimal:2',
        'solde_fin_periode' => 'decimal:2',
        'solde_moyen' => 'decimal:2',
        'taux_annuel' => 'decimal:2',
        'taux_journalier' => 'decimal:6',
        'interets_bruts' => 'decimal:2',
        'impots' => 'decimal:2',
        'interets_nets' => 'decimal:2',
        'details' => 'array',
    ];

    // ========== RELATIONS ==========
    
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function typeCompte()
    {
        return $this->belongsTo(TypeCompte::class);
    }

    public function chapitreInteret()
    {
        return $this->belongsTo(PlanComptable::class, 'chapitre_interet_id');
    }

    // ========== SCOPES ==========
    
    public function scopeCalcule($query)
    {
        return $query->where('statut', 'CALCULE');
    }

    public function scopeVerse($query)
    {
        return $query->where('statut', 'VERSE');
    }

    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_calcul', [$dateDebut, $dateFin]);
    }

    public function scopeMois($query, $annee, $mois)
    {
        return $query->whereYear('date_calcul', $annee)
                     ->whereMonth('date_calcul', $mois);
    }

    public function scopeAnnee($query, $annee)
    {
        return $query->whereYear('date_calcul', $annee);
    }

    // ========== MÉTHODES ==========
    
    /**
     * Verser les intérêts au compte
     */
    public function verser(): bool
    {
        if ($this->statut !== 'CALCULE') {
            return false;
        }

        $this->statut = 'VERSE';
        $this->date_versement = now();
        $this->save();

        return true;
    }

    /**
     * Annuler le calcul
     */
    public function annuler(): bool
    {
        if ($this->statut === 'ANNULE') {
            return false;
        }

        $this->statut = 'ANNULE';
        $this->save();

        return true;
    }

    /**
     * Générer un numéro de pièce
     */
    public static function genererNumeroPiece($date): string
    {
        $dateStr = $date->format('Ymd');
        $random = substr(md5(uniqid()), 0, 6);
        
        return "INT-{$dateStr}-{$random}";
    }

    // ========== MÉTHODES STATIQUES - CRÉATION ==========
    
    /**
     * Créer un calcul d'intérêts journalier
     * Gère le scénario du "dépôt tardif"
     */
    public static function creerCalculJournalier(
        Compte $compte,
        TypeCompte $typeCompte,
        \DateTime $date,
        float $solde
    ): ?self {
        // Pas d'intérêts si désactivé ou solde nul/négatif
        if (!$typeCompte->interets_actifs || $solde <= 0) {
            return null;
        }

        // Vérifier si déjà calculé pour ce jour
        $existe = self::where('compte_id', $compte->id)
            ->where('date_calcul', $date)
            ->exists();
            
        if ($existe) {
            return null;
        }

        // Calcul
        $tauxJournalier = $typeCompte->getTauxJournalier();
        $interetsBruts = $solde * $tauxJournalier;
        $interetsNets = $interetsBruts; // Pas d'impôts pour simplifier

        return self::create([
            'compte_id' => $compte->id,
            'type_compte_id' => $typeCompte->id,
            'date_calcul' => $date,
            'periode_debut' => $date,
            'periode_fin' => $date,
            'solde_debut_periode' => $solde,
            'solde_fin_periode' => $solde,
            'solde_moyen' => $solde,
            'nombre_jours' => 1,
            'taux_annuel' => $typeCompte->taux_interet_annuel,
            'taux_journalier' => $tauxJournalier,
            'interets_bruts' => round($interetsBruts, 2),
            'impots' => 0,
            'interets_nets' => round($interetsNets, 2),
            'chapitre_interet_id' => $typeCompte->chapitre_interet_credit_id,
            'numero_piece' => self::genererNumeroPiece($date),
            'statut' => 'CALCULE',
            'details' => [
                'type_calcul' => 'journalier',
                'heure_reference' => $typeCompte->heure_calcul_interet,
            ],
        ]);
    }

    /**
     * Créer un calcul d'intérêts pour une période
     * Ex: Dépôt de 9M le 15 déc → 16 jours d'intérêts (16-31 déc)
     */
    public static function creerCalculPeriode(
        Compte $compte,
        TypeCompte $typeCompte,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        float $solde
    ): ?self {
        // Pas d'intérêts si désactivé ou solde nul/négatif
        if (!$typeCompte->interets_actifs || $solde <= 0) {
            return null;
        }

        // Calcul
        $resultat = $typeCompte->calculerInteretsPeriode($solde, $dateDebut, $dateFin);
        
        return self::create([
            'compte_id' => $compte->id,
            'type_compte_id' => $typeCompte->id,
            'date_calcul' => $dateFin,
            'periode_debut' => $dateDebut,
            'periode_fin' => $dateFin,
            'solde_debut_periode' => $solde,
            'solde_fin_periode' => $solde,
            'solde_moyen' => $solde,
            'nombre_jours' => $resultat['nombre_jours'],
            'taux_annuel' => $resultat['taux_annuel'],
            'taux_journalier' => $resultat['taux_journalier'],
            'interets_bruts' => $resultat['interets_bruts'],
            'impots' => 0,
            'interets_nets' => $resultat['interets_bruts'],
            'chapitre_interet_id' => $typeCompte->chapitre_interet_credit_id,
            'numero_piece' => self::genererNumeroPiece($dateFin),
            'statut' => 'CALCULE',
            'details' => [
                'type_calcul' => 'periode',
                'exemple_depot_tardif' => $dateDebut != $dateFin->modify('first day of'),
            ],
        ]);
    }

    /**
     * Calculer total intérêts pour un compte sur une période
     */
    public static function totalInteretsPeriode(
        int $compteId,
        \DateTime $dateDebut,
        \DateTime $dateFin
    ): float {
        return self::where('compte_id', $compteId)
            ->whereBetween('date_calcul', [$dateDebut, $dateFin])
            ->where('statut', '!=', 'ANNULE')
            ->sum('interets_nets');
    }
}
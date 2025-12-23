<?php

namespace App\Models\compte\frais;

use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegleCalcul extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'regles_calcul';
    
    protected $fillable = [
        'type_compte_id',
        'parametrage_frais_id',
        'code_regle',
        'libelle_regle',
        'type_regle',
        'conditions',
        'declencheurs',
        'methode_calcul',
        'parametres_calcul',
        'periodicite_calcul',
        'jour_calcul',
        'heure_calcul',
        'code_arrête',
        'echelle_arrête',
        'compte_produit_defaut_id',
        'compte_attente_defaut_id',
        'necessite_validation',
        'roles_validation',
        'actif',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'conditions' => 'array',
        'declencheurs' => 'array',
        'parametres_calcul' => 'array',
        'roles_validation' => 'array',
        'necessite_validation' => 'boolean',
        'actif' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Relation: Type de compte
     */
    public function typeCompte()
    {
        return $this->belongsTo(TypeCompte::class);
    }

    /**
     * Relation: Paramétrage frais
     */
    public function parametrageFrais()
    {
        return $this->belongsTo(ParametrageFrais::class);
    }

    /**
     * Relation: Compte produit par défaut
     */
    public function compteProduitDefaut()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_produit_defaut_id');
    }

    /**
     * Relation: Compte attente par défaut
     */
    public function compteAttenteDefaut()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_attente_defaut_id');
    }

    /**
     * Vérifier si la règle est applicable
     */
    public function estApplicable(array $contexte): bool
    {
        if (!$this->actif) {
            return false;
        }

        // Vérifier les dates
        $now = now();
        if ($this->date_debut && $now->lt($this->date_debut)) {
            return false;
        }
        if ($this->date_fin && $now->gt($this->date_fin)) {
            return false;
        }

        // Vérifier les conditions
        if ($this->conditions) {
            foreach ($this->conditions as $condition) {
                if (!$this->verifierCondition($condition, $contexte)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Vérifier une condition spécifique
     */
    private function verifierCondition(array $condition, array $contexte): bool
    {
        $champ = $condition['champ'];
        $operateur = $condition['operateur'];
        $valeur = $condition['valeur'];
        $valeurContexte = $contexte[$champ] ?? null;

        switch ($operateur) {
            case '=':
                return $valeurContexte == $valeur;
            case '!=':
                return $valeurContexte != $valeur;
            case '>':
                return $valeurContexte > $valeur;
            case '>=':
                return $valeurContexte >= $valeur;
            case '<':
                return $valeurContexte < $valeur;
            case '<=':
                return $valeurContexte <= $valeur;
            case 'IN':
                return in_array($valeurContexte, (array)$valeur);
            case 'NOT_IN':
                return !in_array($valeurContexte, (array)$valeur);
            default:
                return false;
        }
    }

    /**
     * Calculer le montant selon la méthode
     */
    public function calculerMontant(array $donnees): float
    {
        $montant = 0;
        
        switch ($this->methode_calcul) {
            case 'FIXE':
                $montant = $this->parametres_calcul['montant_fixe'] ?? 0;
                break;
                
            case 'POURCENTAGE':
                $base = $donnees['base_calcul'] ?? 0;
                $taux = $this->parametres_calcul['taux'] ?? 0;
                $montant = $base * ($taux / 100);
                break;
                
            case 'ECHELLE':
                $base = $donnees['base_calcul'] ?? 0;
                $echelles = $this->parametres_calcul['echelles'] ?? [];
                
                foreach ($echelles as $echelle) {
                    if ($base >= $echelle['min'] && $base <= $echelle['max']) {
                        $montant = $echelle['montant'];
                        break;
                    }
                }
                break;
                
            case 'SEUIL':
                $base = $donnees['base_calcul'] ?? 0;
                $seuil = $this->parametres_calcul['seuil'] ?? 0;
                $montant_atteint = $this->parametres_calcul['montant_atteint'] ?? 0;
                $montant_non_atteint = $this->parametres_calcul['montant_non_atteint'] ?? 0;
                
                $montant = ($base >= $seuil) ? $montant_atteint : $montant_non_atteint;
                break;
        }
        
        return round($montant, 2);
    }
}
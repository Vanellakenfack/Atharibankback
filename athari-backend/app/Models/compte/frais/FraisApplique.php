<?php

namespace App\Models\compte\frais;

use App\Models\compte\Compte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FraisApplique extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'frais_appliques';
    
    protected $fillable = [
        'compte_id',
        'parametrage_frais_id',
        'date_application',
        'date_prelevement',
        'montant_calcule',
        'montant_preleve',
        'base_calcul_valeur',
        'methode_calcul',
        'statut',
        'operation_id',
        'user_id',
        'compte_produit_id',
        'compte_client_id',
        'date_comptabilisation',
        'reference_comptable',
        'erreur_message',
        'tentatives',
    ];

    protected $casts = [
        'date_application' => 'date',
        'date_prelevement' => 'date',
        'date_comptabilisation' => 'datetime',
    ];

    /**
     * Relation: Compte associé
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Relation: Paramétrage du frais
     */
    public function parametrageFrais()
    {
        return $this->belongsTo(ParametrageFrais::class);
    }

    /**
     * Relation: Compte produit
     */
    public function compteProduit()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_produit_id');
    }

    /**
     * Relation: Compte client
     */
    public function compteClient()
    {
        return $this->belongsTo(PlanComptable::class, 'compte_client_id');
    }

    /**
     * Relation: Mouvements comptables
     */
    public function mouvements()
    {
        return $this->hasMany(MouvementComptable::class);
    }

    /**
     * Scope: Frais à prélever
     */
    public function scopeAPrelever($query)
    {
        return $query->where('statut', 'A_PRELEVER')
            ->orWhere('statut', 'EN_ATTENTE');
    }

    /**
     * Scope: Frais par période
     */
    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_application', [$debut, $fin]);
    }

    /**
     * Appliquer le frais sur le compte
     */
    public function appliquer(): bool
    {
        try {
            $compte = $this->compte;
            $montant = $this->montant_calcule;
            
            // Vérifier si le solde est suffisant
            if ($compte->solde >= $montant) {
                // Débiter le compte client
                $compte->solde -= $montant;
                $compte->save();
                
                $this->statut = 'PRELEVE';
                $this->montant_preleve = $montant;
                $this->date_prelevement = now();
                
                // Générer le mouvement comptable
                $this->genererMouvementComptable();
            } else {
                // Mettre en attente
                $this->statut = 'EN_ATTENTE';
                
                // Générer une écriture dans le compte d'attente
                $this->genererMouvementAttente();
            }
            
            $this->save();
            return true;
            
        } catch (\Exception $e) {
            $this->erreur_message = $e->getMessage();
            $this->tentatives++;
            $this->save();
            return false;
        }
    }

    /**
     * Générer le mouvement comptable
     */
    private function genererMouvementComptable()
    {
        MouvementComptable::create([
            'frais_applique_id' => $this->id,
            'compte_id' => $this->compte_id,
            'date_mouvement' => now(),
            'libelle_mouvement' => $this->parametrageFrais->libelle_frais,
            'description' => "Frais appliqué sur compte {$this->compte->numero_compte}",
            'compte_debit_id' => $this->compte_client_id,
            'compte_credit_id' => $this->compte_produit_id,
            'montant_debit' => $this->montant_calcule,
            'montant_credit' => $this->montant_calcule,
            'journal' => 'FRAIS',
            'numero_piece' => 'FRAIS-' . $this->id,
            'reference_operation' => $this->reference_comptable,
            'statut' => 'COMPTABILISE',
            'date_validation' => now(),
        ]);
        
        $this->date_comptabilisation = now();
    }

    /**
     * Générer mouvement d'attente
     */
    private function genererMouvementAttente()
    {
        $compteAttente = $this->parametrageFrais->compte_attente_id;
        
        if ($compteAttente) {
            MouvementComptable::create([
                'frais_applique_id' => $this->id,
                'compte_id' => $this->compte_id,
                'date_mouvement' => now(),
                'libelle_mouvement' => $this->parametrageFrais->libelle_frais . ' (En attente)',
                'description' => "Frais en attente - Solde insuffisant",
                'compte_debit_id' => $compteAttente,
                'compte_credit_id' => $this->compte_produit_id,
                'montant_debit' => $this->montant_calcule,
                'montant_credit' => $this->montant_calcule,
                'journal' => 'FRAIS',
                'numero_piece' => 'ATT-' . $this->id,
                'reference_operation' => $this->reference_comptable,
                'statut' => 'COMPTABILISE',
            ]);
        }
    }
}
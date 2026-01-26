<?php
// app/Models/Gestionnaire.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gestionnaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gestionnaire_code',
        'gestionnaire_nom',
        'gestionnaire_prenom',
        'telephone',
        'email',
        'agence_id',
        'etat',
        'cni_recto',
        'cni_verso',
        'plan_localisation_domicile',
        'signature',
        'ville',
        'quartier'
    ];

    protected $casts = [
        'etat' => 'string'
    ];

    protected $appends = [
        'nom_complet',
        'adresse_complete',
        'cni_recto_url',
        'cni_verso_url',
        'plan_localisation_domicile_url',
        'signature_url'
    ];

    // Accessors pour les URLs complètes des images
    public function getCniRectoUrlAttribute()
    {
        return $this->cni_recto ? asset('storage/' . $this->cni_recto) : null;
    }

    public function getCniVersoUrlAttribute()
    {
        return $this->cni_verso ? asset('storage/' . $this->cni_verso) : null;
    }

    public function getPlanLocalisationDomicileUrlAttribute()
    {
        return $this->plan_localisation_domicile ? asset('storage/' . $this->plan_localisation_domicile) : null;
    }

    public function getSignatureUrlAttribute()
    {
        return $this->signature ? asset('storage/' . $this->signature) : null;
    }

    public function agence()
    {
        return $this->belongsTo(Agency::class, 'agence_id');
    }

    public function comptes()
    {
        return $this->belongsToMany(Compte::class, 'gestionnaires_comptes')
                    ->withPivot('date_affectation', 'est_gestionnaire_principal')
                    ->withTimestamps();
    }

    public function scopeActifs($query)
    {
        return $query->where('etat', 'present');
    }

    public function scopeSupprimes($query)
    {
        return $query->where('etat', 'supprime');
    }

    public function marquerCommeSupprime()
    {
        $this->etat = 'supprime';
        $this->save();
        $this->delete();
    }

    public function restaurer()
    {
        $this->etat = 'present';
        $this->save();
        $this->restore();
    }

    public function getNomCompletAttribute()
    {
        return $this->gestionnaire_nom . ' ' . $this->gestionnaire_prenom;
    }

    // Méthode pour obtenir l'adresse complète
    public function getAdresseCompleteAttribute()
    {
        $adresse = '';
        if ($this->quartier) {
            $adresse .= $this->quartier;
        }
        if ($this->ville) {
            $adresse .= $adresse ? ', ' . $this->ville : $this->ville;
        }
        return $adresse ?: 'Non spécifiée';
    }

    // Méthode statique pour générer le code automatiquement
    public static function genererCode()
    {
        // Récupérer le dernier code existant
        $dernierGestionnaire = self::withTrashed()
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$dernierGestionnaire) {
            // Si aucun gestionnaire n'existe, commencer à G001
            return 'G001';
        }
        
        // Extraire le numéro du dernier code
        $dernierCode = $dernierGestionnaire->gestionnaire_code;
        
        // Vérifier si le code suit le format GXXX
        if (preg_match('/^G(\d{3})$/', $dernierCode, $matches)) {
            $numero = (int) $matches[1];
            $nouveauNumero = $numero + 1;
        } else {
            // Si le format n'est pas correct, recommencer à G001
            // Mais d'abord vérifier s'il y a d'autres codes valides
            $dernierCodeValide = self::withTrashed()
                ->where('gestionnaire_code', 'LIKE', 'G%')
                ->whereRaw('LENGTH(gestionnaire_code) = 4')
                ->orderBy('gestionnaire_code', 'desc')
                ->first();
            
            if ($dernierCodeValide && preg_match('/^G(\d{3})$/', $dernierCodeValide->gestionnaire_code, $matches)) {
                $numero = (int) $matches[1];
                $nouveauNumero = $numero + 1;
            } else {
                // Aucun code valide trouvé, commencer à G001
                return 'G001';
            }
        }
        
        // Formater le nouveau numéro avec 3 chiffres
        return 'G' . str_pad($nouveauNumero, 3, '0', STR_PAD_LEFT);
    }
}
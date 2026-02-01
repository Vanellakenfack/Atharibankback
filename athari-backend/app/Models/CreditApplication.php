<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\client\Client;

class CreditApplication extends Model
{
    use HasFactory;

   protected $fillable = [
        // Informations d'identification
        'reference',
        'num_dossier',
        'client_id',
        'client_name',
        'credit_product_id',
        'type_credit',
        
        // Paramètres du crédit
        'montant',
        'duree',
        'taux_interet',
        'observation', // Ajouté car présent dans le frontend
        
        // Situation financière (Nouveaux champs du formulaire)
        'source_revenus',
        'revenus_mensuels',
        'autres_revenus',
        'depenses_mensuelles',
        'montant_dettes',
        'description_dettes',
        'nom_banque',
        'numero_compte',
        
        // Suivi et Statut
        'statut',
        'code_mise_en_place',
        'note_credit',
        'created_by',
        
        // Chemins des fichiers (Uploads)
        'demande_credit',
        'plan_epargne',
        'document_identite',
        'photos_4x4',
        'plan_localisation',
        'facture_electricite',
        'casier_judiciaire',
        'historique_compte',
    ];

    protected $attributes = [
        'statut' => 'SOUMIS', // Valeur par défaut
    ];

    /* ===================== RELATIONS ===================== */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function avis()
{
    return $this->hasMany(Avis::class);
}


    // CORRECTION : Relation vers CreditProduct (table créée par votre migration)
    public function product()
    {
        return $this->belongsTo(CreditProduct::class, 'credit_product_id');
    }

    public function approvals()
    {
        return $this->hasMany(CreditApproval::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ===================== SCOPES ===================== */

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'SOUMIS');
    }

    public function scopeApprouves($query)
    {
        return $query->where('statut', 'APPROUVE');
    }

    public function scopeRejetes($query)
    {
        return $query->where('statut', 'REJETE');
    }

    /* ===================== LOGIQUE DE STATUT ===================== */

    public function updateStatus($newStatus, $observation = null)
    {
        $oldStatus = $this->statut;
        $this->update(['statut' => $newStatus]);

        // Si vous avez une table d'historique
        // return $this->history()->create([...]);
    }

    /* ===================== ACCESSEURS ===================== */

    public function getStatutLabelAttribute()
    {
        $statuts = [
            'SOUMIS' => 'Soumis',
            'EN_COURS' => 'En cours d\'étude',
            'APPROUVE' => 'Approuvé',
            'REJETE' => 'Rejeté',
            'MISE_EN_PLACE' => 'Mis en place',
        ];

        return $statuts[$this->statut] ?? $this->statut;
    }

    public function getMontantFormattedAttribute()
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }
}
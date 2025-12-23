<?php

namespace App\Models\chapitre;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\compte\Compte;

class PlanComptable extends Model
{
    protected $table = 'plan_comptable';

    protected $fillable = [
        'categorie_id',
        'code',
        'libelle',
        'nature_solde',
        'est_actif'
    ];

    /**
     * Récupère la catégorie parente pour accéder au type_compte (ACTIF, PASSIF...)
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieComptable::class, 'categorie_id');
    }

    /**
     * Scope pour ne récupérer que les comptes utilisables
     */
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

     /**
     * Relation: Plan comptable peut avoir plusieurs comptes
     */
    public function comptes()
    {
        return $this->hasMany(Compte::class, 'plan_comptable_id');
    }

    /**
     * Scope: Filtrer par nature de solde
     */
    public function scopeNatureSolde($query, string $nature)
    {
        return $query->where('nature_solde', $nature);
    }

    /**
     * Scope: Filtrer par catégorie
     */
    public function scopeParCategorie($query, int $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }

    /**
     * Scope: Rechercher par code ou libellé
     */
    public function scopeRecherche($query, string $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('code', 'like', "%{$terme}%")
              ->orWhere('libelle', 'like', "%{$terme}%");
        });
    }

    /**
     * Obtenir le code et libellé formatés
     */
    public function getCodeLibelleAttribute(): string
    {
        return "{$this->code} - {$this->libelle}";
    }
}
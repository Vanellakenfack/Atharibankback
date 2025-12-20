<?php
namespace App\Models\chapitre;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorieComptable extends Model
{
    protected $table = 'categories_comptables';

    protected $fillable = [
        'code',
        'libelle',
        'niveau',
        'parent_id',
        'type_compte'
    ];

    /**
     * Récupère les comptes de détail rattachés à cette catégorie.
     */
    public function comptes(): HasMany
    {
        return $this->hasMany(PlanComptable::class, 'categorie_id');
    }

    /**
     * Relation pour la hiérarchie (Parent)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CategorieComptable::class, 'parent_id');
    }

    /**
     * Relation pour les sous-catégories (Enfants)
     */
    public function enfants(): HasMany
    {
        return $this->hasMany(CategorieComptable::class, 'parent_id');
    }
}
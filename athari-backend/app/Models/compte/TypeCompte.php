<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle représentant un type de compte bancaire
 * Référence: NOMENCLATURE DES COMPTES AUDACE VRAI
 * 
 * @property int $id
 * @property string $code Code à 2 chiffres (01-30)
 * @property string $libelle Libellé du compte
 * @property string|null $description Description détaillée
 * @property bool $est_mata Compte MATA (nécessite rubriques)
 * @property bool $necessite_duree Nécessite durée de blocage
 * @property bool $est_islamique Compte islamique
 * @property bool $actif Type actif/inactif
 */
class TypeCompte extends Model
{
    use HasFactory;

    protected $table = 'types_comptes';

    protected $fillable = [
        'code',
        'libelle',
        'est_mata',
        'necessite_duree',
        'est_islamique',
        'actif',
    ];

    protected $casts = [
        'est_mata' => 'boolean',
        'necessite_duree' => 'boolean',
        'est_islamique' => 'boolean',
        'actif' => 'boolean',
    ];

    /**
     * Relation: Type de compte peut avoir plusieurs comptes
     */
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    /**
     * Scope: Types actifs uniquement
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Scope: Comptes MATA
     */
    public function scopeMata($query)
    {
        return $query->where('est_mata', true);
    }

    /**
     * Obtenir les rubriques MATA disponibles
     */
    public static function getRubriquesMata(): array
    {
        return [
            'SANTE' => 'Santé',
            'BUSINESS' => 'Business',
            'FETE' => 'Fête',
            'FOURNITURE' => 'Fourniture',
            'IMMO' => 'Immobilier',
            'SCOLARITE' => 'Scolarité',
        ];
    }

    /**
     * Obtenir les durées de blocage disponibles (3-12 mois)
     */
    public static function getDureesBlocage(): array
    {
        return range(3, 12);
    }
}
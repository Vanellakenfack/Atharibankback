<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle représentant un mandataire de compte
 * 
 * @property int $id
 * @property int $compte_id ID du compte
 * @property int $ordre Ordre du mandataire (1 ou 2)
 * @property string $sexe Sexe (masculin/feminin)
 * @property string $nom Nom
 * @property string $prenom Prénom
 * @property \DateTime $date_naissance Date de naissance
 * @property string $lieu_naissance Lieu de naissance
 * @property string $telephone Téléphone
 * @property string $adresse Adresse
 * @property string $nationalite Nationalité
 * @property string $profession Profession
 * @property string|null $nom_jeune_fille_mere Nom jeune fille mère
 * @property string $numero_cni Numéro CNI
 * @property string $situation_familiale Situation familiale
 * @property string|null $nom_conjoint Nom conjoint
 * @property \DateTime|null $date_naissance_conjoint Date naissance conjoint
 * @property string|null $lieu_naissance_conjoint Lieu naissance conjoint
 * @property string|null $cni_conjoint CNI conjoint
 * @property string|null $signature_path Chemin signature
 */
class Mandataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'ordre',
        'sexe',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'telephone',
        'adresse',
        'nationalite',
        'profession',
        'nom_jeune_fille_mere',
        'numero_cni',
        'situation_familiale',
        'nom_conjoint',
        'date_naissance_conjoint',
        'lieu_naissance_conjoint',
        'cni_conjoint',
        'signature_path',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_naissance_conjoint' => 'date',
    ];

    /**
     * Relation: Mandataire appartient à un compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Obtenir le nom complet du mandataire
     */
    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' ' . $this->prenom;
    }

    /**
     * Vérifier si le mandataire est marié
     */
    public function estMarie(): bool
    {
        return $this->situation_familiale === 'marie';
    }

    /**
     * Vérifier si c'est le mandataire principal
     */
    public function estPrincipal(): bool
    {
        return $this->ordre === 1;
    }
}
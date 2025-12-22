<?php

namespace App\Models\compte;
use App\Models\chapitre\PlanComptable;
use App\Models\client\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_compte',
        'client_id',
        'type_compte_id',
        'plan_comptable_id', // MODIFICATION: Remplace chapitre_comptable_id
        'devise',
        'gestionnaire_nom',
        'gestionnaire_prenom',
        'gestionnaire_code',
        'rubriques_mata',
        'duree_blocage_mois',
        'statut',
        'solde',
        'notice_acceptee',
        'date_acceptation_notice',
        'signature_path',
        'date_ouverture',
        'date_cloture',
        'observations',
    ];

    protected $casts = [
        'rubriques_mata' => 'array',
        'solde' => 'decimal:2',
        'notice_acceptee' => 'boolean',
        'date_acceptation_notice' => 'datetime',
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
    ];

    /**
     * Relation: Compte appartient à un client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation: Compte a un type
     */
    public function typeCompte()
    {
        return $this->belongsTo(TypeCompte::class);
    }

    /**
     * Relation: Compte lié à un plan comptable
     * MODIFICATION: Utilise PlanComptable au lieu de ChapitreComptable
     */
    public function planComptable()
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_id');
    }

    /**
     * Relation: Compte peut avoir plusieurs mandataires (max 2)
     */
    public function mandataires()
    {
        return $this->hasMany(Mandataire::class)->orderBy('ordre');
    }

    /**
     * Relation: Compte peut avoir plusieurs documents
     */
    public function documents()
    {
        return $this->hasMany(DocumentCompte::class);
    }

    /**
     * Scope: Comptes actifs uniquement
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope: Filtrer par devise
     */
    public function scopeDevise($query, $devise)
    {
        return $query->where('devise', $devise);
    }

    /**
     * Scope: Filtrer par nature de solde du plan comptable
     */
    public function scopeParNatureSolde($query, string $nature)
    {
        return $query->whereHas('planComptable', function ($q) use ($nature) {
            $q->where('nature_solde', $nature);
        });
    }

    /**
     * Accessor: Formater le numéro de compte
     */
    public function getNumeroCompteFormatteAttribute()
    {
        // Format: XXX-XXXXXX-XX-X-X (Agence-Client-Type-Ordre-Clé)
        $num = $this->numero_compte;
        return substr($num, 0, 3) . '-' . 
               substr($num, 3, 6) . '-' . 
               substr($num, 9, 2) . '-' . 
               substr($num, 11, 1) . '-' . 
               substr($num, 12, 1);
    }

    /**
     * Vérifier si le compte est un compte MATA
     */
    public function estCompteMata(): bool
    {
        return $this->typeCompte->est_mata;
    }

    /**
     * Vérifier si le compte nécessite une durée de blocage
     */
    public function necessiteDuree(): bool
    {
        return $this->typeCompte->necessite_duree;
    }

    /**
     * Obtenir le solde formaté avec devise
     */
    public function getSoldeFormatteAttribute(): string
    {
        return number_format($this->solde, 2, ',', ' ') . ' ' . $this->devise;
    }

    /**
     * Obtenir les informations comptables complètes
     */
    public function getInfosComptablesAttribute(): array
    {
        return [
            'plan_code' => $this->planComptable->code,
            'plan_libelle' => $this->planComptable->libelle,
            'nature_solde' => $this->planComptable->nature_solde,
            'categorie' => $this->planComptable->categorie->libelle ?? null,
        ];
    }
}
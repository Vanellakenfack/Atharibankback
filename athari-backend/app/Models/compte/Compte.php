<?php

namespace App\Models\compte;
use App\Models\chapitre\PlanComptable;
use App\Models\client\Client;
use App\Models\frais\MouvementRubriqueMata;
use App\Services\Frais\GestionRubriqueMataService;
use App\Models\User;

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
        'gestionnaire_code',
        'gestionnaire_nom',    // DOIT ÊTRE PRÉSENT
       'gestionnaire_prenom', // DOIT ÊTRE PRÉSENT
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
        'motif_rejet', // <--- DOIT ÊTRE ICI

        'est_en_opposition',
        'date_rejet',
    'rejete_par',
    'validation_chef_agence',
    'validation_juridique',
    'est_en_opposition',
    'ca_id',
    'juriste_id',
    'dossier_complet',
    'checklist_juridique',
    'date_validation_juridique'
    ];

    protected $casts = [
        'rubriques_mata' => 'array',
        'solde' => 'decimal:2',
        'notice_acceptee' => 'boolean',
        'date_acceptation_notice' => 'datetime',
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
        'validation_chef_agence' => 'boolean',
    'validation_juridique' => 'boolean',
    'est_en_opposition' => 'boolean',

    'checklist_juridique' => 'array',
        
        'dossier_complet' => 'boolean',
        'est_en_opposition' => 'boolean',
        'date_validation_juridique' => 'datetime',
        'date_rejet' => 'datetime',
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

     /**
     * Relation avec les mouvements des rubriques MATA
     */
    public function mouvementsRubriquesMata()
    {
        return $this->hasMany(MouvementRubriqueMata::class);
    }

    /**
     * Obtenir le récapitulatif des rubriques MATA
     */
    public function getRecapitulatifRubriquesAttribute()
    {
        if (!$this->typeCompte->est_mata) {
            return null;
        }

        $service = app(GestionRubriqueMataService::class);
        return $service->getRecapitulatifRubriques($this);
    }

    /**
     * Obtenir le solde d'une rubrique spécifique
     */
    public function getSoldeRubrique($rubrique)
    {
        return MouvementRubriqueMata::getSoldeRubrique($this->id, $rubrique);
    }
    public function mouvements()
    {
        // Un compte possède plusieurs mouvements
        // 'compte_id' est la clé étrangère dans la table mouvements_comptables
        return $this->hasMany(MouvementComptable::class, 'compte_id');
    }

    protected static function booted()
{
    static::creating(function ($compte) {
        if (!$compte->created_by) {
            $compte->created_by = auth()->id() ;
        }
    });
}

// Cette méthode permet de récupérer l'objet User complet via created_by
// app/Models/compte/Compte.php

public function utilisateur_createur()
{
    // On lie created_by à l'id de la table users
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}

public function chefAgence() {
        return $this->belongsTo(User::class, 'ca_id');
    }

    public function juriste() {
        return $this->belongsTo(User::class, 'juriste_id');
    }

    
}

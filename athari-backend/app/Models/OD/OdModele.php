<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Compte\MouvementComptable;
use App\Models\Agency;
use App\Models\User;
Use App\Models\compte\Compte;
class OdModele extends Model
{
    use SoftDeletes;

    protected $table = 'od_modeles';
    
    protected $fillable = [
        'code',
        'nom',
        'description',
        'type_operation',
        'code_operation',
        'est_actif',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    /**
     * Relation avec les lignes du modèle
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(OdModeleLigne::class, 'modele_id');
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur modificateur
     */
    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Appliquer le modèle pour créer une OD
     */
    public function appliquer(array $donnees, User $saisiPar): OperationDiverse
    {
        $od = OperationDiverse::create([
            'agence_id' => $donnees['agence_id'],
            'date_operation' => $donnees['date_operation'] ?? now(),
            'date_valeur' => $donnees['date_valeur'] ?? now(),
            'date_comptable' => $donnees['date_comptable'] ?? now(),
            'type_operation' => $this->type_operation,
            'code_operation' => $this->code_operation,
            'libelle' => $donnees['libelle'] ?? $this->nom,
            'description' => $donnees['description'] ?? $this->description,
            'montant' => $donnees['montant'],
            'devise' => $donnees['devise'] ?? 'FCFA',
            'numero_guichet' => $donnees['numero_guichet'],
            'numero_piece' => $donnees['numero_piece'] ?? OperationDiverse::generateNumeroPiece(),
            'modele_id' => $this->id,
            'saisi_par' => $saisiPar->id,
            'statut' => OperationDiverse::STATUT_SAISI,
            'justificatif_type' => $donnees['justificatif_type'] ?? null,
            'justificatif_numero' => $donnees['justificatif_numero'] ?? null,
            'justificatif_date' => $donnees['justificatif_date'] ?? now(),
        ]);

        // Créer les lignes comptables à partir du modèle
        foreach ($this->lignes as $ligne) {
            $montant = $ligne->montant_fixe ?? $donnees['montant'];
            
            if ($ligne->sens === 'D') {
                $od->update(['compte_debit_id' => $ligne->compte_id]);
            } else {
                $od->update(['compte_credit_id' => $ligne->compte_id]);
            }
        }

        $od->enregistrerHistorique('CREATION_PAR_MODELE', null, OperationDiverse::STATUT_SAISI);

        return $od;
    }

    /**
     * Vérifier si le modèle est équilibré
     */
    public function estEquilibre(): bool
    {
        $totalDebit = $this->lignes()->where('sens', 'D')->sum('montant_fixe');
        $totalCredit = $this->lignes()->where('sens', 'C')->sum('montant_fixe');
        
        return $totalDebit == $totalCredit;
    }

    /**
     * Scope pour les modèles actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    /**
     * Scope par type d'opération
     */
    public function scopeParType($query, $type)
    {
        return $query->where('type_operation', $type);
    }
}
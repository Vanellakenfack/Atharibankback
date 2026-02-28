<?php

namespace App\Models\OD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Compte\MouvementComptable;
use App\Models\Agency;
use App\Models\User;
Use App\Models\compte\Compte;
use App\Models\Concerns\UsesDateComptable;

class OdHistorique extends Model
{
    use UsesDateComptable;
    protected $table = 'od_historique';
    
    public $timestamps = true;
    
    protected $fillable = [
        'operation_diverse_id',
        'user_id',
        'action',
        'ancien_statut',
        'nouveau_statut',
        'description',
        'donnees_modifiees',
        'ip_address',
        'user_agent',
        'date_comptable',
        'jour_comptable_id'
    ];

    protected $casts = [
        'donnees_modifiees' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relation avec l'OD
     */
    public function operationDiverse(): BelongsTo
    {
        return $this->belongsTo(OperationDiverse::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formater les données modifiées pour l'affichage
     */
    public function getDonneesModifieesFormateesAttribute(): string
    {
        if (empty($this->donnees_modifiees)) {
            return 'Aucune donnée modifiée';
        }

        $formatted = [];
        foreach ($this->donnees_modifiees as $champ => $valeurs) {
            $formatted[] = sprintf(
                "%s: %s → %s",
                $this->getLibelleChamp($champ),
                $this->formatValeur($valeurs['ancien']),
                $this->formatValeur($valeurs['nouveau'])
            );
        }

        return implode(', ', $formatted);
    }

    /**
     * Obtenir le libellé d'un champ
     */
    private function getLibelleChamp(string $champ): string
    {
        $libelles = [
            'montant' => 'Montant',
            'libelle' => 'Libellé',
            'description' => 'Description',
            'statut' => 'Statut',
            'justificatif_type' => 'Type de justificatif',
            'est_urgence' => 'Urgence',
        ];

        return $libelles[$champ] ?? ucfirst(str_replace('_', ' ', $champ));
    }

    /**
     * Formater une valeur
     */
    private function formatValeur($valeur): string
    {
        if ($valeur === null) {
            return '(vide)';
        }

        if (is_bool($valeur)) {
            return $valeur ? 'Oui' : 'Non';
        }

        return (string) $valeur;
    }
}
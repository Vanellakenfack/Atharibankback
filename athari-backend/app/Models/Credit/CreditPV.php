<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CreditPV extends Model
{
    use HasFactory;

    // Précise le nom exact de la table
    protected $table = 'credit_pvs';

    protected $fillable = [
        'credit_application_id',
        'numero_pv',
        'fichier_pdf',
        'date_pv',
        'lieu_pv',
        'montant_approuvee', // Mis à jour pour correspondre à ta base de données
        'resume_decision',
        'duree_approuvee',
        'nom_garantie',
        'details_avis_membres',
        'genere_par',
        'statut'
    ];

    /**
     * Cast des attributs.
     * Cela permet de manipuler 'details_avis_membres' comme un tableau PHP directement.
     */
    protected $casts = [
        'date_pv' => 'datetime',
        'details_avis_membres' => 'array',
        'montant_approuvee' => 'decimal:2',
    ];

    /**
     * Relation avec la demande de crédit
     */
    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class, 'credit_application_id');
    }

    /**
     * Relation avec l'utilisateur (le membre du comité) qui a généré le PV
     */
    public function generateur()
    {
        return $this->belongsTo(User::class, 'genere_par');
    }
    public function avisCredits()
{
    return $this->hasMany(AvisCredit::class, 'credit_application_id');
}
}
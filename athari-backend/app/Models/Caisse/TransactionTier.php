<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionTier extends Model
{
    use HasFactory;

    // Nom de la table créée par votre migration
    protected $table = 'transaction_tiers';

    // Champs autorisés pour le remplissage de masse (Mass Assignment)
    protected $fillable = [
        'transaction_id',
        'nom_complet',
        'type_piece',
        'numero_piece'
    ];

    /**
     * Relation inverse : Un enregistrement de tiers appartient à une transaction de caisse.
     */
    public function transaction()
    {
        return $this->belongsTo(CaisseTransaction::class, 'transaction_id');
    }
}
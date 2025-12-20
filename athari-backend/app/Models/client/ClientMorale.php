<?php
namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMorale extends Model
{
    protected $table = 'clients_morales';

    protected $fillable = [
        'client_id', 'raison_sociale', 'sigle', 'forme_juridique', 
        'rccm', 'nui', 'nom_gerant', 'fonction_gerant'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
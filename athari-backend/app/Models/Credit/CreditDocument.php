<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'type_document',
        'fichier',
        'statut',
        'commentaire',
        'uploaded_by'
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_application_id',
        'type_document',
        'fichier',
        'uploaded_by',
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

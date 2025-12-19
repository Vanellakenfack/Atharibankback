<?php
// app/Models/AccountDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentsCompte extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'uploaded_by',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille',
        'is_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->chemin_fichier);
    }

    public function getTailleFormatteeAttribute(): string
    {
        $bytes = $this->taille;
        $units = ['o', 'Ko', 'Mo', 'Go'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
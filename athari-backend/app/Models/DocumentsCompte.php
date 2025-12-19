<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentsCompte extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'document_type',
        'document_name',
        'file_path',
        'file_type',
        'file_size',
        'is_verified',
        'verified_by',
        'verified_at',
        'uploaded_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public const DOCUMENT_TYPES = [
        'cni' => 'Carte Nationale d\'Identité',
        'passport' => 'Passeport',
        'justificatif_domicile' => 'Justificatif de domicile',
        'photo' => 'Photo d\'identité',
        'signature' => 'Spécimen de signature',
        'statuts' => 'Statuts de l\'entreprise',
        'rccm' => 'Registre de Commerce',
        'niu' => 'Numéro d\'Identification Unique',
        'autres' => 'Autres documents',
    ];

    public const MAX_FILE_SIZE = 8 * 1024 * 1024; // 8 Mo

    public const ALLOWED_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verify(int $userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }
}
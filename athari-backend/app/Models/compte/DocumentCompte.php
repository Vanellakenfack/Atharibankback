<?php

namespace App\Models\compte;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Modèle représentant un document associé à un compte
 *
 * @property int $id
 * @property int $compte_id ID du compte
 * @property string $type_document Type de document
 * @property string $nom_fichier Nom original
 * @property string $chemin_fichier Chemin stockage
 * @property string $extension Extension
 * @property int $taille_octets Taille en octets
 * @property string $mime_type Type MIME
 * @property string|null $description Description
 * @property int $uploaded_by ID utilisateur upload
 */
class DocumentCompte extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents_compte';

    protected $fillable = [
        'compte_id',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'extension',
        'taille_octets',
        'mime_type',
        'description',
        'uploaded_by',
    ];

    /**
     * Types de documents autorisés
     */
    const TYPES_DOCUMENTS = [
        'CNI_CLIENT' => 'CNI du client',
        'JUSTIFICATIF_DOMICILE' => 'Justificatif de domicile',
        'CNI_MANDATAIRE_1' => 'CNI Mandataire 1',
        'CNI_MANDATAIRE_2' => 'CNI Mandataire 2',
        'CNI_CONJOINT_1' => 'CNI Conjoint Mandataire 1',
        'CNI_CONJOINT_2' => 'CNI Conjoint Mandataire 2',
        'AUTRE' => 'Autre document',
    ];

    /**
     * Extensions autorisées
     */
    const EXTENSIONS_AUTORISEES = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Taille maximale (10 MB en octets)
     */
    const TAILLE_MAX = 10485760; // 10 * 1024 * 1024

    /**
     * Relation: Document appartient à un compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Relation: Document uploadé par un utilisateur
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtenir la taille formatée
     */
    public function getTailleFormateeAttribute(): string
    {
        $taille = $this->taille_octets;

        if ($taille < 1024) {
            return $taille . ' octets';
        } elseif ($taille < 1048576) {
            return round($taille / 1024, 2) . ' Ko';
        } else {
            return round($taille / 1048576, 2) . ' Mo';
        }
    }

    /**
     * Obtenir l'URL du document
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->chemin_fichier);
    }

    /**
     * Supprimer le fichier physique
     */
    public function supprimerFichier(): bool
    {
        if (Storage::exists($this->chemin_fichier)) {
            return Storage::delete($this->chemin_fichier);
        }
        return false;
    }

    /**
     * Boot: Supprimer automatiquement le fichier à la suppression du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->supprimerFichier();
        });
    }
}

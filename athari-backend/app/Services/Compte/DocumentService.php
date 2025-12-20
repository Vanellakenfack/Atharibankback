<?php

namespace App\Services\Compte;

use App\Models\compte\DocumentCompte;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service de gestion des documents de compte
 * Gère l'upload, stockage et suppression des documents
 */
class DocumentService
{
    /**
     * Télécharger et enregistrer un document
     * 
     * @param int $compteId ID du compte
     * @param UploadedFile $fichier Fichier uploadé
     * @param string $typeDocument Type de document
     * @param string|null $description Description optionnelle
     * @param int $uploadedBy ID de l'utilisateur
     * @return DocumentCompte Document créé
     */
    public function uploadDocument(
        int $compteId,
        UploadedFile $fichier,
        string $typeDocument,
        ?string $description = null,
        int $uploadedBy
    ): DocumentCompte {
        // Valider la taille
        if ($fichier->getSize() > DocumentCompte::TAILLE_MAX) {
            throw new \Exception('Le fichier ne doit pas dépasser 10 MB.');
        }
        
        // Valider l'extension
        $extension = strtolower($fichier->getClientOriginalExtension());
        if (!in_array($extension, DocumentCompte::EXTENSIONS_AUTORISEES)) {
            throw new \Exception('Format de fichier non autorisé. Formats acceptés: ' . implode(', ', DocumentCompte::EXTENSIONS_AUTORISEES));
        }
        
        // Générer un nom unique
        $nomFichier = Str::uuid() . '.' . $extension;
        
        // Stocker le fichier
        $chemin = $fichier->storeAs('documents/comptes/' . $compteId, $nomFichier, 'private');
        
        // Créer l'enregistrement
        return DocumentCompte::create([
            'compte_id' => $compteId,
            'type_document' => $typeDocument,
            'nom_fichier' => $fichier->getClientOriginalName(),
            'chemin_fichier' => $chemin,
            'extension' => $extension,
            'taille_octets' => $fichier->getSize(),
            'mime_type' => $fichier->getMimeType(),
            'description' => $description,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * Supprimer un document
     * 
     * @param int $documentId ID du document
     * @return bool Succès de la suppression
     */
    public function supprimerDocument(int $documentId): bool
    {
        $document = DocumentCompte::findOrFail($documentId);
        
        return $document->delete();
    }

    /**
     * Télécharger un document
     * 
     * @param int $documentId ID du document
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function telechargerDocument(int $documentId)
    {
        $document = DocumentCompte::findOrFail($documentId);
        
        return Storage::download($document->chemin_fichier, $document->nom_fichier);
    }
}
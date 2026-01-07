<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les documents associés aux comptes
 * Gère CNI, justificatifs, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_compte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade')->comment('Compte associé');
            
            // Informations du document
            $table->string('type_document')->comment('Type: CNI_CLIENT, JUSTIFICATIF_DOMICILE, etc.');
            $table->string('nom_fichier')->comment('Nom original du fichier');
            $table->string('chemin_fichier')->comment('Chemin de stockage du fichier');
            $table->string('extension', 10)->comment('Extension du fichier (pdf, jpg, png)');
            $table->unsignedBigInteger('taille_octets')->comment('Taille en octets (max 10MB)');
            $table->string('mime_type')->comment('Type MIME du fichier');
            
            // Métadonnées
            $table->text('description')->nullable()->comment('Description du document');
            $table->foreignId('uploaded_by')->constrained('users')->comment('Utilisateur ayant uploadé');
            
            $table->timestamps();
            
            $table->index('compte_id');
            $table->index('type_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_compte');
    }
};
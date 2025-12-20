<?php
// database/migrations/2024_01_01_000004_create_account_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_compte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('compte')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            
            $table->enum('type_document', [
                'cni_client',
                'cni_mandataire',
                'justificatif_domicile',
                'photo_identite',
                'signature',
                'formulaire_ouverture',
                'convention',
                'autre'
            ]);
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type');
            $table->integer('taille'); // en octets
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_compte');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->string('type_document');
            $table->string('fichier');

            $table->enum('statut', ['SOUMIS', 'VALIDE', 'REJETE'])
                  ->default('SOUMIS');

            $table->text('commentaire')->nullable();

            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('od_historique', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_diverse_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('action', [
                'CREATION',
                'MODIFICATION',
                'VALIDATION_AGENCE',
                'VALIDATION_COMPTABLE',
                'VALIDATION_DG',
                'COMPTABILISATION',
                'REJET',
                'ANNULATION',
                'UPLOAD_JUSTIFICATIF',
                'CREATION_PAR_MODELE'
            ]);
            $table->string('ancien_statut', 50)->nullable();
            $table->string('nouveau_statut', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('donnees_modifiees')->nullable()->comment('Champs modifiés (JSON)');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Index
            $table->index(['operation_diverse_id', 'created_at']);
            $table->index(['user_id', 'action']);
            $table->index('created_at');
            $table->index('action');
            $table->index('ancien_statut');
            $table->index('nouveau_statut');
            
            // Clés étrangères
            $table->foreign('operation_diverse_id')
                  ->references('id')
                  ->on('operation_diverses')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_historique');
    }
};
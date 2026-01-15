<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('od_historique', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_diverse_id')->constrained('operation_diverses');
            $table->foreignId('user_id')->constrained('users')->comment('User qui a fait l\'action');
            $table->enum('action', ['CREATION','MODIFICATION','VALIDATION','COMPTABILISATION','REJET','ANNULATION']);
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('od_historique');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
        {
            Schema::create('od_workflow', function (Blueprint $table) {
                $table->id();
                $table->date('date_comptable')->nullable();
                $table->unsignedBigInteger('operation_diverse_id');
                $table->integer('niveau')->comment('1=Chef agence, 2=Chef comptable, 3=DG (pour charges)');
                $table->string('role_requis', 100)->comment('Rôle nécessaire pour cette validation');
                $table->unsignedBigInteger('user_id')->nullable()->comment('Utilisateur qui a validé');
                $table->enum('decision', ['EN_ATTENTE', 'APPROUVE', 'REJETE'])->default('EN_ATTENTE');
                $table->string('code_a_verifier', 255)->nullable();
                $table->text('commentaire')->nullable();
                $table->timestamp('date_decision')->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['operation_diverse_id', 'niveau'], 'idx_od_niveau');
                $table->index('user_id');
                $table->index('date_comptable');

                // Foreign keys
                $table->foreign('operation_diverse_id')
                    ->references('id')
                    ->on('operation_diverses')
                    ->onDelete('cascade');
                    
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_workflow');
    }
};
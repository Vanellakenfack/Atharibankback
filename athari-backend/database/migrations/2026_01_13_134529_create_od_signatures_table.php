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
        Schema::create('od_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_diverse_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('niveau_validation')->comment('Niveau dans la chaîne de validation');
            $table->string('role_validation', 50)->comment('Rôle du validateur');
            $table->enum('decision', ['APPROUVE', 'REJETE', 'EN_ATTENTE'])->default('EN_ATTENTE');
            $table->text('commentaire')->nullable();
            $table->string('signature_path', 255)->nullable()->comment('Chemin signature électronique');
            $table->timestamp('signature_date')->nullable();
            $table->timestamps();

            // Index
            $table->unique(['operation_diverse_id', 'user_id', 'niveau_validation'], 'od_signatures_unique');
            $table->index(['operation_diverse_id', 'niveau_validation'], 'od_signatures_operation_diverse_id_niveau_validation_index');
            $table->index(['user_id', 'decision'], 'od_signatures_user_id_decision_index');

            // Clés étrangères
            $table->foreign('operation_diverse_id')
                  ->references('id')
                  ->on('operation_diverses');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_signatures');
    }
};
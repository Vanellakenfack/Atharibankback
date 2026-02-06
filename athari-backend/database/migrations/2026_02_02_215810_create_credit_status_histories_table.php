<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->string('ancien_statut');
            $table->string('nouveau_statut');

            $table->text('commentaire')->nullable();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('date_changement');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_status_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandataire', function (Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->id();

        $table->foreignId('account_id')
            ->constrained('compte')
            ->onDelete('restrict');

        $table->enum('type_mandataire', ['mandataire_1', 'mandataire_2']);
        $table->enum('sexe', ['M', 'F']);
        $table->string('nom');
        $table->string('prenoms');
        $table->date('date_naissance');
        $table->string('lieu_naissance');
        $table->string('telephone');
        $table->string('adresse');
        $table->string('nationalite');
        $table->string('profession');
        $table->string('nom_jeune_fille_mere')->nullable();
        $table->string('numero_cni')->unique();
        $table->date('cni_delivrance')->nullable();
        $table->date('cni_expiration')->nullable();

        $table->enum('situation_familiale', ['celibataire', 'marie', 'divorce', 'veuf', 'autre']);
        $table->string('nom_conjoint')->nullable();
        $table->date('date_naissance_conjoint')->nullable();
        $table->string('lieu_naissance_conjoint')->nullable();
        $table->string('cni_conjoint')->nullable();

        $table->string('signature_path')->nullable();
        $table->string('photo_path')->nullable();

        $table->boolean('is_active')->default(true);
        $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('mandataire');
    }
};
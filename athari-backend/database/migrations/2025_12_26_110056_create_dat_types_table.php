<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     */
      public function up(): void
{
    Schema::create('dat_types', function (Blueprint $table) {
        $table->id();
        $table->string('libelle');
        $table->decimal('taux_interet', 5, 4); 
        $table->integer('duree_mois'); 
        $table->decimal('taux_penalite', 5, 4);
        $table->integer('nombre_tranches_requis')->default(3);
        
        $table->string('code_comptable_interet')->nullable();
        $table->string('code_comptable_penalite')->nullable();
         $table->foreignId('plan_comptable_interet_id')->nullable()->constrained('plan_comptable');
        $table->foreignId('plan_comptable_penalite_id')->nullable()->constrained('plan_comptable');
        
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    
    public function down(): void
    {
        Schema::dropIfExists('dat_types');
    }
};

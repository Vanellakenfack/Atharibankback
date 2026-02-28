<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('caisse_transactions_digitales', function (Blueprint $table) {
            // 1. On rend l'ancienne clé étrangère optionnelle ou on la supprime
            // Si vous voulez couper le lien avec caisse_transactions :
           // On vérifie d'abord si la colonne existe
        if (Schema::hasColumn('caisse_transactions_digitales', 'caisse_transaction_id')) {
            // On essaie de supprimer la clé étrangère de manière brute 
            // car le nom peut varier selon votre version de MySQL
            $table->dropColumn('caisse_transaction_id'); 
        }

            // 2. AJOUT DES CHAMPS DE STRUCTURE BANCAIRE
            $table->string('reference_unique')->unique()->after('id');
            $table->foreignId('session_id')->after('reference_unique')->constrained('caisse_sessions');
            $table->foreignId('compte_id')->nullable()->after('session_id')->constrained('comptes');
            
            $table->string('code_agence')->nullable()->after('compte_id');
            $table->string('code_guichet')->nullable()->after('code_agence');
            $table->string('code_caisse')->nullable()->after('code_guichet');

            // 3. AJOUT DES CHAMPS DE FLUX
            $table->string('type_flux')->after('code_caisse')->comment('RETRAIT ou VERSEMENT');
            $table->decimal('montant_brut', 15, 2)->after('operateur');
            $table->decimal('commissions', 15, 2)->default(0)->after('montant_brut');

            // 4. CHAMPS D'AUDIT ET DATE
            $table->dateTime('date_operation')->after('commissions');
            $table->dateTime('date_valeur')->after('date_operation');
            $table->foreignId('caissier_id')->after('date_valeur')->constrained('users');
            $table->string('statut')->default('VALIDE')->after('caissier_id');

            // 5. CHAMPS DE GESTION COMPTABLE (UsesDateComptable)
            $table->integer('jour_comptable_id')->nullable()->after('date_comptable');
        });
    }

    public function down()
    {
        Schema::table('caisse_transactions_digitales', function (Blueprint $table) {
            // Logique inverse si nécessaire pour revenir à l'état initial
            $table->dropColumn([
                'reference_unique', 'session_id', 'compte_id', 'code_agence', 
                'code_guichet', 'code_caisse', 'type_flux', 'montant_brut', 
                'commissions', 'date_operation', 'date_valeur', 'caissier_id', 
                'statut', 'date_comptable', 'jour_comptable_id'
            ]);
            
            $table->foreignId('caisse_transaction_id')
                  ->constrained('caisse_transactions');
        });
    }
};
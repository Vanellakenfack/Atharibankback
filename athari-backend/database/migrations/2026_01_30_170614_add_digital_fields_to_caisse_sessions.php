<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_sessions', function (Blueprint $table) {
            // --- OUVERTURE (Pour le bilan initial) ---
            $table->decimal('ouverture_om_espece', 15, 2)->default(0)->after('solde_ouverture');
            $table->decimal('ouverture_momo_espece', 15, 2)->default(0)->after('ouverture_om_espece');

            // --- FLUX TEMPS RÉEL (Soldes actuels des enveloppes) ---
            // Ces champs stockent le cash "mis à part" pour les clients de passage
            $table->decimal('solde_om_espece', 15, 2)->default(0)->after('solde_informatique');
            $table->decimal('solde_momo_espece', 15, 2)->default(0)->after('solde_om_espece');

            // --- STOCKS VIRTUELS (UV) ---
            // Miroirs des comptes 57111001 et 57113001 pour affichage rapide
            $table->decimal('solde_om_uv', 15, 2)->default(0)->after('solde_momo_espece');
            $table->decimal('solde_momo_uv', 15, 2)->default(0)->after('solde_om_uv');

            // --- FERMETURE (Pour l'audit et les écarts) ---
            $table->decimal('physique_om_espece', 15, 2)->nullable()->after('solde_fermeture');
            $table->decimal('physique_momo_espece', 15, 2)->nullable()->after('physique_om_espece');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'ouverture_om_espece', 'ouverture_momo_espece',
                'solde_om_espece', 'solde_momo_espece',
                'solde_om_uv', 'solde_momo_uv',
                'physique_om_espece', 'physique_momo_espece'
            ]);
        });
    }
};
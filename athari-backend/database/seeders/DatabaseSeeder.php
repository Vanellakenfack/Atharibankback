<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Exécute les seeders essentiels dans le bon ordre
        // 1) Permissions (rôles & permissions)
        // 2) Utilisateurs (qui s'appuient sur les rôles créés)
        $this->call([
            PermissionsSeeder::class,
            UserSeeder::class,
        ]);
    }
}

<?php
// app/Console/Commands/AppliquerCommissionsMensuelles.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Frais\CalculFraisService;

class AppliquerCommissionsMensuelles extends Command
{
    protected $signature = 'commissions:mensuelles {--date= : Date de calcul (format: Y-m-d)}';
    protected $description = 'Appliquer les commissions mensuelles de fin de mois';
    
    public function handle(CalculFraisService $calculFraisService)
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        
        $this->info('Début de l\'application des commissions mensuelles pour ' . $date->format('F Y'));
        
        if ($this->confirm('Voulez-vous vraiment appliquer les commissions mensuelles?', true)) {
            try {
                $applications = $calculFraisService->appliquerCommissionsMensuelles($date);
                
                $this->info('✅ ' . count($applications) . ' commissions appliquées avec succès');
                
                // Log des applications
                foreach ($applications as $app) {
                    $this->line('   • Compte ' . $app->compte_id . ': ' . number_format($app->montant, 0, ',', ' ') . ' FCFA');
                }
                
            } catch (\Exception $e) {
                $this->error('❌ Erreur: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('Opération annulée');
        }
        
        return 0;
    }
}
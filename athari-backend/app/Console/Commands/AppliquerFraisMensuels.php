<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Frais\CalculFraisService;

class AppliquerFraisMensuels extends Command
{
    protected $signature = 'frais:mensuels';
    protected $description = 'Appliquer les frais et commissions mensuels';

    protected $calculFraisService;

    public function __construct(CalculFraisService $calculFraisService)
    {
        parent::__construct();
        $this->calculFraisService = $calculFraisService;
    }

    public function handle()
    {
        $this->info('Début de l\'application des frais mensuels...');
        
        // Appliquer les frais mensuels
        $this->calculFraisService->appliquerFraisMensuels();
        
        // Appliquer les frais SMS
        $this->calculFraisService->appliquerFraisSMS();
        
        // Relancer les frais en attente
        $this->calculFraisService->relancerFraisAttente();
        
        $this->info('Application des frais mensuels terminée.');
        
        return Command::SUCCESS;
    }
}
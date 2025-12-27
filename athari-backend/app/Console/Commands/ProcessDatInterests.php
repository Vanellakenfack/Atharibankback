<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Compte\DATService;

class ProcessDatInterests extends Command
{
    // Le nom de la commande à taper dans le terminal
    protected $signature = 'dat:process-interests';

    // La description de ce que fait la commande
    protected $description = 'Calcule et verse les intérêts mensuels des contrats DAT actifs';

    public function handle()
    {
        $this->info('Début du traitement des intérêts DAT...');
        
        // On appelle ton service
        $service = new DATService();
        $service->traiterInteretsMensuels();

        $this->info('Tous les intérêts ont été traités avec succès !');
    }
}
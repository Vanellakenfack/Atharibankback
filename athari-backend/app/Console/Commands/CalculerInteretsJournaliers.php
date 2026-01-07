<?php
// app/Console/Commands/CalculerInteretsJournaliers.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Frais\CalculFraisService;

class CalculerInteretsJournaliers extends Command
{
    protected $signature = 'interets:calculer {--date= : Date de calcul (format: Y-m-d)}';
    protected $description = 'Calculer et appliquer les intérêts créditeurs journaliers';
    
    public function handle(CalculFraisService $calculFraisService)
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        
        $this->info('Début du calcul des intérêts journaliers pour ' . $date->format('d/m/Y'));
        
        if ($this->confirm('Voulez-vous vraiment calculer les intérêts?', true)) {
            try {
                $applications = $calculFraisService->calculerInteretsCrediteurs($date);
                
                $this->info('✅ ' . count($applications) . ' calculs d\'intérêts effectués avec succès');
                
                $totalInterets = array_sum(array_column($applications->toArray(), 'montant'));
                $this->line('   • Total intérêts distribués: ' . number_format($totalInterets, 0, ',', ' ') . ' FCFA');
                
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
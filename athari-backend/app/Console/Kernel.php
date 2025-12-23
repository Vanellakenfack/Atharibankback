<?php
// app/Console/Kernel.php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Calcul des intérêts journaliers à 12h00
        $schedule->command('interets:calculer')
            ->dailyAt('12:00')
            ->timezone('Africa/Douala');
        
        // Commissions mensuelles le dernier jour du mois à 23:30
        $schedule->command('commissions:mensuelles')
            ->monthlyOn(31, '23:30')
            ->timezone('Africa/Douala');
            
        $schedule->command('commissions:mensuelles')
            ->monthlyOn(30, '23:30')
            ->timezone('Africa/Douala')
            ->when(function () {
                return in_array(now()->month, [4, 6, 9, 11]);
            });
            
        $schedule->command('commissions:mensuelles')
            ->monthlyOn(29, '23:30')
            ->timezone('Africa/Douala')
            ->when(function () {
                return now()->month == 2 && now()->format('L') == 1;
            });
            
        $schedule->command('commissions:mensuelles')
            ->monthlyOn(28, '23:30')
            ->timezone('Africa/Douala')
            ->when(function () {
                return now()->month == 2 && now()->format('L') == 0;
            });
        
        // Commissions SMS le dernier jour du mois à 23:45
        $schedule->command('commissions:sms')
            ->monthlyOn(31, '23:45')
            ->timezone('Africa/Douala');
    }
    
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}
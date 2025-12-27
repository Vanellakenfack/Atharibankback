<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');





// Exécuter le calcul des intérêts le 1er de chaque mois à minuit
Schedule::command('dat:process-interests')->monthlyOn(1, '00:00');
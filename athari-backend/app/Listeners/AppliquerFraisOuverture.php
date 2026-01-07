<?php
// app/Listeners/AppliquerFraisOuverture.php

namespace App\Listeners;

use App\Events\CompteOuvert;
use App\Services\Frais\CalculFraisService;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppliquerFraisOuverture implements ShouldQueue
{
    protected $calculFraisService;
    
    public function __construct(CalculFraisService $calculFraisService)
    {
        $this->calculFraisService = $calculFraisService;
    }
    
    public function handle(CompteOuvert $event)
    {
        // Appliquer les frais d'ouverture
        $this->calculFraisService->appliquerFraisOuverture($event->compte);
    }
}
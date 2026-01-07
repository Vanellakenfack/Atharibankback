<?php
// app/Events/CompteOuvert.php

namespace App\Events;

use App\Models\compte\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteOuvert
{
    use Dispatchable, SerializesModels;
    
    public $compte;
    
    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }
}
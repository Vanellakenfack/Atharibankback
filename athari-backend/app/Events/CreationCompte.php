<?php

namespace App\Events;

use App\Models\compte\Compte;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreationCompte
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Compte $account
    ) {}
}
<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // <--- AJOUTER CECI
use Illuminate\Notifications\Notification;
use App\Channels\SmsChannel;

class CompteActiveNotification extends Notification implements ShouldQueue // <--- IMPLEMENTER
{
    use Queueable; // <--- UTILISER LE TRAIT

    protected $compte;
    public $tries = 3; // Réessayera 3 fois avant d'abandonner
    public $backoff = 60; // Attend 60 secondes entre chaque tentative
    public function __construct($compte)
    {
        $this->compte = $compte;
    }

    public function via($notifiable)
    {
        return [SmsChannel::class, 'database'];
    }

    public function toSms($notifiable)
    {
        return "CONFIRMATION: Votre compte " . $this->compte->numero_compte . " est actif.";
    }
}
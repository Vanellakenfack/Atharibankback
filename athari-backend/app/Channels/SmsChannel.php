<?php
namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        // 1. On récupère le message que vous avez défini dans la Notification
        $message = $notification->toSms($notifiable);
        
        // 2. On récupère le numéro du client (via la méthode routeNotificationForSms)
        $to = $notifiable->routeNotificationForSms();

        // 3. On simule l'envoi en écrivant dans storage/logs/laravel.log
        Log::channel('stack')->info("📱 [SMS SIMULATION]");
        Log::channel('stack')->info("TO: {$to}");
        Log::channel('stack')->info("MESSAGE: {$message}");
        Log::channel('stack')->info("STATUS: Succès (Simulé)");
        Log::channel('stack')->info("-----------------------------------------");

        return true; // On retourne vrai pour que Laravel pense que c'est bon
    }
}
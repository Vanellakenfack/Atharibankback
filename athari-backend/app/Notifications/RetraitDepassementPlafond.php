<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RetraitDepassementPlafond extends Notification
{
    use Queueable;

    public $demande;

    /**
     * On passe l'objet demande au constructeur
     */
    public function __construct($demande)
    {
        $this->demande = $demande;
    }

    /**
     * On définit le canal de distribution (Base de données)
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Les données qui seront stockées en format JSON dans la table 'notifications'
     */
    public function toDatabase($notifiable): array
    {
        // On charge la relation caissiere si elle n'est pas présente pour éviter les erreurs
        $caissiereNom = $this->demande->caissiere ? $this->demande->caissiere->name : 'Caissière inconnue';

        return [
            'demande_id' => $this->demande->id,
            'montant'    => $this->demande->montant,
            'message'    => "Nouvelle demande de retrait : " . number_format($this->demande->montant, 0, ',', ' ') . " FCFA",
            'caissiere'  => $caissiereNom,
            'type'       => 'PLAFOND_DEPASSE'
        ];
    }

    /**
     * Cette méthode est utilisée par défaut par Laravel si toDatabase n'existe pas
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
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
    $demande = $this->demande;

    // 1. On récupère le montant (colonne réelle)
    $montant = is_object($demande) ? $demande->montant : ($demande['montant'] ?? 0);

    // 2. On récupère la référence (DANS LE PAYLOAD)
    $reference = 'N/A';
    if (is_object($demande)) {
        // Si payload_data est casté en array dans le modèle
        $payload = is_array($demande->payload_data) 
            ? $demande->payload_data 
            : json_decode($demande->payload_data, true);
            
        $reference = $payload['reference_unique'] ?? 'N/A';
    } else {
        $reference = $demande['reference_unique'] ?? 'N/A';
    }

    return [
        'demande_id' => is_object($demande) ? $demande->id : ($demande['id'] ?? null),
        'montant'    => $montant,
        'message'    => "Nouvelle demande de retrait : " . number_format($montant, 0, ',', ' ') . " FCFA",
        'caissiere'  => is_object($demande) ? ($demande->caissiere->name ?? 'Caissière') : 'Caissière',
        'type'       => 'PLAFOND_DEPASSE',
        'reference'  => $reference // <--- FIXÉ ICI
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
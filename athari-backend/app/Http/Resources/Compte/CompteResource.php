<?php
namespace App\Http\Resources\Compte;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'statut' => $this->statut,
            
            // Infos Client
            'client' => [
                'nom' => $this->client->nom,
                'nui' => $this->client->nui,
            ],

            // État des validations
            'validations' => [
                'chef_agence' => (bool)$this->validation_chef_agence,
                'juridique' => (bool)$this->validation_juridique,
                'dossier_complet' => (bool)$this->dossier_complet,
            ],

            // Gestion de l'opposition
            'opposition' => [
                'est_en_opposition' => (bool)$this->est_en_opposition,
                'message_alerte' => $this->genererMessageOpposition(),
            ],

            // Détails techniques
            'checklist' => $this->checklist_juridique, // Retourne le tableau JSON
            'motif_rejet' => $this->motif_rejet,
            'date_activation' => $this->date_activation_definitive?->format('d/m/Y H:i'),
            
            // Traçabilité (noms des agents)
            'agents' => [
                'validateur_ca' => $this->chefAgence?->name,
                'validateur_juriste' => $this->juriste?->name,
                'auteur_rejet' => $this->rejetePar?->name,
            ],
        ];
    }

    private function genererMessageOpposition()
    {
        if ($this->statut === 'actif' && $this->est_en_opposition) {
            return "Compte activé mais retraits bloqués : documents manquants.";
        }
        return null;
    }
}
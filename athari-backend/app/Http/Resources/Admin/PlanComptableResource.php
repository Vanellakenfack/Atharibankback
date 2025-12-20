<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanComptableResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'code'    => $this->code,
            'libelle' => strtoupper($this->libelle),
            
            // Utilisation de ?-> pour éviter de crash si la catégorie est manquante
            'comptabilite' => [
                'nature_technique' => $this->nature_solde, 
                'type_bilan'       => $this->categorie?->type_compte ?? 'NON DÉFINI', 
                'classe'           => substr($this->code, 0, 1),
            ],

            'categorie' => $this->categorie ? [
                'id'      => $this->categorie->id,
                'code'    => $this->categorie->code,
                'nom'     => $this->categorie->libelle,
            ] : null,

            'statut' => [
                'est_actif' => (bool) $this->est_actif,
                'label'     => $this->est_actif ? 'Opérationnel' : 'Suspendu',
            ],

            // Sécurité sur le formatage de date au cas où created_at serait null
            'cree_le' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
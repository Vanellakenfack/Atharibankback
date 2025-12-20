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
            
            // On regroupe les informations de classification
            'comptabilite' => [
                'nature_technique' => $this->nature_solde, // ex: CREDIT
                'type_bilan'       => $this->categorie->type_compte, // ex: PASSIF
                'classe'           => substr($this->code, 0, 1), // Extrait la classe (ex: 3)
            ],

            // Informations sur le parent
            'categorie' => [
                'id'      => $this->categorie->id,
                'code'    => $this->categorie->code,
                'nom'     => $this->categorie->libelle,
            ],

            'statut' => [
                'est_actif' => (bool) $this->est_actif,
                'label'     => $this->est_actif ? 'Opérationnel' : 'Suspendu',
            ],

            'cree_le' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
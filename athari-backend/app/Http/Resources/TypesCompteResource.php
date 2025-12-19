<?php
// app/Http/Resources/AccountTypeResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypesCompteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nom' => $this->name,
            'slug' => $this->slug,
            'categorie' => $this->category,
            'sous_categorie' => $this->sub_category,
            
            // Frais
            'frais' => [
                'ouverture' => (float) $this->frais_ouverture,
                'tenue_compte' => (float) $this->frais_tenue_compte,
                'carnet' => (float) $this->frais_carnet,
                'retrait' => (float) $this->frais_retrait,
                'sms' => (float) $this->frais_sms,
                'deblocage' => (float) $this->frais_deblocage,
                'penalite_retrait_anticipe' => (float) $this->penalite_retrait_anticipe,
            ],
            
            // Commissions
            'commissions' => [
                'seuil' => (float) $this->commission_mensuelle_seuil,
                'basse' => (float) $this->commission_mensuelle_basse,
                'haute' => (float) $this->commission_mensuelle_haute,
            ],
            
            // Paramètres
            'minimum_compte' => (float) $this->minimum_compte,
            'remunere' => $this->remunere,
            'taux_interet_annuel' => (float) $this->taux_interet_annuel,
            'est_bloque' => $this->est_bloque,
            'duree_blocage_mois' => $this->duree_blocage_mois,
            'autorise_decouvert' => $this->autorise_decouvert,
            
            // Périodicités
            'periodicite_arrete' => $this->periodicite_arrete,
            'periodicite_extrait' => $this->periodicite_extrait,
            
            'actif' => $this->is_active,
        ];
    }
}
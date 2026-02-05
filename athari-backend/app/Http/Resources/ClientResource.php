<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'num_client'        => $this->num_client,
            'type_client'       => $this->type_client,
            'etat'              => $this->etat,

            // Infos générales
            'telephone'         => $this->telephone,
            'email'             => $this->email,
            'adresse_ville'     => $this->adresse_ville,
            'adresse_quartier'  => $this->adresse_quartier,
            'lieu_dit_domicile' => $this->lieu_dit_domicile,
            'photo_localisation_domicile' => $this->photo_localisation_domicile,
            'photo_localisation_domicile_url' => $this->photo_localisation_domicile_url,
            'lieu_dit_activite' => $this->lieu_dit_activite,
            'ville_activite'    => $this->ville_activite,
            'quartier_activite' => $this->quartier_activite,
            'photo_localisation_activite' => $this->photo_localisation_activite,
            'photo_localisation_activite_url' => $this->photo_localisation_activite_url,
            'bp'                => $this->bp,
            'pays_residence'    => $this->pays_residence,
            'solde_initial'     => $this->solde_initial,
            'immobiliere'       => $this->immobiliere,
            'autres_biens'      => $this->autres_biens,

            // Agence
            'agency' => $this->whenLoaded('agency', function () {
                return [
                    'id'   => $this->agency->id,
                    'nom'  => $this->agency->nom ?? null,
                    'code' => $this->agency->code ?? null,
                ];
            }),

            // Client Physique
            'physique' => $this->when(
                $this->type_client === 'physique' && $this->relationLoaded('physique'),
                function () {
                    return [
                        'id'                    => $this->physique->id,
                        'nom_prenoms'           => $this->physique->nom_prenoms,
                        'sexe'                  => $this->physique->sexe,
                        'date_naissance'        => $this->physique->date_naissance,
                        'lieu_naissance'        => $this->physique->lieu_naissance,
                        'nationalite'           => $this->physique->nationalite,
                        'nui'                   => $this->physique->nui,

                        'photo'                 => $this->physique->photo,
                        'photo_url'             => $this->physique->photo_url,
                        'signature'             => $this->physique->signature,
                        'signature_url'         => $this->physique->signature_url,
                        'cni_recto'             => $this->physique->cni_recto, // NOUVEAU
                        'cni_recto_url'         => $this->physique->cni_recto_url, // NOUVEAU
                        'cni_verso'             => $this->physique->cni_verso, // NOUVEAU
                        'cni_verso_url'         => $this->physique->cni_verso_url, // NOUVEAU

                        'cni_numero'            => $this->physique->cni_numero,
                        'cni_delivrance'        => $this->physique->cni_delivrance,
                        'cni_expiration'        => $this->physique->cni_expiration,

                        'nom_pere'              => $this->physique->nom_pere,
                        'nationalite_pere'      => $this->physique->nationalite_pere,
                        'nom_mere'              => $this->physique->nom_mere,
                        'nationalite_mere'      => $this->physique->nationalite_mere,

                        'profession'            => $this->physique->profession,
                        'employeur'             => $this->physique->employeur,
                        'situation_familiale'   => $this->physique->situation_familiale,

                        'nom_conjoint'          => $this->physique->nom_conjoint,
                        'date_naissance_conjoint'=> $this->physique->date_naissance_conjoint,
                        'cni_conjoint'          => $this->physique->cni_conjoint,
                        'profession_conjoint'   => $this->physique->profession_conjoint,
                        'salaire'               => $this->physique->salaire,
                        'tel_conjoint'          => $this->physique->tel_conjoint,
                    ];
                }
            ),

            // Client Moral
            'morale' => $this->when(
                $this->type_client === 'morale' && $this->relationLoaded('morale'),
                function () {
                    return [
                        'id'               => $this->morale->id,
                        'raison_sociale'   => $this->morale->raison_sociale,
                        'sigle'            => $this->morale->sigle,
                        'forme_juridique'  => $this->morale->forme_juridique,
                        'rccm'             => $this->morale->rccm,
                        'nui'              => $this->morale->nui,
                        'type_entreprise'  => $this->morale->type_entreprise, // NOUVEAU
                        
                        // Gérant 1
                        'nom_gerant'       => $this->morale->nom_gerant,
                        'telephone_gerant' => $this->morale->telephone_gerant, // NOUVEAU
                        'photo_gerant'     => $this->morale->photo_gerant, // NOUVEAU
                        'photo_gerant_url' => $this->morale->photo_gerant_url, // NOUVEAU
                        
                        // Gérant 2
                        'nom_gerant2'      => $this->morale->nom_gerant2, // NOUVEAU
                        'telephone_gerant2'=> $this->morale->telephone_gerant2, // NOUVEAU
                        'photo_gerant2'    => $this->morale->photo_gerant2, // NOUVEAU
                        'photo_gerant2_url'=> $this->morale->photo_gerant2_url, // NOUVEAU
                        
                        // Signataire 1
                        'nom_signataire'   => $this->morale->nom_signataire, // NOUVEAU
                        'telephone_signataire' => $this->morale->telephone_signataire, // NOUVEAU
                        'photo_signataire' => $this->morale->photo_signataire, // NOUVEAU
                        'photo_signataire_url' => $this->morale->photo_signataire_url, // NOUVEAU
                        'signature_signataire' => $this->morale->signature_signataire, // NOUVEAU
                        'signature_signataire_url' => $this->morale->signature_signataire_url, // NOUVEAU
                        
                        // Signataire 2
                        'nom_signataire2'  => $this->morale->nom_signataire2, // NOUVEAU
                        'telephone_signataire2' => $this->morale->telephone_signataire2, // NOUVEAU
                        'photo_signataire2'=> $this->morale->photo_signataire2, // NOUVEAU
                        'photo_signataire2_url' => $this->morale->photo_signataire2_url, // NOUVEAU
                        'signature_signataire2' => $this->morale->signature_signataire2, // NOUVEAU
                        'signature_signataire2_url' => $this->morale->signature_signataire2_url, // NOUVEAU
                        
                        // Signataire 3
                        'nom_signataire3'  => $this->morale->nom_signataire3, // NOUVEAU
                        'telephone_signataire3' => $this->morale->telephone_signataire3, // NOUVEAU
                        'photo_signataire3'=> $this->morale->photo_signataire3, // NOUVEAU
                        'photo_signataire3_url' => $this->morale->photo_signataire3_url, // NOUVEAU
                        'signature_signataire3' => $this->morale->signature_signataire3, // NOUVEAU
                        'signature_signataire3_url' => $this->morale->signature_signataire3_url, // NOUVEAU
                    ];
                }
            ),

            // Meta
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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

            // Infos générales
            'telephone'         => $this->telephone,
            'email'             => $this->email,
            'adresse_ville'     => $this->adresse_ville,
            'adresse_quartier'  => $this->adresse_quartier,
            'bp'                => $this->bp,
            'pays_residence'    => $this->pays_residence,
            'taxable'           => (bool) $this->taxable,
            'interdit_chequier' => (bool) $this->interdit_chequier,

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

                        'cni_numero'            => $this->physique->cni_numero,
                        'cni_delivrance'        => $this->physique->cni_delivrance,
                        'cni_expiration'        => $this->physique->cni_expiration,

                        'profession'            => $this->physique->profession,
                        'employeur'             => $this->physique->employeur,
                        'situation_familiale'   => $this->physique->situation_familiale,

                        'nom_conjoint'          => $this->physique->nom_conjoint,
                        'date_naissance_conjoint'=> $this->physique->date_naissance_conjoint,
                        'profession_conjoint'   => $this->physique->profession_conjoint,
                        'tel_conjoint'          => $this->physique->tel_conjoint,

                        'salaire'               => $this->physique->salaire,

                        'photo'     => $this->physique->photo,
                        'photo_url' => $this->physique->photo
                            ? asset('storage/' . $this->physique->photo)
                            : null,
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
                        'nom_gerant'       => $this->morale->nom_gerant,
                    ];
                }
            ),

            // Meta
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

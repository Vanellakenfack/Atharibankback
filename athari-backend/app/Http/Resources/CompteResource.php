<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte_formate,
            'numero_compte_sans_cle' => $this->numero_compte,
            'cle' => $this->cle_compte,
            
            // Relations
            'client' => new ClientResource($this->whenLoaded('client')),
            'type_compte' => new TypesCompteResource($this->whenLoaded('accountType')),
            'agence' => new AgencyResource($this->whenLoaded('agency')),
            'createur' => new UserResource($this->whenLoaded('creator')),
            
            // Soldes
            'solde' => (float) $this->solde,
            'solde_disponible' => (float) $this->solde_disponible,
            'solde_bloque' => (float) $this->solde_bloque,
            'minimum_compte' => (float) $this->minimum_compte,
            
            // Rubriques MATA BOOST (si applicable)
            'rubriques_mata' => $this->when($this->accountType?->isMataBoost(), [
                'business' => (float) $this->solde_business,
                'sante' => (float) $this->solde_sante,
                'scolarite' => (float) $this->solde_scolarite,
                'fete' => (float) $this->solde_fete,
                'fournitures' => (float) $this->solde_fournitures,
                'immobilier' => (float) $this->solde_immobilier,
                'total' => (float) $this->solde_global_mata,
            ]),
            
            // Statuts
            'statut' => $this->statut,
            'statut_validation' => $this->statut_validation,
            'opposition_debit' => $this->opposition_debit,
            'opposition_credit' => $this->opposition_credit,
            'motif_opposition' => $this->motif_opposition,
            
            // Dates
            'date_ouverture' => $this->date_ouverture?->format('Y-m-d'),
            'date_echeance' => $this->date_echeance?->format('Y-m-d'),
            'date_cloture' => $this->date_cloture?->format('Y-m-d'),
            'echeance_atteinte' => $this->when($this->date_echeance, $this->isEcheanceAtteinte()),
            
            // Validations
            'validations' => [
                'ca' => $this->when($this->validated_by_ca, [
                    'validateur' => new UserResource($this->whenLoaded('validatorCa')),
                    'date' => $this->validated_at_ca?->format('Y-m-d H:i:s'),
                ]),
                'aj' => $this->when($this->validated_by_aj, [
                    'validateur' => new UserResource($this->whenLoaded('validatorAj')),
                    'date' => $this->validated_at_aj?->format('Y-m-d H:i:s'),
                ]),
            ],
            
            // Autres
            'taxable' => $this->taxable,
            'devise' => $this->devise,
            'numero_ordre' => $this->numero_ordre,
            'notes' => $this->notes,
            
            // Relations additionnelles
            'mandataires' => AccountMandataryResource::collection($this->whenLoaded('mandataries')),
            'documents' => AccountDocumentResource::collection($this->whenLoaded('documents')),
            'dernières_transactions' => AccountTransactionResource::collection(
                $this->whenLoaded('transactions', fn() => $this->transactions->take(10))
            ),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
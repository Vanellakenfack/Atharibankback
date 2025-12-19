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
            'account_number' => $this->account_number,
            'account_key' => $this->account_key,
            'full_account_number' => $this->full_account_number,
            'balance' => (float) $this->balance,
            'available_balance' => (float) $this->available_balance,
            'minimum_balance_amount' => (float) $this->minimum_balance_amount,
            'overdraft_limit' => (float) $this->overdraft_limit,
            'status' => $this->status,
            'status_label' => Compte::STATUSES[$this->status] ?? $this->status,
            'debit_blocked' => $this->debit_blocked,
            'credit_blocked' => $this->credit_blocked,
            'opening_date' => $this->opening_date?->format('Y-m-d'),
            'closing_date' => $this->closing_date?->format('Y-m-d'),
            'blocking_end_date' => $this->blocking_end_date?->format('Y-m-d'),
            'blocking_reason' => $this->blocking_reason,
            'mata_boost_balances' => $this->mata_boost_balances,
            'documents_complete' => $this->documents_complete,
            'notice_accepted' => $this->notice_accepted,
            
            'client' => new ClientResource($this->whenLoaded('client')),
            'account_type' => new TypesCompteResource($this->whenLoaded('accountType')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'collector' => new UserResource($this->whenLoaded('collector')),
            'mandataires' => MandataireResource::collection($this->whenLoaded('mandataires')),
            'documents' => DocumentsCompteResource::collection($this->whenLoaded('documents')),
            
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'validated_by_ca' => new UserResource($this->whenLoaded('validatedByCA')),
            'validated_at_ca' => $this->validated_at_ca?->format('Y-m-d H:i:s'),
            'validated_by_aj' => new UserResource($this->whenLoaded('validatedByAJ')),
            'validated_at_aj' => $this->validated_at_aj?->format('Y-m-d H:i:s'),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
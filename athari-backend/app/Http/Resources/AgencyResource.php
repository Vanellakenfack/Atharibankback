<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
{
    return [
        'id'          => $this->id,
        'code'        => strtoupper($this->code),
        'agency_name' => $this->name,
        'initials'    => $this->short_name,
        // Utilisation de l'opérateur nullsafe (?->) pour éviter le crash
        'created_at'  => $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
    ];
}
}

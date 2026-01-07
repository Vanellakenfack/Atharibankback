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
        'id' => $this->id,
        'code' => strtoupper($this->code),
        'agency_name' => $this->name,
        'initials' => $this->short_name,
        'created_at' => $this->created_at ? $this->created_at->format('d/m/Y') : 'N/A',
    ];
    }
}

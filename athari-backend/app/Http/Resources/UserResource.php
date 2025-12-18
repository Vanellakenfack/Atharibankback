<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            // Rôles
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->pluck('name')
            ),
            'role' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->first()?->name
            ),

            // Permissions (Spatie)
            'permissions' => $this->when(
                method_exists($this, 'getAllPermissions'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),

            // Dates
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

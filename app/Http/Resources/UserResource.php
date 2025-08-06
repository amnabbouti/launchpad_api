<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getName(),
            'email' => $this->email,

            // Role information
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role?->public_id,
                'slug' => $this->role?->slug,
                'title' => $this->role?->title,
                'forbidden' => $this->role?->getForbidden() ?? [],
                'created_at' => $this->role?->created_at?->format('c'),
                'updated_at' => $this->role?->updated_at?->format('c'),
            ]),

            // Computed role checks
            'is_super_admin' => $this->isSuperAdmin(),
            'is_manager' => $this->isManager(),
            'is_employee' => $this->isEmployee(),
            'is_admin' => $this->isManager() || $this->isSuperAdmin(),
            'is_active' => true,

            // Organization
            'organization' => $this->organization ? [
                'id' => $this->organization->public_id,
                'name' => $this->organization->name,
            ] : null,

            // Attachments
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->public_id,
                'name' => $attachment->name,
                'url' => $attachment->url,
                'size' => $attachment->size,
                'type' => $attachment->type,
            ])
            ),

            'created_at' => $this->created_at?->format('c'),
            'updated_at' => $this->updated_at?->format('c'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'role' => new RoleResource($this->whenLoaded('role')),
            'org_id' => $this->org_id,
            'is_active' => $this->is_active,
            'is_super_admin' => $this->isSuperAdmin(),
            'is_manager' => $this->isManager(),
            'is_employee' => $this->isEmployee(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'items' => ItemResource::collection($this->whenLoaded('items')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}

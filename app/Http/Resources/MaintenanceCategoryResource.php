<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MaintenanceCategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'org_id' => $this->org_id,            
            'name' => $this->name,
            'remarks' => $this->remarks,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'maintenance_conditions' => MaintenanceConditionResource::collection($this->whenLoaded('maintenanceConditions')),
        ];
    }
}

<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MaintenanceDetailResource extends BaseResource {
    public function toArray(Request $request): array {
        return [
            'id'                       => $this->id,
            'org_id'                   => $this->org_id,
            'value'                    => $this->value,
            'maintenance_condition_id' => $this->maintenance_condition_id,
            'maintenance_id'           => $this->maintenance_id,
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
            'organization'             => new OrganizationResource($this->whenLoaded('organization')),
            'maintenance_condition'    => new MaintenanceConditionResource($this->whenLoaded('maintenanceCondition')),
            'maintenance'              => new MaintenanceResource($this->whenLoaded('maintenance')),
        ];
    }
}

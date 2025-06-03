<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UnitOfMeasureResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'description' => $this->description,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),

            'items' => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),

            'maintenance_conditions' => $this->whenLoaded('maintenanceConditions', fn () => MaintenanceConditionResource::collection($this->maintenanceConditions)),
        ];

        return $this->addCommonData($data, $request);
    }
}

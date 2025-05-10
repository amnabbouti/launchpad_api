<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitOfMeasureResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'description' => $this->description,
            'type' => $this->type,
            'is_active' => $this->is_active,

            // relationships when they are loaded
            'maintenance_conditions' => $this->when($this->relationLoaded('maintenanceConditions'), function () {
                // there's a MaintenanceConditionResource
                return $this->maintenanceConditions;
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DescriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'description_string' => $this->description_string,
            'language' => $this->language,
            'is_active' => $this->is_active,
            'maintenance_category_id' => $this->maintenance_category_id,

            // relationships when they are loaded
            'maintenance_category' => $this->when($this->relationLoaded('maintenanceCategory'), function () {
                // there's a MaintenanceCategoryResource
                return $this->maintenanceCategory;
            }),
        ];
    }
}

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

            // Relationships
            'maintenance_category' => $this->when($this->relationLoaded('maintenanceCategory'), function () {
                return $this->maintenanceCategory;
            }),
        ];
    }
}

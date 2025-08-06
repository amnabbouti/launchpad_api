<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MaintenanceConditionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'org_id' => $this->org_id,
            'mail_on_warning' => $this->mail_on_warning,
            'mail_on_maintenance' => $this->mail_on_maintenance,
            'maintenance_recurrence_quantity' => $this->maintenance_recurrence_quantity,
            'maintenance_warning_date' => $this->maintenance_warning_date?->toISOString(),
            'maintenance_date' => $this->maintenance_date?->toISOString(),
            'quantity_for_warning' => $this->quantity_for_warning ? (float) $this->quantity_for_warning : null,
            'quantity_for_maintenance' => $this->quantity_for_maintenance ? (float) $this->quantity_for_maintenance : null,
            'recurrence_unit' => $this->recurrence_unit,
            'price_per_unit' => $this->price_per_unit ? (float) $this->price_per_unit : null,
            'is_active' => $this->is_active,
            'item_id' => $this->item_id,
            'status_when_returned_id' => $this->status_when_returned_id,
            'status_when_exceeded_id' => $this->status_when_exceeded_id,
            'maintenance_category_id' => $this->maintenance_category_id,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'is_overdue' => $this->maintenance_date && $this->maintenance_date->isPast(),
            'is_warning_due' => $this->maintenance_warning_date && $this->maintenance_warning_date->isPast(),
            'is_recurring' => $this->is_recurring,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'item' => new ItemResource($this->whenLoaded('item')),
            'status_when_returned' => new StatusResource($this->whenLoaded('statusWhenReturned')),
            'status_when_exceeded' => new StatusResource($this->whenLoaded('statusWhenExceeded')),
            'maintenance_category' => new MaintenanceCategoryResource($this->whenLoaded('maintenanceCategory')),
            'unit_of_measure' => new UnitOfMeasureResource($this->whenLoaded('unitOfMeasure')),
            'maintenance_details' => MaintenanceDetailResource::collection($this->whenLoaded('maintenanceDetails')),
        ];
    }
}

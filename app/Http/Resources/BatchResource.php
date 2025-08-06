<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BatchResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->public_id,
            'org_id' => $this->org_id,
            'batch_number' => $this->batch_number,
            'received_date' => $this->received_date?->toISOString(),
            'expiry_date' => $this->expiry_date?->toISOString(),
            'supplier_id' => $this->supplier_id,
            'unit_cost' => $this->unit_cost,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'expired' => $this->getIsExpiredAttribute(),
            'active_and_not_expired' => $this->getIsActiveAndNotExpiredAttribute(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'items' => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
            'check_in_outs' => $this->whenLoaded('checkInOuts', fn () => CheckInOutResource::collection($this->checkInOuts)),
            'maintenances' => $this->whenLoaded('maintenances', fn () => MaintenanceResource::collection($this->maintenances)),
        ];

        return $this->addCommonData($data, $request);
    }
}

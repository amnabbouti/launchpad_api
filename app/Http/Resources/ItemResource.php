<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->public_id, 
            'org_id' => $this->org_id,
            'name' => $this->name,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'active' => $this->active,
            'specifications' => $this->specifications,
            'category' => $this->category?->name,
            'unit' => $this->unitOfMeasure?->name,
            'status' => $this->status?->name,
            'in_maintenance' => (bool) ($this->relationLoaded('maintenances')
                ? $this->maintenances->whereNull('date_back_from_maintenance')->count() > 0
                : ($this->maintenances()->whereNull('date_back_from_maintenance')->count() > 0)),

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'stockItems' => $this->whenLoaded('stockItems', fn () => StockItemResource::collection($this->stockItems)),
            'maintenances' => $this->whenLoaded('maintenances', fn () => MaintenanceResource::collection($this->maintenances)),
            'maintenanceConditions' => $this->whenLoaded('maintenanceConditions', fn () => MaintenanceConditionResource::collection($this->maintenanceConditions)),
            'suppliers' => $this->whenLoaded('suppliers', fn () => SupplierResource::collection($this->suppliers)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}

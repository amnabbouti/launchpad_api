<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'active' => $this->active,
            'specifications' => $this->specifications,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'user_id' => $this->user_id,
            'status_id' => $this->status_id,
            'in_maintenance' => (bool) ($this->relationLoaded('maintenances')
                ? $this->maintenances->whereNull('date_back_from_maintenance')->count() > 0
                : ($this->maintenances()->whereNull('date_back_from_maintenance')->count() > 0)),

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'unitOfMeasure' => $this->whenLoaded('unitOfMeasure', fn () => new UnitOfMeasureResource($this->unitOfMeasure)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'status' => $this->whenLoaded('status', fn () => new StatusResource($this->status)),
            'stockItems' => $this->whenLoaded('stockItems', fn () => StockItemResource::collection($this->stockItems)),
            'maintenances' => $this->whenLoaded('maintenances', fn () => MaintenanceResource::collection($this->maintenances)),
            'maintenanceConditions' => $this->whenLoaded('maintenanceConditions', fn () => MaintenanceConditionResource::collection($this->maintenanceConditions)),
            'suppliers' => $this->whenLoaded('suppliers', fn () => SupplierResource::collection($this->suppliers)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}

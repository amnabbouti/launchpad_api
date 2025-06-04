<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrganizationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'address' => $this->address,
            'website' => $this->website,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'users' => $this->whenLoaded('users', fn () => UserResource::collection($this->users)),
            'items' => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
            'categories' => $this->whenLoaded('categories', fn () => CategoryResource::collection($this->categories)),
            'locations' => $this->whenLoaded('locations', fn () => LocationResource::collection($this->locations)),
            'suppliers' => $this->whenLoaded('suppliers', fn () => SupplierResource::collection($this->suppliers)),
            'stocks' => $this->whenLoaded('stocks', fn () => StockResource::collection($this->stocks)),
            'unit_of_measures' => $this->whenLoaded('unitOfMeasures', fn () => UnitOfMeasureResource::collection($this->unitOfMeasures)),
            'statuses' => $this->whenLoaded('statuses', fn () => StatusResource::collection($this->statuses)),
            'maintenance_categories' => $this->whenLoaded('maintenanceCategories', fn () => MaintenanceCategoryResource::collection($this->maintenanceCategories)),
            'maintenances' => $this->whenLoaded('maintenances', fn () => MaintenanceResource::collection($this->maintenances)),
            'check_in_outs' => $this->whenLoaded('checkInOuts', fn () => CheckInOutResource::collection($this->checkInOuts)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}

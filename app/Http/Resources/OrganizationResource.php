<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrganizationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->when(auth()->user()->isSuperAdmin(), $this->id, $this->public_id),
            'name' => $this->name,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'logo' => $this->logo,
            'industry' => $this->industry,
            'tax_id' => $this->tax_id,
            'billing_address' => $this->billing_address,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'licenses' => LicenseResource::collection($this->licenses),
            'settings' => $this->settings,
            'created_by' => $this->created_by,
            'deleted_at' => $this->deleted_at?->toISOString(),
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'users' => $this->whenLoaded('users', fn() => UserResource::collection($this->users)),
            'items' => $this->whenLoaded('items', fn() => ItemResource::collection($this->items)),
            'categories' => $this->whenLoaded('categories', fn() => CategoryResource::collection($this->categories)),
            'locations' => $this->whenLoaded('locations', fn() => LocationResource::collection($this->locations)),
            'suppliers' => $this->whenLoaded('suppliers', fn() => SupplierResource::collection($this->suppliers)),
            'batches' => $this->whenLoaded('batches', fn() => BatchResource::collection($this->batches)),
            'unit_of_measures' => $this->whenLoaded('unitOfMeasures', fn() => UnitOfMeasureResource::collection($this->unitOfMeasures)),
            'statuses' => $this->whenLoaded('statuses', fn() => StatusResource::collection($this->statuses)),
            'maintenance_categories' => $this->whenLoaded('maintenanceCategories', fn() => MaintenanceCategoryResource::collection($this->maintenanceCategories)),
            'maintenances' => $this->whenLoaded('maintenances', fn() => MaintenanceResource::collection($this->maintenances)),
            'check_in_outs' => $this->whenLoaded('checkInOuts', fn() => CheckInOutResource::collection($this->checkInOuts)),
            'attachments' => $this->whenLoaded('attachments', fn() => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}

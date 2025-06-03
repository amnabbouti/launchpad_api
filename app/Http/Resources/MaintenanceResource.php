<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MaintenanceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $isActive = $this->date_in_maintenance && ! $this->date_back_from_maintenance;

        $duration = null;

        if ($this->date_in_maintenance && $this->date_back_from_maintenance) {
            $start = new \DateTime($this->date_in_maintenance);
            $end = new \DateTime($this->date_back_from_maintenance);
            $duration = $start->diff($end)->days;
        }

        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'remarks' => $this->remarks,
            'invoice_nbr' => $this->invoice_nbr,
            'cost' => $this->cost,
            'date_in_maintenance' => $this->date_in_maintenance?->toISOString(),
            'date_expected_back_from_maintenance' => $this->date_expected_back_from_maintenance?->toISOString(),
            'date_back_from_maintenance' => $this->date_back_from_maintenance?->toISOString(),
            'duration_days' => $duration,
            'active' => $this->active,
            'is_repair' => $this->is_repair,
            'import_id' => $this->import_id,
            'import_source' => $this->import_source,
            'user_id' => $this->user_id,
            'supplier_id' => $this->supplier_id,
            'stock_item_id' => $this->stock_item_id,
            'status_out_id' => $this->status_out_id,
            'status_in_id' => $this->status_in_id,

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'stockItem' => $this->whenLoaded('stockItem', fn () => new StockItemResource($this->stockItem)),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'statusOut' => $this->whenLoaded('statusOut', fn () => new ItemStatusResource($this->statusOut)),
            'statusIn' => $this->whenLoaded('statusIn', fn () => new ItemStatusResource($this->statusIn)),
            'maintenanceDetails' => $this->whenLoaded('maintenanceDetails', fn () => MaintenanceDetailResource::collection($this->maintenanceDetails)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}

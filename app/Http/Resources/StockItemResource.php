<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StockItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'stock_id' => $this->stock_id,
            'item_id' => $this->item?->public_id,
            'serial_number' => $this->serial_number,
            'barcode' => $this->barcode,
            'quantity' => $this->quantity,
            'status_id' => $this->status_id,
            'notes' => $this->notes,
            'is_checked_out' => $this->is_checked_out,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'stock' => new StockResource($this->whenLoaded('stock')),
            'item' => new ItemResource($this->whenLoaded('item')),
            'status' => new StatusResource($this->whenLoaded('status')),
            'maintenances' => MaintenanceResource::collection($this->whenLoaded('maintenances')),            'check_in_outs' => CheckInOutResource::collection($this->whenLoaded('checkInOuts')),
            'stock_item_locations' => StockItemLocationResource::collection($this->whenLoaded('stockItemLocations')),
            'current_check_out' => $this->current_check_out ? new CheckInOutResource($this->current_check_out) : null,
        ];
    }
}

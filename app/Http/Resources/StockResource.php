<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'barcode' => $this->barcode,
            'purchase_price' => $this->purchase_price,
            'purchase_date' => $this->purchase_date,
            'warranty_end_date' => $this->warranty_end_date,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'location_id' => $this->location_id,
            'status_id' => $this->status_id,
            'is_checked_out' => $this->is_checked_out,

            // Relationships
            'items' => $this->when($this->relationLoaded('items'), function () {
                return ItemResource::collection($this->items);
            }),
            'location' => $this->when($this->relationLoaded('location'), function () {
                return new LocationResource($this->location);
            }),
            'status' => $this->when($this->relationLoaded('status'), function () {
                return new StatusResource($this->status);
            }),
            'current_check_out' => $this->when($this->current_check_out, function () {
                return $this->current_check_out;
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemSupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'supplier_id' => $this->supplier_id,
            'supplier_part_number' => $this->supplier_part_number,
            'price' => $this->price,
            'lead_time' => $this->lead_time,
            'is_preferred' => $this->is_preferred,

            // relationships when they are loaded
            'item' => $this->when($this->relationLoaded('item'), function () {
                return new ItemResource($this->item);
            }),
            'supplier' => $this->when($this->relationLoaded('supplier'), function () {
                return new SupplierResource($this->supplier);
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'location_id' => $this->location_id,
            'quantity' => $this->quantity,

            // Relationships
            'item' => $this->when($this->relationLoaded('item'), function () {
                return new ItemResource($this->item);
            }),
            'location' => $this->when($this->relationLoaded('location'), function () {
                return new LocationResource($this->location);
            }),
        ];
    }
}

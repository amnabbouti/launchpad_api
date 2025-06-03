<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StockItemLocationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'stock_item_id' => $this->stock_item_id,
            'location_id' => $this->location_id,
            'quantity' => $this->quantity,
            'moved_date' => $this->moved_date?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'stock_item' => new StockItemResource($this->whenLoaded('stockItem')),
            'location' => new LocationResource($this->whenLoaded('location')),
        ];
    }
}

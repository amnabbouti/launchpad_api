<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemLocationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'quantity' => $this->quantity,
            'moved_date' => $this->moved_date?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Organization details 
            'organization' => [
                'id' => $this->org_id,
                'name' => $this->organization?->name,
            ],
            
            // Item details 
            'item' => $this->whenLoaded('item', fn () => new ItemResource($this->item)),
            
            // Location details 
            'location' => $this->whenLoaded('location', fn () => new LocationResource($this->location)),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemLocationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        // Calculate availability data using the new clean service method
        $availableQuantity = null;
        $checkedOutQuantity = 0;
        $availabilityStatus = 'unknown';
        
        try {
            $checkInOutService = app(\App\Services\CheckInOutService::class);
            $availabilityData = $checkInOutService->getAvailabilityData($this->id);
            
            $availableQuantity = $availabilityData['available_quantity'];
            $checkedOutQuantity = $availabilityData['checked_out_quantity'];
            $availabilityStatus = $availabilityData['availability_status'];
        } catch (\Exception $e) {
            // Silently handle error, availability will be null
        }

        return [
            'id' => $this->public_id,
            'quantity' => $this->quantity,
            'available_quantity' => $availableQuantity,
            'checked_out_quantity' => $checkedOutQuantity,
            'availability_status' => $availabilityStatus,
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

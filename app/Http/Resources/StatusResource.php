<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StatusResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        // Base data common to both Status and ItemStatus models
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        // Add code field if this is an ItemStatus model (has the code attribute)
        if (isset($this->code)) {
            $data['code'] = $this->code;
        }

        // Add timestamps for ItemStatus (based on ItemStatusResource pattern)
        if (isset($this->code)) {
            $data['created_at'] = $this->created_at?->toISOString();
            $data['updated_at'] = $this->updated_at?->toISOString();
        }

        // Common relationships
        $data['organization'] = $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization));

        // Status-specific relationships (when no code field exists)
        if (! isset($this->code)) {
            $data['items'] = $this->whenLoaded('items', fn () => ItemResource::collection($this->items));
            $data['maintenancesOut'] = $this->whenLoaded('maintenancesOut', fn () => MaintenanceResource::collection($this->maintenancesOut));
            $data['maintenancesIn'] = $this->whenLoaded('maintenancesIn', fn () => MaintenanceResource::collection($this->maintenancesIn));
            $data['checkInOutsOut'] = $this->whenLoaded('checkInOutsOut', fn () => CheckInOutResource::collection($this->checkInOutsOut));
            $data['checkInOutsIn'] = $this->whenLoaded('checkInOutsIn', fn () => CheckInOutResource::collection($this->checkInOutsIn));
        } else {
            // ItemStatus-specific relationships (when code field exists)
            $data['stockItems'] = $this->whenLoaded('stockItems', fn () => StockItemResource::collection($this->stockItems));
            $data['maintenancesOut'] = $this->whenLoaded('maintenancesOut', fn () => MaintenanceResource::collection($this->maintenancesOut));
            $data['maintenancesIn'] = $this->whenLoaded('maintenancesIn', fn () => MaintenanceResource::collection($this->maintenancesIn));
            $data['checkouts'] = $this->whenLoaded('checkouts', fn () => CheckInOutResource::collection($this->checkouts));
            $data['checkins'] = $this->whenLoaded('checkins', fn () => CheckInOutResource::collection($this->checkins));
        }

        return $this->addCommonData($data, $request);
    }
}

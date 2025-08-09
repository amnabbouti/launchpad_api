<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->public_id,
            'org_id' => $this->org_id,
            'organization' => $this->whenLoaded('organization', function () {
                return new OrganizationResource($this->organization);
            }),
            'seats' => $this->seats,
            'license_key' => $this->license_key,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

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
            'id' => $this->id,
            'public_id' => $this->public_id,
            'organization_id' => $this->organization_id,
            'plan_id' => $this->plan_id,
            'organization' => $this->whenLoaded('organization', function () {
                return new OrganizationResource($this->organization);
            }),
            'plan' => $this->whenLoaded('plan', function () {
                return new PlanResource($this->plan);
            }),
            'seats' => $this->seats,
            'license_key' => $this->license_key,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

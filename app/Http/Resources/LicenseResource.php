<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'           => $this->id,
            'org_id'       => $this->org_id,
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'seats'        => $this->seats,
            'license_key'  => $this->license_key,
            'starts_at'    => $this->starts_at?->toISOString(),
            'ends_at'      => $this->ends_at?->toISOString(),
            'status'       => $this->status,
            'meta'         => $this->meta,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}

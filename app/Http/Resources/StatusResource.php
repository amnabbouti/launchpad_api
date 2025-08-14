<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StatusResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'          => $this->id,
            'org_id'      => $this->org_id,
            'name'        => $this->name,
            'code'        => $this->code,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];

        $data['organization'] = $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization));
        $data['items']        = $this->whenLoaded('items', fn () => ItemResource::collection($this->items));

        return $this->addCommonData($data, $request);
    }
}

<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LocationResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'           => $this->id,
            'org_id'       => $this->org_id,
            'parent_id'    => $this->parent_id,
            'name'         => $this->name,
            'code'         => $this->code,
            'path'         => $this->path,
            'description'  => $this->description,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'children'     => $this->whenLoaded('childrenRecursive', fn () => LocationResource::collection($this->childrenRecursive)),
            'items'        => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)) ?? null,
        ];

        return $this->addCommonData($data, $request);
    }
}

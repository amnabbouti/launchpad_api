<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LocationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'parent' => $this->whenLoaded('parent', fn () => new LocationResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => LocationResource::collection($this->children)),
            'childrenRecursive' => $this->whenLoaded('childrenRecursive', fn () => LocationResource::collection($this->childrenRecursive)),
            'items' => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
        ];

        return $this->addCommonData($data, $request);
    }
}

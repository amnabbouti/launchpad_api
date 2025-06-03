<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),

            'parent' => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),

            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),

            'children_recursive' => $this->whenLoaded('childrenRecursive', fn () => CategoryResource::collection($this->childrenRecursive)),

            'items' => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
        ];

        return $this->addCommonData($data, $request);
    }
}

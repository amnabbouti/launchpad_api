<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'           => $this->id,
            'name'         => $this->name,
            'parent_id'    => $this->parent?->id,
            'path'         => $this->path,
            'org_id'       => $this->org_id,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'children'     => $this->whenLoaded('childrenRecursive', fn () => CategoryResource::collection($this->childrenRecursive)),
            'items'        => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
        ];

        return $this->addCommonData($data, $request);
    }
}

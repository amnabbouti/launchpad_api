<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'is_active' => $this->is_active,

            // relationships when they are loaded
            'parent' => $this->when($this->relationLoaded('parent'), function () {
                return new LocationResource($this->parent);
            }),
            'children' => $this->when($this->relationLoaded('children'), function () {
                return LocationResource::collection($this->children);
            }),
            'items' => $this->when($this->relationLoaded('items'), function () {
                return ItemResource::collection($this->items);
            }),
        ];
    }
}

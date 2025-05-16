<?php

namespace App\Http\Resources;

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

            // Relationships
            'parent' => $this->when($this->relationLoaded('parent'), function () {
                return new LocationResource($this->parent);
            }),
            'childrens' => $this->when($this->relationLoaded('childrens'), function () {
                return LocationResource::collection($this->childrens);
            }),
            'items' => $this->when($this->relationLoaded('items'), function () {
                return ItemResource::collection($this->items);
            }),
        ];
    }
}

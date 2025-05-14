<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,

            // relationships when they are loaded
            'parent' => $this->when($this->relationLoaded('parent'), function () {
                return new CategoryResource($this->parent);
            }),
            'children' => $this->when($this->relationLoaded('children'), function () {
                return CategoryResource::collection($this->children);
            }),
            'items' => $this->when($this->relationLoaded('items'), function () {
                return ItemResource::collection($this->items);
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'unit' => $this->unit,
            'category_id' => $this->category_id,
            'user_id' => $this->user_id,
            'stock_id' => $this->stock_id,
            'is_active' => $this->is_active,
            'active' => $this->active,
            'specifications' => $this->specifications,

            // relationships when they are loaded
            'category' => $this->when($this->relationLoaded('category'), function () {
                return new CategoryResource($this->category);
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return new UserResource($this->user);
            }),
            'stock' => $this->when($this->relationLoaded('stock'), function () {
                return new StockResource($this->stock);
            }),
            'locations' => $this->when($this->relationLoaded('locations'), function () {
                return LocationResource::collection($this->locations);
            }),
            'suppliers' => $this->when($this->relationLoaded('suppliers'), function () {
                return SupplierResource::collection($this->suppliers);
            }),
        ];
    }
}

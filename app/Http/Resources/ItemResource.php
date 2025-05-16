<?php

namespace App\Http\Resources;

class ItemResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = [
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
            'specifications' => $this->specifications,
            'in_maintenance' => (bool) ($this->relationLoaded('maintenances')
                ? $this->maintenances->whereNull('date_back_from_maintenance')->count() > 0
                : ($this->maintenances()->whereNull('date_back_from_maintenance')->count() > 0)),

            // Relationships
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

        return $data;
    }
}

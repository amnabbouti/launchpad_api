<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'             => $this->id,
            'name'           => $this->name,
            'code'           => $this->code,
            'barcode'        => $this->barcode,
            'type'           => $this->type,
            'description'    => $this->description,
            'tracking_mode'  => $this->tracking_mode,
            'price'          => $this->price,
            'serial_number'  => $this->serial_number,
            'notes'          => $this->notes,
            'specifications' => $this->specifications,
            'org_id'         => $this->org_id,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),

            'in_maintenance' => $this->maintenances()->whereNotNull('date_in_maintenance')->whereNull('date_back_from_maintenance')->exists(),
            'is_active'      => $this->is_active,

            'total_quantity' => $this->whenLoaded('locations', fn () => $this->locations->sum('pivot.quantity') ?? 0),
            'supplier_count' => $this->whenLoaded('suppliers', fn () => $this->suppliers->count() ?? 0),

            'organization'    => $this->whenLoaded('organization', fn () => ['id' => $this->organization?->id, 'name' => $this->organization?->name]),
            'category'        => $this->whenLoaded('category', fn () => ['id' => $this->category?->id, 'name' => $this->category?->name]),
            'status'          => $this->whenLoaded('status', fn () => ['id' => $this->status?->id, 'name' => $this->status?->name]),
            'unit_of_measure' => $this->whenLoaded('unitOfMeasure', fn () => ['name' => $this->unitOfMeasure?->name, 'symbol' => $this->unitOfMeasure?->symbol]),

            'suppliers' => $this->whenLoaded('suppliers', fn () => $this->suppliers->map(static fn ($supplier) => [
                'id'           => $supplier->id,
                'name'         => $supplier->name,
                'is_preferred' => $supplier->pivot->is_preferred ?? false,
            ])),
            'locations' => $this->whenLoaded('locations', fn () => $this->locations->map(static fn ($location) => [
                'id'         => $location->id,
                'name'       => $location->name,
                'code'       => $location->code,
                'quantity'   => $location->pivot->quantity,
                'created_at' => $location->pivot->created_at?->toISOString(),
            ])),
        ];

        return $this->addCommonData($data, $request);
    }
}

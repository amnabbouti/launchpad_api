<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SupplierResource extends BaseResource {
    public function toArray(Request $request): array {
        // Check if item supplier relationship or supplier
        $isItemSupplier = $this->resource instanceof \App\Models\ItemSupplier;

        if ($isItemSupplier) {
            // ItemSupplier relationship
            $data = [
                'id'                   => $this->id,
                'org_id'               => $this->org_id,
                'item_id'              => $this->item_id,
                'supplier_id'          => $this->supplier_id,
                'supplier_part_number' => $this->supplier_part_number,
                'price'                => $this->price,
                'currency'             => $this->currency,
                'lead_time_days'       => $this->lead_time_days,
                'is_preferred'         => $this->is_preferred,
                'created_at'           => $this->created_at?->toISOString(),
                'updated_at'           => $this->updated_at?->toISOString(),

                'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
                'item'         => $this->whenLoaded('item', fn () => new ItemResource($this->item)),
                'supplier'     => $this->whenLoaded('supplier', fn () => new self($this->supplier)),
            ];
        } else {
            // Supplier data
            $data = [
                'id'           => $this->id,
                'name'         => $this->name,
                'code'         => $this->code,
                'contact_name' => $this->contact_name,
                'email'        => $this->email,
                'phone'        => $this->phone,
                'address'      => $this->address,
                'city'         => $this->city,
                'state'        => $this->state,
                'postal_code'  => $this->postal_code,
                'country'      => $this->country,
                'website'      => $this->website,
                'tax_id'       => $this->tax_id,
                'notes'        => $this->notes,
                'is_active'    => $this->is_active,
                'created_at'   => $this->created_at?->toISOString(),
                'updated_at'   => $this->updated_at?->toISOString(),

                'organization'   => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
                'items'          => $this->whenLoaded('items', fn () => ItemResource::collection($this->items)),
                'item_suppliers' => $this->whenLoaded('itemSuppliers', fn () => self::collection($this->itemSuppliers)),
            ];
        }

        return $this->addCommonData($data, $request);
    }
}

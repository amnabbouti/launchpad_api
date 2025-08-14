<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemMovementResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'             => $this->id,
            'movement_type'  => $this->movement_type,
            'quantity'       => $this->quantity,
            'moved_at'       => $this->moved_at?->toISOString(),
            'reason'         => $this->reason,
            'notes'          => $this->notes,
            'reference_id'   => $this->reference_id,
            'reference_type' => $this->reference_type,
            'org_id'         => $this->org_id,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),

            'item' => $this->whenLoaded('item', fn () => [
                'id'            => $this->item?->id,
                'name'          => $this->item?->name,
                'code'          => $this->item?->code,
                'tracking_mode' => $this->item?->tracking_mode,
            ]),

            'from_location' => $this->whenLoaded('fromLocation', fn () => [
                'id'   => $this->fromLocation?->id,
                'name' => $this->fromLocation?->name,
                'code' => $this->fromLocation?->code,
            ]),

            'to_location' => $this->whenLoaded('toLocation', fn () => [
                'id'   => $this->toLocation?->id,
                'name' => $this->toLocation?->name,
                'code' => $this->toLocation?->code,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ]),
        ];

        return $this->addCommonData($data, $request);
    }
}

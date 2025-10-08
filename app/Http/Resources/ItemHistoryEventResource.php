<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemHistoryEventResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'         => $this->id,
            'event_type' => $this->event_type,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'reason'     => $this->reason,
            'org_id'     => $this->org_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Related entities (simplified)
            'item' => $this->whenLoaded('item', fn () => [
                'id'            => $this->item?->id,
                'name'          => $this->item?->name,
                'code'          => $this->item?->code,
                'tracking_mode' => $this->item?->tracking_mode,
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

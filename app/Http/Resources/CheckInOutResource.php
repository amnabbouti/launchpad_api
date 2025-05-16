<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckInOutResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user'),
            'item' => $this->whenLoaded('item'),
            'checkout_location' => $this->whenLoaded('checkoutLocation'),
            'checkout_date' => $this->checkout_date,
            'quantity' => $this->quantity,
            'status_out' => $this->whenLoaded('statusOut'),
            'checkin_user' => $this->whenLoaded('checkinUser'),
            'checkin_location' => $this->whenLoaded('checkinLocation'),
            'checkin_date' => $this->checkin_date,
            'checkin_quantity' => $this->checkin_quantity,
            'status_in' => $this->whenLoaded('statusIn'),
            'expected_return_date' => $this->expected_return_date,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}

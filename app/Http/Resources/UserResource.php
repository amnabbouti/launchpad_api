<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'date_of_birth' => $this->date_of_birth,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'name' => $this->getName(),

            // Relationships
            'items' => $this->when($this->relationLoaded('items'), function () {
                return ItemResource::collection($this->items);
            }),
        ];
    }
}

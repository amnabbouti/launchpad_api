<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,

            // Relationships
            'stocks' => $this->when($this->relationLoaded('stocks'), function () {
                return StockResource::collection($this->stocks);
            }),
            'maintenances_out' => $this->when($this->relationLoaded('maintenancesOut'), function () {
                return $this->maintenancesOut;
            }),
            'maintenances_in' => $this->when($this->relationLoaded('maintenancesIn'), function () {
                return $this->maintenancesIn;
            }),
            'check_in_outs_out' => $this->when($this->relationLoaded('checkInOutsOut'), function () {
                return $this->checkInOutsOut;
            }),
            'check_in_outs_in' => $this->when($this->relationLoaded('checkInOutsIn'), function () {
                return $this->checkInOutsIn;
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'name' => $this->name,
            'price' => $this->price,
            'user_limit' => $this->user_limit,
            'features' => $this->features,
            'interval' => $this->interval,
            'is_active' => $this->is_active,
            'organization_count' => $this->whenLoaded('organizations', function () {
                return $this->organizations->count();
            }, function () {
                return $this->organizations_count ?? $this->organizations()->count();
            }),
            'license_count' => $this->whenLoaded('licenses', function () {
                return $this->licenses->count();
            }, function () {
                return $this->licenses_count ?? $this->licenses()->count();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'title'       => $this->title,
            'forbidden'   => $this->getForbidden() ?? [],
            'users_count' => $this->when($this->relationLoaded('users'), fn () => $this->users->count()),
            'created_at'  => $this->created_at?->format('c'),
            'updated_at'  => $this->updated_at?->format('c'),
        ];
    }
}

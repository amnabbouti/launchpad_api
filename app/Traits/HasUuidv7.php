<?php

declare(strict_types = 1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Symfony\Component\Uid\Uuid;

trait HasUuidv7 {
    use HasUuids;

    /**
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string {
        return (string) Uuid::v7();
    }

    /**
     * Get the columns that should receive a unique identifier.
     */
    public function uniqueIds(): array {
        return [$this->getKeyName()];
    }
}

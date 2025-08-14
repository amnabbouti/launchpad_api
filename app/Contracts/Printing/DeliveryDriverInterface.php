<?php

declare(strict_types = 1);

namespace App\Contracts\Printing;

interface DeliveryDriverInterface {
    /**
     * Deliver payload to the target destination (e.g., TCP printer, IPP queue).
     * Should return an artifact path or identifier when applicable.
     */
    public function deliver(string $payload, array $context = []): ?string;
}

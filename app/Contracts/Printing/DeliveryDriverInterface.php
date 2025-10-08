<?php

declare(strict_types=1);

namespace App\Contracts\Printing;

interface DeliveryDriverInterface
{
    /**
     * Deliver payload to the target destination (e.g., TCP printer, IPP queue).
     */
    public function deliver(string $payload, array $context = []): ?string;
}

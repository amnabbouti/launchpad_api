<?php

declare(strict_types = 1);

namespace App\Drivers\Printing;

use App\Contracts\Printing\DeliveryDriverInterface;
use Illuminate\Support\Facades\Storage;

final class FileDeliveryDriver implements DeliveryDriverInterface {
    public function deliver(string $payload, array $context = []): ?string {
        $orgId  = (string) ($context['org_id'] ?? 'system');
        $format = (string) ($context['format'] ?? 'zpl');
        $prefix = (string) ($context['prefix'] ?? 'labels');

        $directory = $prefix . '/' . $orgId . '/' . date('Y/m/d');
        $filename  = date('His') . '_' . bin2hex(random_bytes(4)) . '.' . $format;
        $path      = $directory . '/' . $filename;

        if ($format === 'pdf') {
            $decoded = base64_decode($payload, true);
            if ($decoded !== false) {
                $payload = $decoded;
            }
        }

        Storage::disk('local')->put($path, $payload);

        return Storage::disk('local')->path($path);
    }
}

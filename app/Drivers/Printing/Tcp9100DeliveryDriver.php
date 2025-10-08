<?php

declare(strict_types = 1);

namespace App\Drivers\Printing;

use App\Contracts\Printing\DeliveryDriverInterface;
use InvalidArgumentException;
use RuntimeException;

final class Tcp9100DeliveryDriver implements DeliveryDriverInterface {
    public function deliver(string $payload, array $context = []): ?string {
        $host    = (string) ($context['host'] ?? '');
        $port    = (int) ($context['port'] ?? 9100);
        $timeout = (int) ($context['timeout'] ?? 5);

        if ($host === '' || $port <= 0) {
            throw new InvalidArgumentException('Host and port are required for TCP delivery');
        }

        $address = "tcp://{$host}:{$port}";
        $errno   = 0;
        $errstr  = '';
        $stream  = @stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (! $stream) {
            throw new RuntimeException("Failed to connect to {$address}: {$errstr} ({$errno})");
        }

        stream_set_timeout($stream, $timeout);
        $bytesWritten = fwrite($stream, $payload);
        fflush($stream);
        fclose($stream);

        if ($bytesWritten === false || $bytesWritten < mb_strlen($payload)) {
            throw new RuntimeException('Failed to write complete payload to TCP socket');
        }

        return $address;
    }
}

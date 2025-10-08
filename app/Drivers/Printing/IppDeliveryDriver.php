<?php

declare(strict_types = 1);

namespace App\Drivers\Printing;

use App\Contracts\Printing\DeliveryDriverInterface;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;
use Throwable;

use const DIRECTORY_SEPARATOR;

final class IppDeliveryDriver implements DeliveryDriverInterface {
    public function deliver(string $payload, array $context = []): ?string {
        $format   = (string) ($context['format'] ?? 'pdf');
        $isBase64 = (bool) ($context['is_base64'] ?? true);
        $uri      = (string) ($context['uri'] ?? '');
        $jobName  = (string) ($context['job_name'] ?? 'IPP Job');
        $username = (string) ($context['username'] ?? 'system');

        $binary = $payload;
        if ($isBase64) {
            $decoded = base64_decode($payload, true);
            if ($decoded !== false) {
                $binary = $decoded;
            }
        }

        // If no IPP URI provided, nothing to send
        if ($uri === '') {
            return null;
        }

        // Create a temporary file to pass to the IPP library
        $tmpDir  = sys_get_temp_dir();
        $tmpFile = mb_rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ipp_' . bin2hex(random_bytes(6)) . '.' . $format;
        file_put_contents($tmpFile, $binary);

        try {
            $client         = new Client;
            $builder        = new Builder;
            $responseParser = new ResponseParser;
            $printerManager = new PrinterManager($builder, $client, $responseParser);
            $jobManager     = new JobManager($builder, $client, $responseParser);

            $printer = $printerManager->findByUri($uri);
            $job     = new Job;
            $job->setName($jobName);
            $job->setUsername($username);
            $job->addFile($tmpFile);

            $jobManager->send($printer, $job);
        } catch (Throwable $e) {
            @unlink($tmpFile);

            throw $e;
        }

        @unlink($tmpFile);

        return null;
    }
}

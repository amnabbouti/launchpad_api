<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Drivers\Printing\FileDeliveryDriver;
use App\Drivers\Printing\IppDeliveryDriver;
use App\Drivers\Printing\Tcp9100DeliveryDriver;
use App\Models\PrintJob;
use App\Services\LabelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessPrintJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $jobId) {}

    public function handle(LabelService $labelService, Tcp9100DeliveryDriver $tcp, FileDeliveryDriver $file, IppDeliveryDriver $ipp): void {
        /** @var PrintJob $job */
        $job = PrintJob::query()->findOrFail($this->jobId);
        $job->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $artifact = null;
            if ($job->printer && $job->printer->driver === 'zpl') {
                $payload  = $labelService->generateZpl($job->entity_type, $job->entity_ids, (array) $job->options);
                $artifact = $tcp->deliver($payload, [
                    'host' => $job->printer->host,
                    'port' => (int) ($job->printer->port ?? 9100),
                ]);
            } elseif ($job->printer && $job->printer->driver === 'ipp') {
                $payload = $labelService->generate('pdf', $job->entity_type, $job->entity_ids, (array) $job->options);
                $config  = (array) ($job->printer->config ?? []);
                $uri     = (string) ($config['uri'] ?? '');
                if ($uri === '') {
                    // Fallback to file if IPP URI is not configured on the printer
                    $artifact = $file->deliver($payload, [
                        'org_id'    => (string) ($job->org_id ?? 'system'),
                        'format'    => 'pdf',
                        'prefix'    => 'printjobs',
                        'is_base64' => true,
                    ]);
                } else {
                    $artifact = $ipp->deliver($payload, [
                        'uri'       => $uri,
                        'org_id'    => (string) ($job->org_id ?? 'system'),
                        'format'    => 'pdf',
                        'is_base64' => true,
                        'job_name'  => 'PrintJob ' . $job->id,
                        'username'  => (string) ($job->user->name ?? 'system'),
                    ]);
                }
            } else {
                // Default: persist ZPL payload when no supported driver is selected
                $payload  = $labelService->generateZpl($job->entity_type, $job->entity_ids, (array) $job->options);
                $artifact = $file->deliver($payload, [
                    'org_id' => (string) ($job->org_id ?? 'system'),
                    'format' => 'zpl',
                ]);
            }

            $job->update([
                'status'        => 'done',
                'finished_at'   => now(),
                'artifact_path' => $artifact,
            ]);
        } catch (Throwable $e) {
            Log::error('Print job failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            $job->update([
                'status'        => 'failed',
                'finished_at'   => now(),
                'error_code'    => class_basename($e),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

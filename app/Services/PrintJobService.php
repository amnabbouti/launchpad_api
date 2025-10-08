<?php

declare(strict_types = 1);

namespace App\Services;

use App\Jobs\ProcessPrintJob;
use App\Models\PrintJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function array_key_exists;
use function in_array;
use function is_array;

class PrintJobService extends BaseService {
    public function __construct(PrintJob $printJob) {
        parent::__construct($printJob);
    }

    public function createJob(array $data): PrintJob {
        $data = $this->applyPrintJobBusinessRules($data);
        $this->validatePrintJobBusinessRules($data);

        $job = DB::transaction(fn () => $this->create($data));
        // Run synchronously for now to avoid DB queue RLS issues. We'll switch to Redis later.
        ProcessPrintJob::dispatchSync($job->id);

        return $job;
    }

    public function deleteJob(string $jobId): bool {
        return DB::transaction(fn () => $this->delete($jobId));
    }

    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['status'] ?? null, static fn ($q, $value) => $q->where('status', $value))
            ->when($filters['format'] ?? null, static fn ($q, $value) => $q->where('format', $value))
            ->when($filters['entity_type'] ?? null, static fn ($q, $value) => $q->where('entity_type', $value))
            ->when($filters['preset'] ?? null, static fn ($q, $value) => $q->where('preset', $value))
            ->when($filters['printer_id'] ?? null, static fn ($q, $value) => $q->where('printer_id', $value))
            ->when($filters['user_id'] ?? null, static fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'status'      => $this->toString($params['status'] ?? null),
            'format'      => $this->toString($params['format'] ?? null),
            'entity_type' => $this->toString($params['entity_type'] ?? null),
            'preset'      => $this->toString($params['preset'] ?? null),
            'printer_id'  => $this->toString($params['printer_id'] ?? null),
            'user_id'     => $this->toString($params['user_id'] ?? null),
            'with'        => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function updateJob(string $jobId, array $data): PrintJob {
        $data = $this->applyPrintJobBusinessRules($data, $jobId);
        $this->validatePrintJobBusinessRules($data, $jobId);

        return DB::transaction(fn () => $this->update($jobId, $data));
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'status',
            'format',
            'entity_type',
            'preset',
            'printer_id',
            'user_id',
        ]);
    }

    protected function getValidRelations(): array {
        return [
            'organization',
            'user',
            'printer',
        ];
    }

    private function applyPrintJobBusinessRules(array $data, $jobId = null): array {
        if (! isset($data['status'])) {
            $data['status'] = 'queued';
        }

        if (isset($data['copies'])) {
            $data['copies'] = max(1, (int) $data['copies']);
        }

        if (isset($data['format'])) {
            $data['format'] = mb_strtolower((string) $data['format']);
        }

        if (isset($data['entity_ids']) && is_array($data['entity_ids'])) {
            $data['entity_ids'] = array_values($data['entity_ids']);
        }

        return $data;
    }

    private function validatePrintJobBusinessRules(array $data, $jobId = null): void {
        if (! $jobId) {
            foreach (['entity_type', 'entity_ids', 'format'] as $required) {
                if (! array_key_exists($required, $data) || $data[$required] === null || $data[$required] === '') {
                    throw new InvalidArgumentException("The {$required} field is required");
                }
            }
            if (! is_array($data['entity_ids']) || empty($data['entity_ids'])) {
                throw new InvalidArgumentException('The entity_ids field must be a non-empty array');
            }
        }

        if (isset($data['format'])) {
            $allowedFormats = ['zpl', 'pdf', 'png', 'svg'];
            if (! in_array($data['format'], $allowedFormats, true)) {
                throw new InvalidArgumentException('The format must be one of: ' . implode(',', $allowedFormats));
            }
        }

        if (isset($data['status'])) {
            $allowedStatuses = ['queued', 'processing', 'done', 'failed'];
            if (! in_array($data['status'], $allowedStatuses, true)) {
                throw new InvalidArgumentException('The status must be one of: ' . implode(',', $allowedStatuses));
            }
        }
    }
}

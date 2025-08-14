<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Printer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function in_array;

class PrinterService extends BaseService {
    public function __construct(Printer $printer) {
        parent::__construct($printer);
    }

    public function createPrinter(array $data): Printer {
        $data = $this->applyPrinterBusinessRules($data);
        $this->validatePrinterBusinessRules($data);

        return DB::transaction(fn () => $this->create($data));
    }

    public function deletePrinter(string $printerId): bool {
        return DB::transaction(fn () => $this->delete($printerId));
    }

    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, static fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['driver'] ?? null, static fn ($q, $value) => $q->where('driver', $value))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'name'      => $this->toString($params['name'] ?? null),
            'driver'    => $this->toString($params['driver'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with'      => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function updatePrinter(string $printerId, array $data): Printer {
        $data = $this->applyPrinterBusinessRules($data, $printerId);
        $this->validatePrinterBusinessRules($data, $printerId);

        return DB::transaction(fn () => $this->update($printerId, $data));
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'driver',
            'is_active',
        ]);
    }

    protected function getValidRelations(): array {
        return [
            'organization',
            'printJobs',
        ];
    }

    private function applyPrinterBusinessRules(array $data, $printerId = null): array {
        if (isset($data['driver'])) {
            $data['driver'] = mb_strtolower((string) $data['driver']);
        }

        return $data;
    }

    private function validatePrinterBusinessRules(array $data, $printerId = null): void {
        if (! $printerId) {
            if (empty($data['name'])) {
                throw new InvalidArgumentException('The name field is required');
            }
            if (empty($data['driver'])) {
                throw new InvalidArgumentException('The driver field is required');
            }
        }

        if (isset($data['driver'])) {
            $allowed = ['zpl', 'ipp', 'qztray'];
            if (! in_array($data['driver'], $allowed, true)) {
                throw new InvalidArgumentException('The driver must be one of: ' . implode(',', $allowed));
            }
        }

        if (isset($data['port']) && $data['port'] !== null) {
            if (! is_numeric($data['port']) || (int) $data['port'] < 1 || (int) $data['port'] > 65535) {
                throw new InvalidArgumentException('The port must be a valid TCP port');
            }
        }
    }
}

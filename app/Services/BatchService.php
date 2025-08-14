<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class BatchService extends BaseService {
    public function __construct(Batch $batch) {
        parent::__construct($batch);
    }

    /**
     * Create a new batch.
     */
    public function createBatch(array $data): Model {
        $data = $this->applyBatchBusinessRules($data);
        $this->validateBatchBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Delete a batch.
     */
    public function deleteBatch(string $id): bool {
        return $this->delete($id);
    }

    /**
     * Get filtered batches with organization scoping.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        // Apply filters
        $query->when($filters['supplier_id'] ?? null, static fn ($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['batch_number'] ?? null, static fn ($q, $value) => $q->where('batch_number', 'like', "%{$value}%"))
            ->when($filters['received_date'] ?? null, static fn ($q, $value) => $q->whereDate('received_date', $value))
            ->when($filters['expiry_date'] ?? null, static fn ($q, $value) => $q->whereDate('expiry_date', $value))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['expired']), static function ($q) use ($filters) {
                if ($filters['expired']) {
                    return $q->where('expiry_date', '<', now());
                }

                return $q->where(static function ($subQ): void {
                    $subQ->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
                });
            })
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array {
        // Validate parameters against the allowlist
        $this->validateParams($params);

        return [
            'supplier_id'   => $this->toString($params['supplier_id'] ?? null),
            'batch_number'  => $this->toString($params['batch_number'] ?? null),
            'received_date' => $this->toString($params['received_date'] ?? null),
            'expiry_date'   => $this->toString($params['expiry_date'] ?? null),
            'is_active'     => $this->toBool($params['is_active'] ?? null),
            'expired'       => $this->toBool($params['expired'] ?? null),
            'with'          => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Update a batch.
     */
    public function updateBatch(string $id, array $data): Model {
        $data = $this->applyBatchBusinessRules($data);
        $this->validateBatchBusinessRules($data, $id);

        return $this->update($id, $data);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'supplier_id',
            'batch_number',
            'received_date',
            'expiry_date',
            'is_active',
            'expired',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array {
        return ['supplier', 'items'];
    }

    /**
     * Apply business rules for batch operations.
     */
    private function applyBatchBusinessRules(array $data): array {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for batch operations.
     */
    private function validateBatchBusinessRules(array $data, $batchId = null): void {
        if (isset($data['batch_number'])) {
            $query = Batch::where('batch_number', $data['batch_number']);

            if ($batchId) {
                $query->where('id', '!=', $batchId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException(__(ErrorMessages::BATCH_NUMBER_EXISTS));
            }
        }

        // Validate unit cost is non-negative
        if (isset($data['unit_cost']) && ($data['unit_cost'] < 0)) {
            throw new InvalidArgumentException(__(ErrorMessages::BATCH_NEGATIVE_COST));
        }

        // Validate date relationships
        if (isset($data['expiry_date'], $data['received_date'])  ) {
            $receivedDate = Carbon::parse($data['received_date']);
            $expiryDate   = Carbon::parse($data['expiry_date']);

            if ($expiryDate->isBefore($receivedDate)) {
                throw new InvalidArgumentException(__(ErrorMessages::BATCH_INVALID_DATES));
            }
        }
    }
}

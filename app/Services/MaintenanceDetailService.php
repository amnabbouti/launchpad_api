<?php

namespace App\Services;

use App\Models\MaintenanceDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaintenanceDetailService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(MaintenanceDetail $maintenanceDetail)
    {
        parent::__construct($maintenanceDetail);
    }

    /**
     * Process request parameters with explicit validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist for security
        $this->validateParams($params);

        return [
            'maintenance_id' => $this->toInt($params['maintenance_id'] ?? null),
            'maintenance_condition_id' => $this->toInt($params['maintenance_condition_id'] ?? null),
            'value' => isset($params['value']) && is_numeric($params['value']) ? (float) $params['value'] : null,
            'created_at_from' => $this->toString($params['created_at_from'] ?? null),
            'created_at_to' => $this->toString($params['created_at_to'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered maintenance details with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['maintenance_id'] ?? null, fn ($q, $id) => $q->where('maintenance_id', $id))
            ->when($filters['maintenance_condition_id'] ?? null, fn ($q, $id) => $q->where('maintenance_condition_id', $id))
            ->when(isset($filters['value']), fn ($q) => $q->where('value', $filters['value']))
            ->when($filters['created_at_from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date))
            ->when($filters['created_at_to'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', $date))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Create a new maintenance detail with validation.
     */
    public function createMaintenanceDetail(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a maintenance detail with validation.
     */
    public function updateMaintenanceDetail(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Get the latest maintenance detail for a condition.
     */
    public function getLatestForCondition(int $maintenanceConditionId): ?Model
    {
        return $this->getQuery()
            ->where('maintenance_condition_id', $maintenanceConditionId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'maintenance_id', 'maintenance_condition_id', 'value', 
            'created_at_from', 'created_at_to',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['maintenance', 'maintenanceCondition'];
    }
}

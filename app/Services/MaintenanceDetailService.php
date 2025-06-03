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
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'with' => isset($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
            'maintenance_id' => isset($params['maintenance_id']) ? (int) $params['maintenance_id'] : null,
            'maintenance_condition_id' => isset($params['maintenance_condition_id']) ? (int) $params['maintenance_condition_id'] : null,
            'value' => isset($params['value']) ? (float) $params['value'] : null,
            'created_at_from' => $params['created_at_from'] ?? null,
            'created_at_to' => $params['created_at_to'] ?? null,
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
     * Create a new maintenance detail with validated data.
     */
    public function createMaintenanceDetail(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a maintenance detail with validated data.
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
}

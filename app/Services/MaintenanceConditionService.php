<?php

namespace App\Services;

use App\Models\MaintenanceCondition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaintenanceConditionService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(MaintenanceCondition $maintenanceCondition)
    {
        parent::__construct($maintenanceCondition);
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
            'item_id' => isset($params['item_id']) ? (int) $params['item_id'] : null,
            'maintenance_category_id' => isset($params['maintenance_category_id']) ? (int) $params['maintenance_category_id'] : null,
            'unit_of_measure_id' => isset($params['unit_of_measure_id']) ? (int) $params['unit_of_measure_id'] : null,
            'is_active' => isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            'due_for_warning' => isset($params['due_for_warning']) ? filter_var($params['due_for_warning'], FILTER_VALIDATE_BOOLEAN) : null,
            'due_for_maintenance' => isset($params['due_for_maintenance']) ? filter_var($params['due_for_maintenance'], FILTER_VALIDATE_BOOLEAN) : null,
        ];
    }

    /**
     * Get filtered maintenance conditions with organization scoping.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['item_id'] ?? null, fn ($q, $id) => $q->where('item_id', $id))
            ->when($filters['maintenance_category_id'] ?? null, fn ($q, $id) => $q->where('maintenance_category_id', $id))
            ->when($filters['unit_of_measure_id'] ?? null, fn ($q, $id) => $q->where('unit_of_measure_id', $id))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['due_for_warning'] ?? null, fn ($q) => $q->where('is_active', true)
                ->whereNotNull('maintenance_warning_date')
                ->where('maintenance_warning_date', '<=', now()))
            ->when($filters['due_for_maintenance'] ?? null, fn ($q) => $q->where('is_active', true)
                ->whereNotNull('maintenance_date')
                ->where('maintenance_date', '<=', now()))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Create a new maintenance condition.
     */
    public function createMaintenanceCondition(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a maintenance condition.
     */
    public function updateMaintenanceCondition(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Get conditions due for warning.
     */
    public function getDueForWarning(): Collection
    {
        return $this->getFiltered(['due_for_warning' => true]);
    }

    /**
     * Get conditions due for maintenance.
     */
    public function getDueForMaintenance(): Collection
    {
        return $this->getFiltered(['due_for_maintenance' => true]);
    }
}

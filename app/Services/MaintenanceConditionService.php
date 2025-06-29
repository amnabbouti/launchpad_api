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
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'item_id' => $this->toInt($params['item_id'] ?? null),
            'maintenance_category_id' => $this->toInt($params['maintenance_category_id'] ?? null),
            'unit_of_measure_id' => $this->toInt($params['unit_of_measure_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'due_for_warning' => $this->toBool($params['due_for_warning'] ?? null),
            'due_for_maintenance' => $this->toBool($params['due_for_maintenance'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
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

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'item_id', 'maintenance_category_id', 'unit_of_measure_id', 
            'is_active', 'due_for_warning', 'due_for_maintenance',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'organization',
            'item', 
            'statusWhenReturned', 
            'statusWhenExceeded', 
            'maintenanceCategory', 
            'unitOfMeasure', 
            'maintenanceDetails'
        ];
    }
}

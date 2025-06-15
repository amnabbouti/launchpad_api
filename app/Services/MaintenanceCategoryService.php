<?php

namespace App\Services;

use App\Models\MaintenanceCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCategoryService extends BaseService
{
    public function __construct(MaintenanceCategory $maintenanceCategory)
    {
        parent::__construct($maintenanceCategory);
    }

    /**
     * Process request parameters with explicit validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist.
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered maintenance categories.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Create a new maintenance category with validation.
     */
    public function createMaintenanceCategory(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a maintenance category with validation.
     */
    public function updateMaintenanceCategory(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Get only active maintenance categories.
     */
    public function getActive(): Collection
    {
        return $this->getFiltered(['is_active' => true]);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'name', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['maintenances'];
    }
}

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
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'with' => isset($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
            'name' => $params['name'] ?? null,
            'is_active' => isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
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
     * Create a new maintenance category with validated data.
     */
    public function createMaintenanceCategory(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a maintenance category with validated data.
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
}

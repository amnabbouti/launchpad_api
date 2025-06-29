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
        $data = $this->applyMaintenanceCategoryBusinessRules($data);
        $this->validateMaintenanceCategoryBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Update a maintenance category with validation.
     */
    public function updateMaintenanceCategory(int $id, array $data): Model
    {
        $data = $this->applyMaintenanceCategoryBusinessRules($data, $id);
        $this->validateMaintenanceCategoryBusinessRules($data, $id);

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
            'org_id', 'name', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['maintenances'];
    }

    /**
     * Apply business rules for maintenance category operations.
     */
    private function applyMaintenanceCategoryBusinessRules(array $data, $categoryId = null): array
    {
        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for maintenance category operations.
     * This handles the complex validation logic that was in MaintenanceCategoryRequest.
     */
    private function validateMaintenanceCategoryBusinessRules(array $data, $categoryId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The maintenance category name is required');
        }

        if (empty($data['org_id'])) {
            throw new \InvalidArgumentException('The organization ID is required');
        }

        // Validate organization exists
        $organization = \App\Models\Organization::find($data['org_id']);
        if (!$organization) {
            throw new \InvalidArgumentException('The selected organization is invalid');
        }

        // Validate name uniqueness within organization
        // This replaces: Rule::unique('maintenance_categories')->where('org_id', $this->org_id)->ignore($categoryId)
        $query = MaintenanceCategory::where('name', $data['name'])
                                   ->where('org_id', $data['org_id']);
        
        if ($categoryId) {
            $query->where('id', '!=', $categoryId);
        }
        
        if ($query->exists()) {
            throw new \InvalidArgumentException('This maintenance category name already exists for the organization');
        }
    }
}

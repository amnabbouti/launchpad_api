<?php

namespace App\Services;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasureService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(UnitOfMeasure $unitOfMeasure)
    {
        parent::__construct($unitOfMeasure);
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'code' => $this->toString($params['code'] ?? null),
            'type' => $this->toString($params['type'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered units of measure.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('code', $code))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Get units of measure by name.
     */
    public function getByName(string $name): Collection
    {
        return $this->getFiltered(['name' => $name]);
    }

    /**
     * Get only active units of measure.
     */
    public function getActive(): Collection
    {
        return $this->getFiltered(['is_active' => true]);
    }

    /**
     * Create a new unit of measure with validaion
     */
    public function createUnitOfMeasure(array $data): Model
    {
        $data = $this->applyUnitOfMeasureBusinessRules($data);
        $this->validateUnitOfMeasureBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Update a unit of measure with validation
     */
    public function updateUnitOfMeasure(int $id, array $data): Model
    {
        $data = $this->applyUnitOfMeasureBusinessRules($data, $id);
        $this->validateUnitOfMeasureBusinessRules($data, $id);

        return $this->update($id, $data);
    }

    /**
     * Get units of measure by code.
     */
    public function getByCode(string $code): Collection
    {
        return $this->getFiltered(['code' => $code]);
    }

    /**
     * Get units of measure by type.
     */
    public function getByType(string $type): Collection
    {
        return $this->getFiltered(['type' => $type]);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'name', 'code', 'type', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['maintenanceConditions'];
    }

    /**
     * Apply business rules for unit of measure operations.
     */
    private function applyUnitOfMeasureBusinessRules(array $data, $unitId = null): array
    {
        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for unit of measure operations.
     * This handles the complex validation logic that was in UnitOfMeasureRequest.
     */
    private function validateUnitOfMeasureBusinessRules(array $data, $unitId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The unit name is required');
        }

        if (empty($data['type'])) {
            throw new \InvalidArgumentException('The unit type is required');
        }

        if (empty($data['org_id'])) {
            throw new \InvalidArgumentException('Organization ID is required');
        }

        // Validate type is valid
        $validTypes = [
            UnitOfMeasure::TYPE_DATE,
            UnitOfMeasure::TYPE_DAYS_ACTIVE,
            UnitOfMeasure::TYPE_DAYS_CHECKED_OUT,
            UnitOfMeasure::TYPE_QUANTITY,
            UnitOfMeasure::TYPE_DISTANCE,
            UnitOfMeasure::TYPE_WEIGHT,
            UnitOfMeasure::TYPE_VOLUME,
        ];

        if (!in_array($data['type'], $validTypes)) {
            throw new \InvalidArgumentException('The selected unit type is invalid');
        }

        // Validate code uniqueness within organization if provided
        // This replaces: unique:unit_of_measures,code,{id},id,org_id,{orgId}
        if (isset($data['code']) && !empty($data['code'])) {
            $query = UnitOfMeasure::where('code', $data['code'])
                                  ->where('org_id', $data['org_id']);
            
            if ($unitId) {
                $query->where('id', '!=', $unitId);
            }
            
            if ($query->exists()) {
                throw new \InvalidArgumentException('This unit code is already used in your organization');
            }
        }
    }
}

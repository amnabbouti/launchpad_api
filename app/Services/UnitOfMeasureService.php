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
        return $this->create($data);
    }

    /**
     * Update a unit of measure with validation
     */
    public function updateUnitOfMeasure(int $id, array $data): Model
    {
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
            'name', 'code', 'type', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['maintenanceConditions'];
    }
}

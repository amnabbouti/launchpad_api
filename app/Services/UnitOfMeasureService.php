<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
    public function getFiltered(array $filters = []): Builder
    {
        return $this->getQuery()
            ->when($filters['name'] ?? null, fn($q, $name) => $q->where('name', 'like', "%$name%"))
            ->when($filters['code'] ?? null, fn($q, $code) => $q->where('code', $code))
            ->when($filters['type'] ?? null, fn($q, $type) => $q->where('type', $type))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn($q, $with) => $q->with($with));
    }

    /**
     * Apply business rules for unit of measure operations.
     */
    private function applyUnitOfMeasureBusinessRules(array $data): array
    {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for unit of measure operations.
     */
    private function validateUnitOfMeasureBusinessRules(array $data, $unitId = null): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException(__(ErrorMessages::UNIT_OF_MEASURE_NAME_REQUIRED));
        }

        if (empty($data['type'])) {
            throw new InvalidArgumentException(__(ErrorMessages::UNIT_OF_MEASURE_TYPE_REQUIRED));
        }

        if (empty($data['org_id'])) {
            throw new InvalidArgumentException(__(ErrorMessages::UNIT_OF_MEASURE_ORG_REQUIRED));
        }

        $validTypes = [
            UnitOfMeasure::TYPE_DATE,
            UnitOfMeasure::TYPE_DAYS_ACTIVE,
            UnitOfMeasure::TYPE_DAYS_CHECKED_OUT,
            UnitOfMeasure::TYPE_QUANTITY,
            UnitOfMeasure::TYPE_DISTANCE,
            UnitOfMeasure::TYPE_WEIGHT,
            UnitOfMeasure::TYPE_VOLUME,
        ];

        if (! in_array($data['type'], $validTypes)) {
            throw new InvalidArgumentException(__(ErrorMessages::UNIT_OF_MEASURE_TYPE_INVALID));
        }

        // Validate code uniqueness within an organization if provided
        if (! empty($data['code'])) {
            $query = UnitOfMeasure::where('code', $data['code'])
                ->where('org_id', $data['org_id']);

            if ($unitId) {
                $query->where('id', '!=', $unitId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException(__(ErrorMessages::UNIT_OF_MEASURE_CODE_EXISTS));
            }
        }
    }

    /**
     * Create a new unit of measure with validation
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
        $data = $this->applyUnitOfMeasureBusinessRules($data);
        $this->validateUnitOfMeasureBusinessRules($data, $id);

        return $this->update($id, $data);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'name',
            'code',
            'type',
            'is_active',
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

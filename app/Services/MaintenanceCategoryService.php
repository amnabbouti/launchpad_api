<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\MaintenanceCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MaintenanceCategoryService extends BaseService
{
    public function __construct(MaintenanceCategory $maintenanceCategory)
    {
        parent::__construct($maintenanceCategory);
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function getFiltered(array $filters = []): Builder
    {
        return $this->getQuery()
            ->when($filters['name'] ?? null, fn($q, $name) => $q->where('name', 'like', "%$name%"))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn($q, $with) => $q->with($with));
    }

    public function createMaintenanceCategory(array $data): Model
    {
        $data = $this->applyMaintenanceCategoryBusinessRules($data);
        $this->validateMaintenanceCategoryBusinessRules($data);

        return $this->create($data);
    }

    public function updateMaintenanceCategory(int $id, array $data): Model
    {
        $data = $this->applyMaintenanceCategoryBusinessRules($data, $id);
        $this->validateMaintenanceCategoryBusinessRules($data, $id);

        return $this->update($id, $data);
    }

    public function getActive(): Collection
    {
        return $this->getFiltered(['is_active' => true])->get();
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'name',
            'is_active',
        ]);
    }

    protected function getValidRelations(): array
    {
        return ['maintenances'];
    }

    private function applyMaintenanceCategoryBusinessRules(array $data, $categoryId = null): array
    {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    private function validateMaintenanceCategoryBusinessRules(array $data, $categoryId = null): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException(__(ErrorMessages::MAINTENANCE_CATEGORY_NAME_REQUIRED));
        }

        if (empty($data['org_id'])) {
            throw new InvalidArgumentException(__(ErrorMessages::ORG_REQUIRED));
        }

        $organization = Organization::find($data['org_id']);
        if (! $organization) {
            throw new InvalidArgumentException(__(ErrorMessages::INVALID_ORG));
        }

        $query = MaintenanceCategory::where('name', $data['name'])
            ->where('org_id', $data['org_id']);

        if ($categoryId) {
            $query->where('id', '!=', $categoryId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException(__(ErrorMessages::MAINTENANCE_CATEGORY_NAME_EXISTS));
        }
    }
}

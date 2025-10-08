<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\MaintenanceCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaintenanceConditionService extends BaseService {
    public function __construct(MaintenanceCondition $maintenanceCondition) {
        parent::__construct($maintenanceCondition);
    }

    public function createMaintenanceCondition(array $data): Model {
        return $this->create($data);
    }

    public function getDueForMaintenance(): Collection {
        return $this->getFiltered(['due_for_maintenance' => true])->get();
    }

    public function getDueForWarning(): Collection {
        return $this->getFiltered(['due_for_warning' => true])->get();
    }

    public function getFiltered(array $filters = []): Builder {
        return $this->getQuery()
            ->when($filters['item_id'] ?? null, static fn ($q, $id) => $q->where('item_id', $id))
            ->when($filters['maintenance_category_id'] ?? null, static fn ($q, $id) => $q->where('maintenance_category_id', $id))
            ->when($filters['unit_of_measure_id'] ?? null, static fn ($q, $id) => $q->where('unit_of_measure_id', $id))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['due_for_warning'] ?? null, static fn ($q) => $q->where('is_active', true)
                ->whereNotNull('maintenance_warning_date')
                ->where('maintenance_warning_date', '<=', now()))
            ->when($filters['due_for_maintenance'] ?? null, static fn ($q) => $q->where('is_active', true)
                ->whereNotNull('maintenance_date')
                ->where('maintenance_date', '<=', now()))
            ->when($filters['with'] ?? null, static fn ($q, $with) => $q->with($with));
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'item_id'                 => $this->toInt($params['item_id'] ?? null),
            'maintenance_category_id' => $this->toInt($params['maintenance_category_id'] ?? null),
            'unit_of_measure_id'      => $this->toInt($params['unit_of_measure_id'] ?? null),
            'is_active'               => $this->toBool($params['is_active'] ?? null),
            'due_for_warning'         => $this->toBool($params['due_for_warning'] ?? null),
            'due_for_maintenance'     => $this->toBool($params['due_for_maintenance'] ?? null),
            'with'                    => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function updateMaintenanceCondition(string $id, array $data): Model {
        return $this->update($id, $data);
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'item_id',
            'maintenance_category_id',
            'unit_of_measure_id',
            'is_active',
            'due_for_warning',
            'due_for_maintenance',
        ]);
    }

    protected function getValidRelations(): array {
        return [
            'organization',
            'item',
            'statusWhenReturned',
            'statusWhenExceeded',
            'maintenanceCategory',
            'unitOfMeasure',
            'maintenanceDetails',
        ];
    }
}

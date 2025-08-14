<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\MaintenanceDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaintenanceDetailService extends BaseService {
    public function __construct(MaintenanceDetail $maintenanceDetail) {
        parent::__construct($maintenanceDetail);
    }

    public function createMaintenanceDetail(array $data): Model {
        return $this->create($data);
    }

    public function getFiltered(array $filters = []): Builder {
        return $this->getQuery()
            ->when($filters['maintenance_id'] ?? null, static fn ($q, $id) => $q->where('maintenance_id', $id))
            ->when($filters['maintenance_condition_id'] ?? null, static fn ($q, $id) => $q->where('maintenance_condition_id', $id))
            ->when(isset($filters['value']), static fn ($q) => $q->where('value', $filters['value']))
            ->when($filters['created_at_from'] ?? null, static fn ($q, $date) => $q->where('created_at', '>=', $date))
            ->when($filters['created_at_to'] ?? null, static fn ($q, $date) => $q->where('created_at', '<=', $date))
            ->when($filters['with'] ?? null, static fn ($q, $with) => $q->with($with));
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'maintenance_id'           => $this->toInt($params['maintenance_id'] ?? null),
            'maintenance_condition_id' => $this->toInt($params['maintenance_condition_id'] ?? null),
            'value'                    => isset($params['value']) && is_numeric($params['value']) ? (float) $params['value'] : null,
            'created_at_from'          => $this->toString($params['created_at_from'] ?? null),
            'created_at_to'            => $this->toString($params['created_at_to'] ?? null),
            'with'                     => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function updateMaintenanceDetail(string $id, array $data): Model {
        return $this->update($id, $data);
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'maintenance_id',
            'maintenance_condition_id',
            'value',
            'created_at_from',
            'created_at_to',
        ]);
    }

    protected function getValidRelations(): array {
        return ['maintenance', 'maintenanceCondition'];
    }
}

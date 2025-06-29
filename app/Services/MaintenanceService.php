<?php

namespace App\Services;

use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class MaintenanceService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Maintenance $maintenance)
    {
        parent::__construct($maintenance);
    }

    /**
     * Get maintenances with details.
     */
    public function getWithDetails(): Collection
    {
        return $this->getQuery()->with(['maintainable', 'user', 'supplier', 'maintenanceDetails'])->get();
    }

    /**
     * Get active maintenances.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->whereNull('date_back_from_maintenance')->get();
    }

    /**
     * Get filtered maintenance records.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters
        $query->when($filters['maintainable_id'] ?? null, fn($q, $value) => $q->where('maintainable_id', $value))
            ->when($filters['maintainable_type'] ?? null, fn($q, $value) => $q->where('maintainable_type', $value))
            ->when($filters['user_id'] ?? null, fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['supplier_id'] ?? null, fn($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['active_only'] ?? null, fn($q) => $q->whereNull('date_back_from_maintenance'))
            ->when($filters['completed_only'] ?? null, fn($q) => $q->whereNotNull('date_back_from_maintenance'))
            ->when($filters['date_from'] ?? null, fn($q, $value) => $q->where('date_in_maintenance', '>=', $value))
            ->when($filters['date_to'] ?? null, fn($q, $value) => $q->where('date_in_maintenance', '<=', $value))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'maintainable_id',
            'maintainable_type',
            'user_id',
            'supplier_id',
            'active_only',
            'completed_only',
            'date_from',
            'date_to',
        ]);
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'maintainable_id' => $this->toInt($params['maintainable_id'] ?? null),
            'maintainable_type' => $this->toString($params['maintainable_type'] ?? null),
            'user_id' => $this->toInt($params['user_id'] ?? null),
            'supplier_id' => $this->toInt($params['supplier_id'] ?? null),
            'active_only' => $this->toBool($params['active_only'] ?? null),
            'completed_only' => $this->toBool($params['completed_only'] ?? null),
            'date_from' => $this->toString($params['date_from'] ?? null),
            'date_to' => $this->toString($params['date_to'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Find maintenance by ID with all relationships
     */
    public function findByIdWithRelations($id): Maintenance
    {
        return $this->findById($id, ['*'], [
            'maintainable',
            'user',
            'supplier',
            'statusOut',
            'statusIn',
            'maintenanceDetails',
            'organization'
        ]);
    }

    /**
     * Get valid relations for the maintenance model.
     */
    protected function getValidRelations(): array
    {
        return [
            'maintainable',
            'user',
            'supplier',
            'statusOut',
            'statusIn',
            'maintenanceDetails',
            'organization'
        ];
    }
}

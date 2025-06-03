<?php

namespace App\Services;

use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->getQuery()->with(['stockItem', 'user', 'supplier', 'maintenanceDetails'])->get();
    }

    /**
     * Get maintenances by stock item.
     */
    public function getByStockItem(int $stockItemId): Collection
    {
        return $this->getQuery()->where('stock_item_id', $stockItemId)->get();
    }

    /**
     * Get active maintenances.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->whereNull('date_back_from_maintenance')->get();
    }

    /**
     * Check if stock item has active maintenance.
     */
    public function hasActiveMaintenance(int $stockItemId): bool
    {
        return $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->whereNull('date_back_from_maintenance')
            ->exists();
    }

    /**
     * Get filtered maintenance records.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters
        $query->when($filters['org_id'] ?? null, fn ($q, $value) => $q->where('org_id', $value))
            ->when($filters['stock_item_id'] ?? null, fn ($q, $value) => $q->where('stock_item_id', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['supplier_id'] ?? null, fn ($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['active_only'] ?? null, fn ($q) => $q->whereNull('date_back_from_maintenance'))
            ->when($filters['completed_only'] ?? null, fn ($q) => $q->whereNotNull('date_back_from_maintenance'))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->where('date_in_for_maintenance', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->where('date_in_for_maintenance', '<=', $value))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'stock_item_id', 'user_id', 'supplier_id',
            'active_only', 'completed_only', 'date_from', 'date_to',
        ]);
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        $processedParams = parent::processRequestParams($params);
        $processedParams['org_id'] = $params['org_id'] ?? null;
        $processedParams['stock_item_id'] = $params['stock_item_id'] ?? null;
        $processedParams['user_id'] = $params['user_id'] ?? null;
        $processedParams['supplier_id'] = $params['supplier_id'] ?? null;
        $processedParams['active_only'] = isset($params['active_only']) ? filter_var($params['active_only'], FILTER_VALIDATE_BOOLEAN) : null;
        $processedParams['completed_only'] = isset($params['completed_only']) ? filter_var($params['completed_only'], FILTER_VALIDATE_BOOLEAN) : null;
        $processedParams['date_from'] = $params['date_from'] ?? null;
        $processedParams['date_to'] = $params['date_to'] ?? null;

        return $processedParams;
    }
}

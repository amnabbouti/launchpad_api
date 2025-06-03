<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ItemService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    /**
     * Get items with their relationships.
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->getQuery()->with($relations)->get();
    }

    /**
     * Get items by category ID.
     */
    public function getByCategoryId(int $categoryId): Collection
    {
        return $this->getQuery()->where('category_id', $categoryId)->get();
    }

    /**
     * Get active items.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
    }

    /**
     * Get items with low stock.
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->getQuery()->where('quantity', '<', $threshold)->get();
    }

    /**
     * Get filtered items with organization scoping.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters using Laravel's when() method for clean conditional filtering
        $query->when($filters['org_id'] ?? null, fn ($q, $value) => $q->where('org_id', $value))
            ->when($filters['category_id'] ?? null, fn ($q, $value) => $q->where('category_id', $value))
            ->when($filters['status_id'] ?? null, fn ($q, $value) => $q->where('status_id', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['low_stock'] ?? null, fn ($q, $value) => $q->where('quantity', '<', $value))
            ->when($filters['q'] ?? null, fn ($q, $value) => $q->where(function ($subQuery) use ($value) {
                $subQuery->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%");
            }))
            ->when($filters['code'] ?? null, fn ($q, $value) => $q->where('code', $value))
            ->when($filters['barcode'] ?? null, fn ($q, $value) => $q->where('barcode', $value))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'category_id', 'status_id', 'user_id', 'low_stock',
            'q', 'code', 'barcode', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'organization', 'category', 'user', 'unitOfMeasure', 'status',
            'stockItems', 'maintenances', 'maintenanceConditions', 'suppliers', 'attachments',
        ];
    }

    /**
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        $processedParams = parent::processRequestParams($params);
        $processedParams['org_id'] = $params['org_id'] ?? null;
        $processedParams['category_id'] = isset($params['category_id']) && is_numeric($params['category_id']) ? (int) $params['category_id'] : null;
        $processedParams['status_id'] = isset($params['status_id']) && is_numeric($params['status_id']) ? (int) $params['status_id'] : null;
        $processedParams['user_id'] = isset($params['user_id']) && is_numeric($params['user_id']) ? (int) $params['user_id'] : null;
        $processedParams['low_stock'] = isset($params['low_stock']) ? filter_var($params['low_stock'], FILTER_VALIDATE_BOOLEAN) : null;
        $processedParams['is_active'] = isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
        $processedParams['q'] = $params['q'] ?? null;
        $processedParams['code'] = $params['code'] ?? null;
        $processedParams['barcode'] = $params['barcode'] ?? null;

        return $processedParams;
    }
}

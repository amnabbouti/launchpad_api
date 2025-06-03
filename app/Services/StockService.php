<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StockService extends BaseService
{
    public function __construct(Stock $stock)
    {
        parent::__construct($stock);
    }

    /**
     * Get filtered stocks with organization scoping.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters using Laravel's when() method for clean conditional filtering
        $query->when($filters['supplier_id'] ?? null, fn ($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['batch_number'] ?? null, fn ($q, $value) => $q->where('batch_number', 'like', "%{$value}%"))
            ->when($filters['received_date'] ?? null, fn ($q, $value) => $q->whereDate('received_date', $value))
            ->when($filters['expiry_date'] ?? null, fn ($q, $value) => $q->whereDate('expiry_date', $value))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['expired']), function ($q) use ($filters) {
                if ($filters['expired']) {
                    return $q->where('expiry_date', '<', now());
                }

                return $q->where(function ($subQ) {
                    $subQ->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
                });
            })
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Create a new stock.
     */
    public function createStock(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a stock.
     */
    public function updateStock(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'supplier_id' => isset($params['supplier_id']) && is_numeric($params['supplier_id']) ? (int) $params['supplier_id'] : null,
            'batch_number' => $params['batch_number'] ?? null,
            'received_date' => $params['received_date'] ?? null,
            'expiry_date' => $params['expiry_date'] ?? null,
            'is_active' => isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            'expired' => isset($params['expired']) ? filter_var($params['expired'], FILTER_VALIDATE_BOOLEAN) : null,
            'with' => ! empty($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
        ];
    }
}

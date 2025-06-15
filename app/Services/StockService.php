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

        // Apply filters
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
     * Delete a stock.
     */
    public function deleteStock(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'supplier_id' => $this->toInt($params['supplier_id'] ?? null),
            'batch_number' => $this->toString($params['batch_number'] ?? null),
            'received_date' => $this->toString($params['received_date'] ?? null),
            'expiry_date' => $this->toString($params['expiry_date'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'expired' => $this->toBool($params['expired'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'supplier_id', 'batch_number', 'received_date', 'expiry_date', 
            'is_active', 'expired',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['supplier', 'items'];
    }
}

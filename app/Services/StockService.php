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
        $data = $this->applyStockBusinessRules($data);
        $this->validateStockBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Update a stock.
     */
    public function updateStock(int $id, array $data): Model
    {
        $data = $this->applyStockBusinessRules($data, $id);
        $this->validateStockBusinessRules($data, $id);

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
            'org_id', 'supplier_id', 'batch_number', 'received_date', 'expiry_date', 
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

    /**
     * Apply business rules for stock operations.
     */
    private function applyStockBusinessRules(array $data, $stockId = null): array
    {
        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for stock operations.
     */
    private function validateStockBusinessRules(array $data, $stockId = null): void
    {
        // Validate required fields
        $requiredFields = ['org_id', 'item_id', 'supplier_id', 'quantity', 'batch_number', 'received_date', 'unit_cost'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace('_', ' ', $field);
                throw new \InvalidArgumentException("The {$fieldName} is required");
            }
        }

        // Validate numeric fields
        if (isset($data['quantity']) && ($data['quantity'] <= 0)) {
            throw new \InvalidArgumentException('The quantity must be greater than 0');
        }

        if (isset($data['unit_cost']) && ($data['unit_cost'] < 0)) {
            throw new \InvalidArgumentException('The unit cost cannot be negative');
        }

        // Validate batch number uniqueness within organization
        $query = Stock::where('batch_number', $data['batch_number'])
                     ->where('org_id', $data['org_id']);
        
        if ($stockId) {
            $query->where('id', '!=', $stockId);
        }
        
        if ($query->exists()) {
            throw new \InvalidArgumentException('This batch number already exists in your organization');
        }

        // Validate date relationships
        if (isset($data['expiry_date']) && isset($data['received_date'])) {
            $receivedDate = \Carbon\Carbon::parse($data['received_date']);
            $expiryDate = \Carbon\Carbon::parse($data['expiry_date']);
            
            if ($expiryDate->isBefore($receivedDate)) {
                throw new \InvalidArgumentException('The expiry date must be after the received date');
            }
        }
    }
}

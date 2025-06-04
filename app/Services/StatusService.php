<?php

namespace App\Services;

use App\Models\ItemStatus;
use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StatusService extends BaseService
{
    protected ItemStatus $itemStatusModel;

    /**
     * Create a new service instance.
     */
    public function __construct(Status $status, ItemStatus $itemStatus)
    {
        parent::__construct($status);
        $this->itemStatusModel = $itemStatus;
    }

    /**
     * Get allowed query parameters for status service.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'type', 'org_id', 'name', 'code', 'is_active', 'item_id', 'stock_item_id', 'description',
        ]);
    }

    /**
     * Get statuses by name (partial match).
     */
    public function getByName(string $name): Collection
    {
        return $this->getQuery()->where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Get only active statuses.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
    }

    /**
     * Create a new status with validated data.
     */
    public function createStatus(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a status with validated data.
     */
    public function updateStatus($id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Get statuses with their related items.
     */
    public function getWithItems(): Collection
    {
        return $this->getQuery()->with('items')->get();
    }

    /**
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        $processedParams = parent::processRequestParams($params);
        $processedParams['org_id'] = $params['org_id'] ?? null;
        $processedParams['name'] = $params['name'] ?? null;
        $processedParams['code'] = $params['code'] ?? null;
        $processedParams['is_active'] = isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
        $processedParams['item_id'] = $params['item_id'] ?? null;
        $processedParams['stock_item_id'] = $params['stock_item_id'] ?? null;
        $processedParams['description'] = $params['description'] ?? null;

        return $processedParams;
    }

    /**
     * Get filtered statuses with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['org_id'] ?? null, fn ($q, $value) => $q->where('org_id', $value))
            ->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('code', 'like', "%{$code}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get filtered item statuses.
     */
    public function getItemStatuses(array $filters = []): Collection
    {
        $query = $this->itemStatusModel->query();

        // Apply organization scope
        if (method_exists($this->itemStatusModel, 'scopeForOrganization') && auth()->check()) {
            $query->forOrganization(auth()->user()->org_id);
        }

        $query->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('code', 'like', "%{$code}%"))
            ->when($filters['description'] ?? null, fn ($q, $desc) => $q->where('description', 'like', "%{$desc}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['item_id'] ?? null, fn ($q, $itemId) => $q->whereHas('stockItems', fn ($stockQuery) => $stockQuery->where('item_id', $itemId)))
            ->when($filters['stock_item_id'] ?? null, fn ($q, $stockItemId) => $q->whereHas('stockItems', fn ($stockQuery) => $stockQuery->where('id', $stockItemId)))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Process request parameters for status type.
     */
    public function processStatusParams(array $params): array
    {
        // Use the main processRequestParams for validation
        return $this->processRequestParams($params);
    }

    /**
     * Process request parameters for item status type.
     */
    public function processItemStatusParams(array $params): array
    {
        // Use the main processRequestParams for validation
        return $this->processRequestParams($params);
    }

    /**
     * Create item status.
     */
    public function createItemStatus(array $data): Model
    {
        return $this->itemStatusModel->create($data);
    }

    /**
     * Update item status.
     */
    public function updateItemStatus($id, array $data): Model
    {
        $itemStatus = $this->findItemStatusById($id);
        $itemStatus->update($data);

        return $itemStatus->fresh();
    }

    /**
     * Delete item status.
     */
    public function deleteItemStatus($id): bool
    {
        $itemStatus = $this->findItemStatusById($id);

        return $itemStatus->delete();
    }

    /**
     * Find item status by ID (supports both public and internal IDs).
     */
    public function findItemStatusById($id, array $columns = ['*'], array $with = []): Model
    {
        $query = $this->itemStatusModel->query();

        if (method_exists($this->itemStatusModel, 'scopeForOrganization') && auth()->check()) {
            $query->forOrganization(auth()->user()->org_id);
        }

        if (! empty($with)) {
            $query->with($with);
        }

        // Try to find by public ID first, then fall back to internal ID
        if (is_numeric($id)) {
            $model = $query->find($id, $columns);
        } else {
            $model = $query->where('public_id', $id)->first($columns);
        }

        if (! $model) {
            throw new \InvalidArgumentException('Item status not found');
        }

        return $model;
    }
}

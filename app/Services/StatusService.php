<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StatusService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Status $status)
    {
        parent::__construct($status);
    }

    /**
     * Create status with business rules.
     */
    public function createStatus(array $data): Status
    {
        $data = $this->applyStatusBusinessRules($data);
        $this->validateStatusBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Update status with business rules.
     */
    public function updateStatus(int $statusId, array $data): Status
    {
        $data = $this->applyStatusBusinessRules($data, $statusId);
        $this->validateStatusBusinessRules($data, $statusId);

        return $this->update($statusId, $data);
    }

    /**
     * Get allowed query parameters for status service.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'name', 'code', 'is_active', 'description',
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
     * Get statuses by code.
     */
    public function getByCode(string $code): Collection
    {
        return $this->getQuery()->where('code', $code)->get();
    }

    /**
     * Get only active statuses.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
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
            'name' => $this->toString($params['name'] ?? null),
            'code' => $this->toString($params['code'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'description' => $this->toString($params['description'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered statuses with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('code', 'like', "%{$code}%"))
            ->when($filters['description'] ?? null, fn ($q, $desc) => $q->where('description', 'like', "%{$desc}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get statuses with their related items.
     */
    public function getWithItems(): Collection
    {
        return $this->getQuery()->with('items')->get();
    }

    /**
     * Apply business rules for status operations.
     */
    private function applyStatusBusinessRules(array $data, $statusId = null): array
    {
        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for status operations.
     * This handles the complex validation logic that was in StatusRequest.
     */
    private function validateStatusBusinessRules(array $data, $statusId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The status name is required');
        }

        if (empty($data['code'])) {
            throw new \InvalidArgumentException('The status code is required');
        }

        if (empty($data['org_id'])) {
            throw new \InvalidArgumentException('Organization ID is required');
        }

        // Validate organization exists
        $organization = \App\Models\Organization::find($data['org_id']);
        if (!$organization) {
            throw new \InvalidArgumentException('The selected organization does not exist');
        }

        // Validate code uniqueness within organization
        // This replaces: Rule::unique('statuses')->ignore($statusId)->where(fn ($query) => $query->where('org_id', $this->org_id))
        $query = Status::where('code', $data['code'])
                      ->where('org_id', $data['org_id']);
        
        if ($statusId) {
            $query->where('id', '!=', $statusId);
        }
        
        if ($query->exists()) {
            throw new \InvalidArgumentException('This status code already exists in your organization');
        }
    }
}

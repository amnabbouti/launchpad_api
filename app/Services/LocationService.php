<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LocationService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Location $location)
    {
        parent::__construct($location);
    }

    /**
     * Get filtered locations for the current organization.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters
        $query->when($filters['name'] ?? null, fn($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, fn($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['description'] ?? null, fn($q, $value) => $q->where('description', 'like', "%{$value}%"))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['parent_id']), function ($q) use ($filters) {
                if ($filters['parent_id'] === 'null' || $filters['parent_id'] === null) {
                    return $q->whereNull('parent_id');
                }

                return $q->where('parent_id', $filters['parent_id']);
            })
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        // Load nested children
        $query->with('childrenRecursive');

        return $query->get();
    }

    /**
     * Create a new location with validated data.
     */
    public function createLocation(array $data): Model
    {
        // Apply business rules and validation
        $data = $this->applyLocationBusinessRules($data);
        $this->validateLocationBusinessRules($data);

        // Handle path for hierarchical locations
        if (! empty($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path . $parent->id . '/' : $parent->id . '/';

            // Convert public ID to internal ID for storage
            $data['parent_id'] = $parent->id;
        }

        return $this->create($data);
    }

    /**
     * Update a location with validated data.
     */
    public function update($id, array $data): Model
    {
        // Apply business rules and validation
        $data = $this->applyLocationBusinessRules($data, $id);
        $this->validateLocationBusinessRules($data, $id);

        $location = $this->findById($id);

        // Update the path for hierarchical locations if parent changed
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent = $this->findById($data['parent_id']);
                $data['path'] = $parent->path ? $parent->path . $parent->id . '/' : $parent->id . '/';
            } else {
                $data['path'] = null;
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get locations with their related items.
     */
    public function getWithItems(): Collection
    {
        return $this->getQuery()
            ->with('items')
            ->get();
    }

    /**
     * Get only active locations.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get locations by parent.
     */
    public function getByParent(int $parentId): Collection
    {
        return $this->getQuery()
            ->where('parent_id', $parentId)
            ->get();
    }

    /**
     * Get locations by code.
     */
    public function getByCode(string $code): Collection
    {
        return $this->getQuery()
            ->where('code', $code)
            ->get();
    }

    /**
     * Get locations with nested children
     */
    public function getWithNestedChildren(): Collection
    {
        return $this->getQuery()
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->get();
    }

    /**
     * Get a single location with its full hierarchy.
     */
    public function findWithHierarchy($id): Model
    {
        $location = $this->findById($id);
        $location->load('childrenRecursive');
        return $location;
    }

    /**
     * Get root locations with full nested hierarchy.
     */
    public function getRootLocationsWithHierarchy(): Collection
    {
        return $this->getQuery()
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->get();
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'code' => $this->toString($params['code'] ?? null),
            'description' => $this->toString($params['description'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'parent_id' => $this->toInt($params['parent_id'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'name', 'code', 'description', 'is_active', 'parent_id',
        ]);
    }

    /**
     * Apply business rules for location operations.
     */
    private function applyLocationBusinessRules(array $data, $locationId = null): array
    {
        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for location operations.
     */
    private function validateLocationBusinessRules(array $data, $locationId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The location name is required and cannot be empty');
        }

        if (empty($data['code'])) {
            throw new \InvalidArgumentException('The location code is required and cannot be empty');
        }

        if (empty($data['org_id'])) {
            throw new \InvalidArgumentException('Organization ID is required');
        }

        // Validate organization exists
        $organization = \App\Models\Organization::find($data['org_id']);
        if (!$organization) {
            throw new \InvalidArgumentException('The specified organization does not exist');
        }

        // Validate code uniqueness within organization
        $query = Location::where('code', $data['code'])
                         ->where('org_id', $data['org_id']);
        
        if ($locationId) {
            $query->where('id', '!=', $locationId);
        }
        
        if ($query->exists()) {
            throw new \InvalidArgumentException('This location code already exists in your organization');
        }

        // Validate parent location exists if provided
        if (isset($data['parent_id']) && !empty($data['parent_id'])) {
            $parent = Location::find($data['parent_id']);
            if (!$parent) {
                throw new \InvalidArgumentException('The selected parent location does not exist in your organization');
            }

            // Prevent circular references
            if ($locationId && $data['parent_id'] == $locationId) {
                throw new \InvalidArgumentException('A location cannot be its own parent');
            }
        }
    }
}

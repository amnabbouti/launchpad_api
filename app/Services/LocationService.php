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

        // Apply filters using Laravel's when() method for clean conditional filtering
        $query->when($filters['name'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, fn ($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['description'] ?? null, fn ($q, $value) => $q->where('description', 'like', "%{$value}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['parent_id']), function ($q) use ($filters) {
                if ($filters['parent_id'] === 'null' || $filters['parent_id'] === null) {
                    return $q->whereNull('parent_id');
                }

                return $q->where('parent_id', $filters['parent_id']);
            })
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Create a new location with validated data.
     */
    public function createLocation(array $data): Model
    {
        // Handle path for hierarchical locations
        if (! empty($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path.$parent->id.'/' : $parent->id.'/';
            
            // Convert public ID to internal ID for storage
            $data['parent_id'] = $parent->id;
        }

        return $this->create($data);
    }

    /**
     * Update a location with validated data.
     */
    public function updateLocation(array $data, int $id): Model
    {
        $location = $this->findById($id);

        // Update the path for hierarchical locations if parent changed
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent = $this->findById($data['parent_id']);
                $data['path'] = $parent->path ? $parent->path.$parent->id.'/' : $parent->id.'/';
            } else {
                $data['path'] = null;
            }
        }

        return $this->update($id, $data);
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
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'name' => $params['name'] ?? null,
            'code' => $params['code'] ?? null,
            'description' => $params['description'] ?? null,
            'is_active' => isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            'parent_id' => $params['parent_id'] ?? null,
            'with' => ! empty($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
        ];
    }
}

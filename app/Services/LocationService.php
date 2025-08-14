<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

use function in_array;

class LocationService extends BaseService {
    /**
     * Create a new service instance.
     */
    public function __construct(Location $location) {
        parent::__construct($location);
    }

    /**
     * Create a new location with validated data.
     */
    public function createLocation(array $data): Model {
        $data = $this->applyLocationBusinessRules($data);
        $this->validateLocationBusinessRules($data);

        // Handle path for hierarchical locations
        if (! empty($data['parent_id'])) {
            $parent       = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path . $parent->id . '/' : $parent->id . '/';
        }

        return $this->create($data);
    }

    /**
     * Get a single location with its full hierarchy.
     */
    public function findWithHierarchy($id): Model {
        $location = $this->findById($id);
        $location->load('childrenRecursive');

        return $location;
    }

    /**
     * Get filtered locations for the current organization.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, static fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, static fn ($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['description'] ?? null, static fn ($q, $value) => $q->where('description', 'like', "%{$value}%"))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['parent_id']), static function ($q) use ($filters) {
                if ($filters['parent_id'] === 'null' || $filters['parent_id'] === null) {
                    return $q->whereNull('parent_id');
                }

                return $q->where('parent_id', $filters['parent_id']);
            })
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        if ($filters['hierarchy'] ?? true) {
            if (in_array('items', $filters['with'] ?? [], true)) {
                $query->with(['childrenRecursive.items', 'childrenRecursive' => static function ($query): void {
                    $query->with('childrenRecursive.items');
                }]);
            } else {
                $query->with('childrenRecursive');
            }
        }

        return $query;
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array {
        // Validate parameters against the allowlist
        $this->validateParams($params);

        return [
            'name'        => $this->toString($params['name'] ?? null),
            'code'        => $this->toString($params['code'] ?? null),
            'description' => $this->toString($params['description'] ?? null),
            'is_active'   => $this->toBool($params['is_active'] ?? null),
            'parent_id'   => $this->toInt($params['parent_id'] ?? null),
            'hierarchy'   => $this->toBool($params['hierarchy'] ?? true),
            'with'        => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Update a location with validated data.
     */
    public function update($id, array $data): Model {
        $data = $this->applyLocationBusinessRules($data);
        $this->validateLocationBusinessRules($data, $id);

        //        $location = $this->findById($id);

        // Update the path for hierarchical locations if the parent changed
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent       = $this->findById($data['parent_id']);
                $data['path'] = $parent->path ? $parent->path . $parent->id . '/' : $parent->id . '/';
            } else {
                $data['path'] = null;
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'code',
            'description',
            'is_active',
            'parent_id',
            'hierarchy',
        ]);
    }

    /**
     * Get valid relations for eager loading.
     */
    protected function getValidRelations(): array {
        return [
            'organization',
            'parent',
            'children',
            'childrenRecursive',
            'items',
        ];
    }

    /**
     * Apply business rules for location operations.
     */
    private function applyLocationBusinessRules(array $data): array {
        // Set the default active status if not provided
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for location operations.
     */
    private function validateLocationBusinessRules(array $data, $locationId = null): void {
        if (empty($data['name'])) {
            throw new InvalidArgumentException(__(ErrorMessages::LOCATION_NAME_REQUIRED));
        }

        if (empty($data['code'])) {
            throw new InvalidArgumentException(__(ErrorMessages::LOCATION_CODE_REQUIRED));
        }

        $query = Location::where('code', $data['code']);

        if ($locationId) {
            $query->where('id', '!=', $locationId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException(__(ErrorMessages::LOCATION_CODE_EXISTS));
        }

        // Validate parent location exists if provided
        if (! empty($data['parent_id'])) {
            $parent = Location::find($data['parent_id']);
            if (! $parent) {
                throw new InvalidArgumentException(__(ErrorMessages::LOCATION_PARENT_NOT_EXISTS));
            }

            // Prevent circular references
            if ($locationId && $data['parent_id'] === $locationId) {
                throw new InvalidArgumentException(__(ErrorMessages::LOCATION_CIRCULAR_REFERENCE));
            }
        }
    }
}

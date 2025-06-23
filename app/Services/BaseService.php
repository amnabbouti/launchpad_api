<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class BaseService
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->getQuery()->with($relations)->get($columns);
    }

    /**
     * Paginate records.
     */
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        if ($perPage <= 0) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER . ': perPage must be a positive integer');
        }

        return $this->getQuery()->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Find record by ID (supports IDs and public IDs).
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        // Try to find by internal ID first (if numeric)
        if (is_numeric($id) && (int) $id > 0) {
            $query = $this->getQuery()->with($relations);

            if (! empty($appends)) {
                $query = $query->append($appends);
            }

            $model = $query->find((int) $id, $columns);
            if ($model) {
                return $model;
            }
        }

        // Try to find by public ID
        if (method_exists($this->model, 'findByPublicId') && is_string($id)) {
            $user = auth()->user();
            if (!$user) {
                throw new InvalidArgumentException(ErrorMessages::UNAUTHORIZED);
            }

            // Pass null org_id for super admins, allowing them to access any entity
            $orgId = $user->isSuperAdmin() ? null : $user->org_id;
            if (!$user->isSuperAdmin() && !$orgId) {
                throw new InvalidArgumentException(ErrorMessages::UNAUTHORIZED);
            }

            $model = $this->model->findByPublicId($id, $orgId);
            if ($model) {
                // Load relationships if requested
                if (!empty($relations)) {
                    $model->load($relations);
                }
                return $model;
            }
        }

        throw new InvalidArgumentException(ErrorMessages::NOT_FOUND);
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        // Resolve public IDs to internal IDs for foreign key fields
        $data = $this->resolvePublicIds($data);

        return $this->model->create($data);
    }

    /**
     * Resolve public IDs to internal IDs for foreign key fields.
     */
    protected function resolvePublicIds(array $data): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return $data;
        }

        // For super admin, don't restrict by org_id to allow cross-organization access
        // For regular users, use their org_id or fallback to org_id in data
        $orgId = null;
        if ($user->is_super_admin !== true && $user->org_id !== null) {
            $orgId = $user->org_id ?? $data['org_id'] ?? null;
            
            // If we still don't have an org_id for non-super admin, we can't resolve public IDs
            if (!$orgId) {
                return $data;
            }
        }

        // Define common foreign key fields that might use public IDs
        $foreignKeyMappings = [
            'item_id' => \App\Models\Item::class,
            'supplier_id' => \App\Models\Supplier::class,
            'parent_id' => \App\Models\Location::class,
            'location_id' => \App\Models\Location::class,
            'checkout_location_id' => \App\Models\Location::class,
            'checkin_location_id' => \App\Models\Location::class,
            'checkin_user_id' => \App\Models\User::class,
            'status_id' => \App\Models\Status::class,
            'status_out_id' => \App\Models\Status::class,
            'status_in_id' => \App\Models\Status::class,
            'maintainable_id' => \App\Models\Item::class,
        ];

        foreach ($foreignKeyMappings as $field => $modelClass) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Check if it looks like a public ID (contains letters and numbers with dashes)
                if (preg_match('/^[A-Z]{2,4}-\d+$/', $data[$field])) {
                    
                    // For polymorphic relationships like maintainable_id, check if maintainable_type is set
                    if ($field === 'maintainable_id' && isset($data['maintainable_type'])) {
                        $modelClass = $data['maintainable_type'];
                    }

                    // Try to resolve public ID to internal ID
                    if (method_exists($modelClass, 'findByPublicId')) {
                        $model = $modelClass::findByPublicId($data[$field], $orgId);
                        if ($model) {
                            $data[$field] = $model->id;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Update a record by ID (supports IDs and public IDs).
     */
    public function update($id, array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        // Resolve public IDs to internal IDs for foreign key fields
        $data = $this->resolvePublicIds($data);

        $record = $this->findById($id);
        $record->update($data);

        return $record;
    }

    /**
     * Delete a record by ID (supports IDs and public IDs).
     */
    public function delete($id): bool
    {
        $record = $this->findById($id);
        return $record->delete();
    }

    /**
     * Get a query builder instance.
     */
    protected function getQuery()
    {
        return $this->model->newQuery();
    }

    /**
     * Get allowed query parameters for this service.
     */
    protected function getAllowedParams(): array
    {
        return ['with'];
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [];
    }

    /**
     * Validate request parameters against allowed list.
     */
    protected function validateParams(array $params): void
    {
        $allowedParams = $this->getAllowedParams();
        $unknownParams = array_diff(array_keys($params), $allowedParams);

        if (! empty($unknownParams)) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER . ': ' . implode(', ', $unknownParams));
        }
    }

    /**
     * Process and validate 'with' (relationships) parameter.
     */
    protected function processWithParameter($with): ?array
    {
        if (empty($with)) {
            return null;
        }

        $relations = is_string($with) ? array_filter(explode(',', $with)) : $with;

        if (!is_array($relations)) {
            return null;
        }

        $validRelations = $this->getValidRelations();
        $invalidRelations = array_diff($relations, $validRelations);

        if (!empty($invalidRelations)) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_RELATION . ': ' . implode(', ', $invalidRelations));
        }

        return $relations;
    }

    /**
     * Convert string boolean values to actual booleans.
     */
    protected function toBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Convert string to integer with validation.
     */
    protected function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Sanitize string parameter.
     */
    protected function toString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? trim($value) : null;
    }

    // ===============================================
    // GENERIC ERROR THROWING HELPER METHODS
    // ===============================================

    /**
     * Throw insufficient permissions error.
     */
    protected function throwInsufficientPermissions(): void
    {
        throw new UnauthorizedAccessException(ErrorMessages::INSUFFICIENT_PERMISSIONS);
    }

    /**
     * Throw forbidden access error.
     */
    protected function throwForbidden(): void
    {
        throw new UnauthorizedAccessException(ErrorMessages::FORBIDDEN);
    }

    /**
     * Throw unauthorized access error.
     */
    protected function throwUnauthorized(): void
    {
        throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
    }

    /**
     * Throw cross organization access error.
     */
    protected function throwCrossOrgAccess(): void
    {
        throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
    }

    /**
     * Throw resource not found error.
     */
    protected function throwNotFound(): void
    {
        throw new InvalidArgumentException(ErrorMessages::NOT_FOUND);
    }

    /**
     * Throw invalid data error.
     */
    protected function throwInvalidData(string $message = null): void
    {
        $errorMessage = $message ?? ErrorMessages::INVALID_DATA;
        throw new InvalidArgumentException($errorMessage);
    }

    /**
     * Throw validation failed error.
     */
    protected function throwValidationFailed(): void
    {
        throw new InvalidArgumentException(ErrorMessages::VALIDATION_FAILED);
    }

    /**
     * Throw resource already exists error.
     */
    protected function throwAlreadyExists(): void
    {
        throw new InvalidArgumentException(ErrorMessages::ALREADY_EXISTS);
    }

    /**
     * Throw resource in use error.
     */
    protected function throwResourceInUse(): void
    {
        throw new InvalidArgumentException(ErrorMessages::RESOURCE_IN_USE);
    }
}

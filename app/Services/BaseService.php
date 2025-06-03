<?php

namespace App\Services;

use App\Constants\ErrorMessages;
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
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER.': perPage must be a positive integer');
        }

        return $this->getQuery()->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Find record by ID.
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        // Validate that ID is numeric and positive
        if (! is_numeric($id) || (int) $id <= 0) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_ID);
        }

        $id = (int) $id;

        $query = $this->getQuery()->with($relations);

        if (! empty($appends)) {
            $query = $query->append($appends);
        }

        $model = $query->find($id, $columns);

        if (! $model) {
            $modelName = class_basename($this->model);
            throw new InvalidArgumentException(ErrorMessages::NOT_FOUND);
        }

        return $model;
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        return $this->model->create($data);
    }

    /**
     * Update a record by ID.
     */
    public function update($id, array $data): Model
    {
        // Validate that ID is numeric and positive
        if (! is_numeric($id) || (int) $id <= 0) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_ID);
        }

        $id = (int) $id;

        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        $record = $this->findById($id);

        $record->update($data);

        return $record;
    }

    /**
     * Delete a record by ID.
     */
    public function delete($id): bool
    {
        // Validate that ID is numeric and positive
        if (! is_numeric($id) || (int) $id <= 0) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_ID);
        }

        $id = (int) $id;

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
     * Process and validate request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters
        $allowedParams = $this->getAllowedParams();
        $unknownParams = array_diff(array_keys($params), $allowedParams);

        if (! empty($unknownParams)) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER.': '.implode(', ', $unknownParams));
        }

        // Process and validate 'with' parameter
        $processedParams = [
            'with' => ! empty($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
        ];

        if (! empty($processedParams['with'])) {
            $validRelations = $this->getValidRelations();
            $invalidRelations = array_diff(
                is_array($processedParams['with']) ? $processedParams['with'] : [$processedParams['with']],
                $validRelations
            );
            if (! empty($invalidRelations)) {
                throw new InvalidArgumentException(ErrorMessages::INVALID_RELATION.': '.implode(', ', $invalidRelations));
            }
        }

        return $processedParams;
    }
}

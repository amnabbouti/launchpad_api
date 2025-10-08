<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function is_array;
use function is_string;

class BaseService
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = []): Builder
    {
        return $this->getQuery()->with($relations)->select($columns);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function create(array $data): Model
    {
        if (empty($data)) {
            $message = __(ErrorMessages::EMPTY_DATA);

            throw new InvalidArgumentException($message);
        }

        // Authorization is handled by PermissionMiddleware
        $model = $this->model->newInstance($data);

        // RLS will handle organization assignment automatically
        $model->save();

        return $model;
    }

    public function delete($id): bool
    {
        $model = $this->findModel($id);

        // Authorization is handled by PermissionMiddleware
        return $model->delete();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $model = $this->findModel($id, $columns, $relations);

        // Authorization is handled by PermissionMiddleware
        if (! empty($appends)) {
            $model->append($appends);
        }

        return $model;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): Model
    {
        if (empty($data)) {
            $message = __(ErrorMessages::EMPTY_DATA);

            throw new InvalidArgumentException($message);
        }

        $model = $this->findModel($id);

        // Authorization is handled by PermissionMiddleware
        $model->update($data);

        return $model->fresh();
    }

    protected function findModel($id, array $columns = ['*'], array $relations = [])
    {
        $model = $this->getQuery()->with($relations)->find($id, $columns);
        if ($model) {
            return $model;
        }

        $message = __(ErrorMessages::NOT_FOUND);

        throw new InvalidArgumentException($message);
    }

    protected function getAllowedParams(): array
    {
        return ['with', 'per_page', 'page'];
    }

    protected function getQuery(): Builder
    {
        // Use the same connection that was used to set RLS context
        // $connectionName = session('rls_connection_name', 'pgsql');
        // $connection     = DB::connection($connectionName);

        // Create a query using the specific connection
        $query = $this->model->newQuery();

        return $query;
    }

    protected function getValidRelations(): array
    {
        return [];
    }

    protected function processWithParameter($with): ?array
    {
        if (empty($with)) {
            return null;
        }

        $relations = is_string($with) ? array_filter(explode(',', $with)) : $with;

        if (! is_array($relations)) {
            return null;
        }

        $validRelations   = $this->getValidRelations();
        $invalidRelations = array_diff($relations, $validRelations);

        if (! empty($invalidRelations)) {
            $message = __(ErrorMessages::INVALID_RELATION) . ': ' . implode(', ', $invalidRelations);

            throw new InvalidArgumentException($message);
        }

        return $relations;
    }

    protected function toBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function toString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? mb_trim($value) : null;
    }

    protected function validateParams(array $params): void
    {
        $allowedParams = $this->getAllowedParams();
        $unknownParams = array_diff(array_keys($params), $allowedParams);

        if (! empty($unknownParams)) {
            $message = __(ErrorMessages::INVALID_QUERY_PARAMETER) . ': ' . implode(', ', $unknownParams);

            throw new InvalidArgumentException($message);
        }
    }
}

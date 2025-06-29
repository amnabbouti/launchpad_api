<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Services\AuthorizationEngine;
use App\Services\PublicIdResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class BaseService
{
    protected Model $model;
    protected string $resource;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resource = AuthorizationEngine::getResourceFromModel($model);
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->getQuery()->with($relations)->get($columns);
    }

    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        if ($perPage <= 0) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER . ': perPage must be a positive integer');
        }

        return $this->getQuery()->with($relations)->paginate($perPage, $columns);
    }

    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $model = $this->findModel($id, $columns, $relations);

        AuthorizationEngine::authorize('view', $this->resource, $model);

        if (!empty($appends)) {
            $model->append($appends);
        }

        return $model;
    }

    public function create(array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        AuthorizationEngine::authorize('create', $this->resource);

        $data = $this->resolvePublicIds($data);
        $model = $this->model->newInstance($data);
        
        AuthorizationEngine::autoAssignOrganization($model);
        $model->save();

        return $model;
    }

    public function update($id, array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException(ErrorMessages::EMPTY_DATA);
        }

        $model = $this->findModel($id);
        
        AuthorizationEngine::authorize('update', $this->resource, $model);

        $data = $this->resolvePublicIds($data);
        $model->update($data);

        return $model->fresh();
    }

    public function delete($id): bool
    {
        $model = $this->findModel($id);
        
        AuthorizationEngine::authorize('delete', $this->resource, $model);

        return $model->delete();
    }

    protected function getQuery()
    {
        $query = $this->model->newQuery();
        return AuthorizationEngine::applyOrganizationScope($query, $this->resource);
    }

    protected function findModel($id, array $columns = ['*'], array $relations = [])
    {
        if (is_numeric($id) && (int) $id > 0) {
            $model = $this->getQuery()->with($relations)->find((int) $id, $columns);
            if ($model) {
                return $model;
            }
        }

        if (method_exists($this->model, 'findByPublicId') && is_string($id)) {
            $user = AuthorizationEngine::getCurrentUser();
            if (!$user) {
                throw new InvalidArgumentException(ErrorMessages::UNAUTHORIZED);
            }

            $orgId = $user->org_id;
            $model = $this->model->findByPublicId($id, $orgId);
            if ($model) {
                if (!empty($relations)) {
                    $model->load($relations);
                }
                return $model;
            }
        }

        throw new InvalidArgumentException(ErrorMessages::NOT_FOUND);
    }

    protected function resolvePublicIds(array $data): array
    {
        return PublicIdResolver::resolve($data);
    }

    protected function getAllowedParams(): array
    {
        return ['with'];
    }

    protected function getValidRelations(): array
    {
        return [];
    }

    protected function validateParams(array $params): void
    {
        $allowedParams = $this->getAllowedParams();
        $unknownParams = array_diff(array_keys($params), $allowedParams);

        if (!empty($unknownParams)) {
            throw new InvalidArgumentException(ErrorMessages::INVALID_QUERY_PARAMETER . ': ' . implode(', ', $unknownParams));
        }
    }

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

        return is_string($value) ? trim($value) : null;
    }
} 
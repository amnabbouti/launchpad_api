<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    public function all(array $columns = ['*'], array $relations = []): Builder
    {
        return $this->getQuery()->with($relations)->select($columns);
    }

    /**
     * @throws UnauthorizedAccessException
     * @throws InvalidArgumentException
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $model = $this->findModel($id, $columns, $relations);

        AuthorizationEngine::authorize('view', $this->resource, $model);

        if (! empty($appends)) {
            $model->append($appends);
        }

        return $model;
    }

    /**
     * @throws UnauthorizedAccessException
     * @throws InvalidArgumentException
     */
    public function create(array $data): Model
    {
        if (empty($data)) {
            $message = __(ErrorMessages::EMPTY_DATA);
            throw new InvalidArgumentException($message);
        }

        AuthorizationEngine::authorize('create', $this->resource);

        $data = $this->resolvePublicIds($data);
        $model = $this->model->newInstance($data);

        AuthorizationEngine::autoAssignOrganization($model);
        $model->save();

        return $model;
    }

    /**
     * @throws UnauthorizedAccessException
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): Model
    {
        if (empty($data)) {
            $message = __(ErrorMessages::EMPTY_DATA);
            throw new InvalidArgumentException($message);
        }

        $model = $this->findModel($id);

        AuthorizationEngine::authorize('update', $this->resource, $model);

        $data = $this->resolvePublicIds($data);
        $model->update($data);

        return $model->fresh();
    }

    /**
     * @throws UnauthorizedAccessException
     */
    public function delete($id): bool
    {
        $model = $this->findModel($id);

        AuthorizationEngine::authorize('delete', $this->resource, $model);

        return $model->delete();
    }

    protected function getQuery(): Builder
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
            if (! $user) {
                $message = __(ErrorMessages::UNAUTHORIZED);
                throw new InvalidArgumentException($message);
            }

            $orgId = $user->org_id;
            $model = $this->model->findByPublicId($id, $orgId);
            if ($model) {
                if (! empty($relations)) {
                    $model->load($relations);
                }

                return $model;
            }
        }

        $message = __(ErrorMessages::NOT_FOUND);
        throw new InvalidArgumentException($message);
    }

    protected function resolvePublicIds(array $data): array
    {
        return PublicIdResolver::resolve($data);
    }

    protected function getAllowedParams(): array
    {
        return ['with', 'per_page', 'page'];
    }

    protected function getValidRelations(): array
    {
        return [];
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

    protected function processWithParameter($with): ?array
    {
        if (empty($with)) {
            return null;
        }

        $relations = is_string($with) ? array_filter(explode(',', $with)) : $with;

        if (! is_array($relations)) {
            return null;
        }

        $validRelations = $this->getValidRelations();
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

        return is_string($value) ? trim($value) : null;
    }
}

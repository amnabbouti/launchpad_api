<?php

namespace App\Services;

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

    // All records
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->get($columns);
    }

    // Paginate
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        if ($perPage <= 0) {
            throw new InvalidArgumentException('Per page must be a positive integer');
        }
        
        return $this->model->with($relations)
            ->paginate($perPage, $columns);
    }

    // Find by ID
    public function findById(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
        
        $query = $this->model->with($relations);

        if (! empty($appends)) {
            $query = $query->append($appends);
        }

        return $query->find($id, $columns);
    }

    // Create
    public function create(array $data): Model
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }
        
        return $this->model->create($data);
    }

    // Update
    public function update(int $id, array $data): Model
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
        
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }
        
        $record = $this->findById($id);
        
        if (!$record) {
            throw new InvalidArgumentException("Record with ID {$id} not found");
        }
        
        $record->update($data);

        return $record;
    }

    // Delete
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
        
        $record = $this->findById($id);
        
        if (!$record) {
            throw new InvalidArgumentException("Record with ID {$id} not found");
        }
        
        return $record->delete();
    }
}

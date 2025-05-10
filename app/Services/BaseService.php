<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseService
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // get all records
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    // get paginated records
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    // get by id
    public function findById(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = $this->model->with($relations);

        if (!empty($appends)) {
            $query = $query->append($appends);
        }

        return $query->find($id, $columns);
    }

    // create a new record
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    // update a record
    public function update(int $id, array $data): Model
    {
        $record = $this->findById($id);
        $record->update($data);
        return $record;
    }

    // delete a record
    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }
}
